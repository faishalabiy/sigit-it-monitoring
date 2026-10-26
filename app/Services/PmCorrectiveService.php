<?php

namespace App\Services;

use App\Models\PmCorrectiveAction;
use App\Models\PmRun;
use App\Models\PmRunCheck;
use Illuminate\Support\Facades\DB;

class PmCorrectiveService
{
    // Recommendations are prompts for investigation, never automatic repairs.
    public function recommendation(PmRunCheck $check): string
    {
        return match ($check->method) {
            'disk_usage'=>'Verifikasi kapasitas filesystem dan penyebab pertumbuhan; susun rencana pembersihan atau ekspansi terotorisasi. Jangan hapus data otomatis.',
            'cpu_usage'=>'Analisis proses, tren CPU dan beban kerja pada server yang benar sebelum melakukan optimasi.',
            'memory_usage'=>'Periksa memory available, proses dominan, dan tren penggunaan RAM. Tentukan apakah perlu optimasi atau penambahan kapasitas.',
            'ssl_expiry'=>'Periksa jadwal perpanjangan sertifikat TLS dan verifikasi hasil pemasangan serta rantai sertifikat.',
            'http_status','response_time'=>'Periksa endpoint aplikasi, respons HTTP, log error, dan dependensi; dokumentasikan akar penyebab.',
            'database_connection'=>'Periksa health endpoint, konektivitas database aplikasi terkait, dan log dengan hak akses minimum.',
            'service_status','systemd_unit','queue_worker'=>'Periksa unit/service/worker sesuai target beserta log, kemudian rencanakan pemulihan berizin.',
            'scheduler_heartbeat'=>'Periksa konfigurasi cron, scheduler Laravel, dan log eksekusi pada server terkait.',
            'backup_age'=>'Periksa jadwal, lokasi, dan kelengkapan backup; lakukan pengujian restore terkontrol secara terpisah.',
            default=>'Verifikasi temuan terhadap sistem dan parameter terkait, tentukan akar penyebab, tindakan korektif, dan bukti penyelesaian.',
        };
    }

    public function createFromCheck(PmRunCheck $check, ?int $actorId=null, bool $allowUnavailable=false): ?PmCorrectiveAction
    {
        $eligible=['warning','fail'];
        if ($allowUnavailable) $eligible[]='unavailable';
        if (! in_array($check->status,$eligible,true)) return null;
        $action=DB::transaction(function () use ($check,$actorId) {
            $existing=PmCorrectiveAction::where('pm_run_check_id',$check->id)->first();
            if ($existing) return $existing;

            $action=PmCorrectiveAction::create([
                'pm_run_id'=>$check->pm_run_id,
                'pm_run_check_id'=>$check->id,
                'status'=>'open',
                'priority'=>$check->status==='fail'?'high':'medium',
                'finding'=>mb_substr($check->system_name.' / '.$check->parameter_name.': '
                    .strtoupper($check->status).' — '.($check->message??'Perlu investigasi'),0,512),
                'recommendation'=>$this->recommendation($check),
                'created_by'=>$actorId,
            ]);
            $action->events()->create([
                'actor_id'=>$actorId,'event'=>'created',
                'changes'=>['check_status'=>$check->status,'source_run_id'=>$check->pm_run_id],
            ]);
            return $action;
        });
        return $action;
    }

    public function createForRun(PmRun $run): int
    {
        if ($run->status==='failed' || $run->status==='running') return 0;
        $created=0;
        foreach ($run->checks()->whereIn('status',['warning','fail'])->cursor() as $check) {
            if (! PmCorrectiveAction::where('pm_run_check_id',$check->id)->exists()) {
                $this->createFromCheck($check);
                $created++;
            }
        }
        return $created;
    }
}