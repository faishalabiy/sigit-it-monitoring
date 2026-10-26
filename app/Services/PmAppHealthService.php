<?php

namespace App\Services;

use App\Models\PmSystem;
use Illuminate\Support\Facades\Http;
use Throwable;

class PmAppHealthService
{
    private array $responses = [];

    public function __construct(private readonly PmLocalHostService $localHost) {}

    public function read(PmSystem $system, string $method): array
    {
        if (! in_array($method,['database_connection','service_status'],true)) {
            return $this->unavailable('Metode pemeriksaan tidak didukung.');
        }
        if (! $this->localHost->appliesTo($system->host)) {
            return $this->unavailable('Target tidak berada pada server dengan konektor health terotorisasi.');
        }

        $url = rtrim((string)$system->target_url,'/');
        $host = strtolower((string)parse_url($url,PHP_URL_HOST));
        $clients = config('pm.health_clients', []);
        $secret = is_array($clients) ? ($clients[$host] ?? null) : null;

        // Strict exact hostname and root URL allowlist. No user-controlled paths,
        // redirects, ports, userinfo or IP destinations.
        if ($url !== 'https://'.$host || ! is_string($secret) || strlen($secret)<32) {
            return $this->unavailable('Aplikasi belum memiliki health connector yang diizinkan.');
        }

        if (! array_key_exists($host,$this->responses)) {
            $this->responses[$host] = $this->fetch($host,$secret);
        }
        $response=$this->responses[$host];
        if (! is_array($response)) {
            return $this->unavailable('Endpoint health aplikasi tidak dapat diverifikasi atau dijangkau.');
        }

        $key=$method==='database_connection'?'database':'service';
        $value=$response[$key];
        return ['status'=>'ok','value'=>$value,
            'message'=>'Status '.($key==='database'?'koneksi database':'aplikasi Laravel').
                ' diperiksa langsung melalui endpoint health terlindungi.'];
    }

    private function fetch(string $host,string $secret): ?array
    {
        try {
            $stamp=(string)time();
            $signature=hash_hmac('sha256',$stamp."\nGET\n/internal/pm/health",$secret);
            $response=Http::withoutRedirecting()->acceptJson()
                ->withHeaders([
                    'X-PM-Timestamp'=>$stamp,
                    'X-PM-Signature'=>$signature,
                ])
                ->withOptions(['verify'=>true,'allow_redirects'=>false])
                ->connectTimeout(3)->timeout(6)
                ->get('https://'.$host.'/internal/pm/health');

            if ($response->status()!==200) return null;
            $data=$response->json();
            if (! is_array($data)
                || ! in_array($data['service']??null,['running','not_running'],true)
                || ! in_array($data['database']??null,['connected','disconnected'],true)) {
                return null;
            }
            return ['service'=>$data['service'],'database'=>$data['database']];
        } catch (Throwable $error) {
            report($error);
            return null;
        }
    }

    private function unavailable(string $reason): array
    {
        return ['status'=>'unavailable','value'=>null,'message'=>$reason];
    }
}
