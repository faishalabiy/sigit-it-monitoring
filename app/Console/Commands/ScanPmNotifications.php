<?php

namespace App\Console\Commands;

use App\Services\PmNotificationService;
use Illuminate\Console\Command;

class ScanPmNotifications extends Command {
    protected $signature='pm:notifications:scan';
    protected $description='Scan jadwal PM, hasil FAIL, corrective overdue, dan bukti backup; kirim email bila diaktifkan.';
    public function handle(PmNotificationService $notifications):int {
        $result=$notifications->scan();
        $emails=$notifications->deliverPending();
        $this->info('Notifikasi baru: '.json_encode($result).
            '; email dikirim: '.$emails['sent'].'; gagal: '.$emails['failed']);
        return self::SUCCESS;
    }
}