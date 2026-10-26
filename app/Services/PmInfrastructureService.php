<?php

namespace App\Services;

use App\Models\PmSystem;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

class PmInfrastructureService
{
    public function __construct(private readonly PmLocalHostService $localHost) {}

    public function check(PmSystem $system, string $method, string $code): array
    {
        if (! $this->localHost->appliesTo($system->host)) {
            return $this->missing('Target bukan server lokal yang diizinkan.');
        }
        if ($method === 'systemd_unit') return $this->systemdUnit($system, $code);
        if ($method === 'scheduler_heartbeat') return $this->schedulerHeartbeat($system, $code);
        if ($method === 'queue_worker') return $this->queueWorker($system, $code);
        return $this->missing('Metode pemeriksaan infrastruktur tidak dikenal.');
    }

    protected function systemdUnit(PmSystem $system, string $code): array
    {
        if ($system->type !== 'server') return $this->missing('Pemeriksaan unit systemd hanya untuk target jenis Server.');
        $unit = config('pm.infra_units.'.$code);
        if (! is_string($unit) || ! preg_match('/^[a-z0-9][a-z0-9.@_-]{0,63}$/',$unit)) {
            return $this->missing('Unit service belum terdaftar pada konfigurasi SIGIT.');
        }
        try {
            // Fixed systemctl binary, allowlisted unit, no shell interpolation.
            $process = new Process(['/usr/bin/systemctl','is-active','--',$unit]);
            $process->setTimeout(3);
            $process->run();
            $state = strtolower(trim($process->getOutput()));
            if ($state==='active' && $process->isSuccessful()) {
                return ['status'=>'ok','value'=>'active',
                    'message'=>'Unit '.$unit.' berstatus active menurut systemd. Ini bukan pengujian fungsional service.'];
            }
            if (in_array($state,['inactive','failed','activating','deactivating'],true)) {
                return ['status'=>'ok','value'=>$state,
                    'message'=>'Unit '.$unit.' menurut systemd: '.$state.'.'];
            }
            return $this->missing('Status unit '.$unit.' tidak dapat dipastikan melalui systemd.');
        } catch (Throwable $exception) {
            report($exception);
            return $this->missing('Pemeriksaan systemd tidak dapat dijalankan.');
        }
    }

    protected function schedulerHeartbeat(PmSystem $system, string $code): array
    {
        if ($system->type !== 'server' || $code !== 'HOST_SIGIT_SCHEDULER') {
            return $this->missing('Heartbeat hanya tersedia untuk scheduler SIGIT pada target Server.');
        }
        $timestamp = Cache::get('pm:sigit:scheduler-last-tick');
        if (! is_numeric($timestamp) || (int)$timestamp<=0) {
            return $this->missing('Belum ada bukti heartbeat SIGIT Scheduler.');
        }
        $age = max(0,(time()-(int)$timestamp)/60);
        return ['status'=>'ok','value'=>round($age,2),
            'message'=>'Menit sejak pm:run-due terakhir dijalankan oleh Laravel Scheduler SIGIT; bukan bukti scheduler aplikasi lain.'];
    }

    protected function queueWorker(PmSystem $system, string $code): array
    {
        if ($system->type !== 'server') return $this->missing('Proses queue hanya dicek pada target Server.');
        $folder = config('pm.queue_worker_paths.'.$code);
        if (! is_string($folder) || ! is_dir($folder)) {
            return $this->missing('Jalur aplikasi queue worker belum dikonfigurasi.');
        }
        $proc = @scandir('/proc');
        if (! is_array($proc)) return $this->missing('Informasi proses Linux tidak dapat dibaca.');
        $unreadable=false;
        foreach ($proc as $pid) {
            if (! ctype_digit($pid)) continue;
            $cmd=@file_get_contents('/proc/'.$pid.'/cmdline');
            if (! is_string($cmd) || ! str_contains($cmd,'artisan'."\0")
                || ! str_contains($cmd,'queue:work')) continue;
            $cwd=@readlink('/proc/'.$pid.'/cwd');
            if ($cwd===false) {$unreadable=true; continue;}
            if (rtrim($cwd,'/')===rtrim($folder,'/')) {
                return ['status'=>'ok','value'=>'running',
                    'message'=>'Ditemukan artisan queue:work yang berasal dari direktori aplikasi '.$code.'.'];
            }
        }
        return $unreadable
            ? $this->missing('Ada proses worker tetapi identitas direktori proses tidak dapat diverifikasi (izin /proc).')
            : ['status'=>'ok','value'=>'not_running',
                'message'=>'Tidak ditemukan artisan queue:work untuk aplikasi '.$code.'.'];
    }

    private function missing(string $reason): array
    {
        return ['status'=>'unavailable','value'=>null,'message'=>$reason];
    }
}