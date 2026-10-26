<?php

namespace App\Services;

use App\Models\PmSystem;
use Throwable;

class PmBackupEvidenceService
{
    /**
     * Reports age of a specifically configured backup artifact only.
     * A recent file is NOT evidence of a successful restore.
     */
    public function check(PmSystem $system): array
    {
        $domain=strtolower((string)parse_url((string)$system->target_url,PHP_URL_HOST));
        $sources=config('pm.backup_directories', []);
        $directory=is_array($sources) ? ($sources[$domain] ?? null) : null;
        if (! is_string($directory) || $directory==='' || ! is_dir($directory)) {
            return $this->unavailable('Sumber backup aplikasi belum dikonfigurasi atau direktori tidak ditemukan. Tidak ada backup yang diasumsikan berhasil.');
        }
        $root=realpath($directory);
        if ($root===false || ! is_dir($root) || ! is_readable($root)) {
            return $this->unavailable('Direktori backup tidak dapat dibaca oleh SIGIT.');
        }
        try {
            $newest=null;
            $newestSize=null;
            $iterator=new \FilesystemIterator($root,\FilesystemIterator::SKIP_DOTS);
            $files=0;
            foreach ($iterator as $file) {
                // Never follow symlinks; only regular, non-empty artifact files.
                if (++$files>2000) return $this->unavailable('Terlalu banyak file di direktori backup; perlu direktori khusus backup.');
                if ($file->isLink() || ! $file->isFile() || ! $file->isReadable()) continue;
                if (! preg_match('/\.(sql|sql\.gz|dump|backup|tar\.gz|zip)$/i',$file->getFilename())) continue;
                if ($file->getSize() <= 0) continue;
                $time=$file->getMTime();
                if ($time>time()+60) continue; // Unexpected future timestamps
                if ($newest===null || $time>$newest) {
                    $newest=$time;
                    $newestSize=$file->getSize();
                }
            }
            if ($newest===null) {
                return $this->unavailable('Tidak ditemukan artefak backup non-kosong dengan ekstensi yang didukung di direktori terdaftar.');
            }
            $age=round(max(0,time()-$newest)/86400,2);
            return ['status'=>'ok','value'=>$age,
                'message'=>'Usia artefak backup terbaru '.$age.' hari (ukuran '.round($newestSize/1024/1024,1).' MiB). Keberadaan file tidak menjamin isi atau keberhasilan restore.'];
        } catch (Throwable $exception) {
            report($exception);
            return $this->unavailable('Bukti backup tidak dapat dibaca.');
        }
    }

    private function unavailable(string $reason): array
    {
        return ['status'=>'unavailable','value'=>null,'message'=>$reason];
    }
}