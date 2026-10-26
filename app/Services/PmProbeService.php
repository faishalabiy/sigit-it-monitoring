<?php

namespace App\Services;

use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use Illuminate\Support\Facades\Http;
use Throwable;

class PmProbeService
{
    private array $httpCache = [];
    private array $sslCache = [];

    public function __construct(
        private readonly PmLocalHostService $localHost,
        private readonly PmAppHealthService $appHealth,
        private readonly PmInfrastructureService $infrastructure,
        private readonly PmBackupEvidenceService $backups,
        private readonly PmProxmoxNasBackupService $proxmoxBackups
    ) {}

    public function check(PmSystem $system, PmParameterTemplate $parameter): array
    {
        $method = $parameter->method;

        if ($method === 'manual_check') {
            return ['status'=>'pending','value'=>null,'message'=>'Memerlukan checklist dan bukti pemeriksaan oleh petugas.'];
        }

        if ($method === 'backup_present') {
            $reading=$this->proxmoxBackups->check($system);
            return $this->assessReading($reading,$parameter);
        }

        if ($method === 'backup_age') {
            $reading=$this->backups->check($system);
            return $this->assessReading($reading,$parameter);
        }

        if (in_array($method,['systemd_unit','scheduler_heartbeat','queue_worker'],true)) {
            $reading=$this->infrastructure->check($system,$method,$parameter->code);
            return $this->assessReading($reading,$parameter);
        }

        if (in_array($method, ['cpu_usage','memory_usage','disk_usage'],true)) {
            if (! $this->localHost->appliesTo($system->host)) {
                return ['status'=>'unavailable','value'=>null,
                    'message'=>'Server target bukan host lokal yang diizinkan; perlu agent monitoring server.'];
            }
            try {
                $value=$this->localHost->read($method);
                $result=$this->evaluate($value,$parameter);
                return ['status'=>$result,'value'=>(string)$value,
                    'message'=>'Pengukuran server fisik/VM bersama '.$system->host.
                        ($method==='disk_usage'?' (filesystem /)':'').
                        '. Bukan pemakaian khusus aplikasi.'];
            } catch (Throwable $exception) {
                report($exception);
                return ['status'=>'unavailable','value'=>null,
                    'message'=>'Tidak dapat membaca metrik lokal dari sistem operasi.'];
            }
        }

        if ($method === 'database_connection'
            && $this->localHost->appliesTo($system->host)
            && rtrim((string)$system->target_url,'/') === rtrim((string)config('app.url'),'/')) {
            try {
                \Illuminate\Support\Facades\DB::connection()->select('SELECT 1');
                return ['status'=>$this->evaluate('connected',$parameter),'value'=>'connected',
                    'message'=>'Koneksi database SIGIT diperiksa melalui Laravel (SELECT 1 tanpa perubahan data).'];
            } catch (Throwable $exception) {
                report($exception);
                return ['status'=>'fail','value'=>'disconnected',
                    'message'=>'Koneksi database SIGIT tidak berhasil.'];
            }
        }

        // Application health is independent of SIGIT's own MySQL connection.
        // Never infer PADUKA/SIPO database status from SIGIT connectivity.
        $appDomain = strtolower((string) parse_url((string)$system->target_url,PHP_URL_HOST));
        if (in_array($method,['database_connection','service_status'],true)
            && in_array($appDomain,['sipo.ptrekaindo.co.id','paduka.ptrekaindo.co.id'],true)) {
            $reading=$this->appHealth->read($system,$method);
            if ($reading['status'] !== 'ok') return $reading;
            $value=$reading['value'];
            return [
                'status'=>$this->evaluate($value,$parameter),
                'value'=>$value,
                'message'=>$reading['message'],
            ];
        }

        if (! in_array($method, ['http_status','response_time','ssl_expiry','service_status'],true)) {
            return ['status'=>'unavailable','value'=>null,
                'message'=>'Konektor/agent untuk metode ini belum dikonfigurasi; tidak ada pengukuran yang dilakukan.'];
        }

        $url = $system->target_url;
        if (! $this->isAllowedTarget($url)) {
            return ['status'=>'unavailable','value'=>null,
                'message'=>'URL pemeriksaan harus HTTPS di domain perusahaan yang diizinkan; periksa Master Sistem.'];
        }

        try {
            if ($method === 'ssl_expiry') {
                $probe = $this->sslCache[$system->id] ??= $this->probeSsl($url);
            } else {
                $probe = $this->httpCache[$system->id] ??= $this->probeHttp($url);
            }

            if ($method === 'service_status' && $probe['status']==='ok') {
                $code=(int)$probe['http_status'];
                $running=$code>=200 && $code<400;
                return ['status'=>$this->evaluate($running?'running':'not_running',$parameter),
                    'value'=>$running?'running':'not_running',
                    'message'=>'Status layanan web dari HTTP '.(string)$code.
                        ' (bukan status service systemd atau proses background).'];
            }

            if ($method === 'response_time' && $probe['status']==='ok' && (int)$probe['http_status']>=500) {
                return ['status'=>'fail','value'=>(string)$probe['response_time'],
                    'message'=>'Web menghasilkan HTTP '.$probe['http_status'].
                        '; waktu respons halaman error tidak dinilai sehat.'];
            }

            if ($probe['status'] !== 'ok') {
                return ['status'=>'unavailable','value'=>null,'message'=>$probe['message']];
            }

            $value = $probe[$method === 'http_status' ? 'http_status'
                : ($method === 'response_time' ? 'response_time' : 'ssl_expiry')];
            $result = $this->evaluate($value,$parameter);

            return ['status'=>$result,'value'=>(string)$value,
                'message'=>($method==='http_status' || $method==='response_time'
                    ? 'Endpoint pemeriksaan: '.($probe['endpoint_path']??'/').'. ' : '')
                    .(match ($result) {
                    'pass'=>'Nilai memenuhi parameter pemeriksaan.',
                    'warning'=>'Nilai memasuki batas WARNING.',
                    'fail'=>'Nilai melewati batas FAIL atau tidak sesuai nilai harapan.',
                    default=>'Nilai berhasil dibaca tetapi belum memiliki aturan evaluasi.',
                })];
        } catch (Throwable $exception) {
            report($exception);
            return ['status'=>'unavailable','value'=>null,
                'message'=>'Tidak dapat memperoleh hasil pemeriksaan dari target (koneksi, DNS atau TLS).'];
        }
    }

    public function isAllowedTarget(?string $url): bool
    {
        if (! is_string($url) || strlen($url)>512) return false;
        $p = parse_url($url);
        if (! is_array($p) || strtolower($p['scheme']??'')!=='https'
            || empty($p['host']) || ! empty($p['user']) || ! empty($p['pass'])
            || isset($p['query']) || isset($p['fragment']) || (isset($p['port']) && (int)$p['port']!==443)) {
            return false;
        }
        $host = strtolower(rtrim($p['host'],'.'));
        if (filter_var($host,FILTER_VALIDATE_IP)) return false;

        foreach (config('pm.allowed_domains',[]) as $domain) {
            $domain = strtolower(ltrim((string)$domain,'.'));
            if ($domain !== '' && ($host===$domain || str_ends_with($host,'.'.$domain))) {
                return true;
            }
        }
        return false;
    }

    protected function probeHttp(string $url): array
    {
        // Prefer explicit, trusted Laravel health paths. Root URLs may 302 to login.
        $hostname=strtolower((string)parse_url($url,PHP_URL_HOST));
        $path=parse_url($url,PHP_URL_PATH);
        $paths=config('pm.http_health_paths', []);
        $healthPath=is_array($paths) ? ($paths[$hostname] ?? null) : null;
        $endpoint=($path===null || $path==='/' || $path==='')
            && is_string($healthPath)
            && preg_match('#^/[a-zA-Z0-9/_-]+$#',$healthPath)
                ? 'https://'.$hostname.$healthPath : $url;
        // No redirects, no cookies or credentials. Only safe GET requests to trusted URLs.
        $start = hrtime(true);
        $response = Http::withoutRedirecting()->withOptions([
            'verify'=>true, 'allow_redirects'=>false,
        ])->connectTimeout((int)config('pm.connect_timeout',3))
          ->timeout((int)config('pm.timeout',6))
          ->withHeaders(['Accept'=>'text/html,application/json'])
          ->get($endpoint);

        return [
            'status'=>'ok',
            'http_status'=>$response->status(),
            'response_time'=>round((hrtime(true)-$start)/1e6, 2),
            'endpoint_path'=>(string)(parse_url($endpoint,PHP_URL_PATH) ?: '/'),
        ];
    }

    protected function probeSsl(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $context=stream_context_create(['ssl'=>[
            'capture_peer_cert'=>true,
            'verify_peer'=>true,'verify_peer_name'=>true,
            'peer_name'=>$host,'SNI_enabled'=>true,
        ]]);

        $socket=@stream_socket_client('ssl://'.$host.':443',$code,$error,
            (int) config('pm.timeout',6),STREAM_CLIENT_CONNECT,$context);
        if ($socket===false) return ['status'=>'unavailable','message'=>'TLS tidak dapat dihubungi atau sertifikat tidak valid.'];

        $certificate=stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
        fclose($socket);
        if (! $certificate) return ['status'=>'unavailable','message'=>'Sertifikat TLS tidak berhasil dibaca.'];

        $data=openssl_x509_parse($certificate);
        if (! is_array($data) || ! isset($data['validTo_time_t'])) {
            return ['status'=>'unavailable','message'=>'Tanggal akhir sertifikat TLS tidak tersedia.'];
        }

        $seconds=$data['validTo_time_t']-time();
        return ['status'=>'ok','ssl_expiry'=>(int)floor($seconds/86400)];
    }

    private function assessReading(array $reading, PmParameterTemplate $parameter): array
    {
        if (($reading['status']??null)!=='ok') return $reading;
        $value=$reading['value'];
        return [
            'status'=>$this->evaluate($value,$parameter),
            'value'=>(string)$value,
            'message'=>$reading['message'],
        ];
    }

    private function evaluate(int|float|string $value,PmParameterTemplate $parameter): string
    {
        $number=is_numeric($value) ? (float)$value : null;
        if ($number!==null) {
            if (($parameter->failure_min!==null && $number <= (float)$parameter->failure_min)
                || ($parameter->failure_max!==null && $number >= (float)$parameter->failure_max)) return 'fail';
            if (($parameter->warning_min!==null && $number <= (float)$parameter->warning_min)
                || ($parameter->warning_max!==null && $number >= (float)$parameter->warning_max)) return 'warning';
        }

        if (filled($parameter->expected_value)
            && strcasecmp((string)$value, trim((string)$parameter->expected_value))!==0) return 'fail';

        if ($number===null && blank($parameter->expected_value)) return 'unavailable';

        if ($number!==null && blank($parameter->expected_value)
            && $parameter->warning_min===null && $parameter->warning_max===null
            && $parameter->failure_min===null && $parameter->failure_max===null) return 'unavailable';

        return 'pass';
    }
}