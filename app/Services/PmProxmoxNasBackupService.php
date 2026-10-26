<?php

namespace App\Services;

use App\Models\PmSystem;
use FilesystemIterator;
use Throwable;

class PmProxmoxNasBackupService
{
    /**
     * Read-only NAS evidence: a completed, non-empty vzdump archive for the
     * explicitly configured shared application VM. No restore verification.
     */
    public function check(PmSystem $system): array
    {
        $hostname = strtolower((string) parse_url((string) $system->target_url, PHP_URL_HOST));
        if (! in_array($hostname, ['paduka.ptrekaindo.co.id', 'sipo.ptrekaindo.co.id'], true)) {
            return $this->unavailable('Target ini belum dipetakan ke backup VM Proxmox bersama.');
        }

        $source = config('pm.proxmox_backup', []);
        $vmId = (string) ($source['shared_vm_id'] ?? '');
        $directory = (string) ($source['dump_directory'] ?? '');
        if (! preg_match('/^[1-9][0-9]{1,8}$/D', $vmId)) {
            return $this->unavailable('VM ID bersama PADUKA/SIPO belum dikonfigurasi (PM_PROXMOX_SHARED_VM_ID).');
        }
        if ($directory === '' || ! is_dir($directory) || ! is_readable($directory)) {
            return $this->unavailable('Folder dump backup NAS Proxmox belum ter-mount atau tidak dapat dibaca SIGIT.');
        }

        try {
            $root = realpath($directory);
            if (! $root || ! is_dir($root)) {
                return $this->unavailable('Sumber backup NAS tidak tersedia.');
            }

            $newest = null;
            $newestName = null;
            $files = 0;
            foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $file) {
                if (++$files > 10000) {
                    return $this->unavailable('Isi folder backup melebihi batas pemeriksaan (10.000 berkas).');
                }
                if ($file->isLink() || ! $file->isFile() || ! $file->isReadable()) continue;

                // Exact VM match prevents VM 100 being confused with VM 101.
                // Ignore temporary or partial archives and metadata-only files.
                if (! preg_match('/^vzdump-qemu-'.preg_quote($vmId, '/')
                    .'-\d{4}_\d{2}_\d{2}-\d{2}_\d{2}_\d{2}\.vma(?:\.(?:zst|gz|lzo))?$/D',
                    $file->getFilename())) continue;
                if ($file->getSize() <= 0) continue;
                $modified = $file->getMTime();
                if ($modified > time() + 60) continue;
                if ($newest === null || $modified > $newest) {
                    $newest = $modified;
                    $newestName = $file->getFilename();
                }
            }

            if ($newest === null) {
                return ['status' => 'ok', 'value' => 'missing',
                    'message' => 'Tidak ditemukan arsip vzdump lengkap untuk VM '.$vmId.' pada NAS.'];
            }

            return ['status' => 'ok', 'value' => 'present',
                'message' => 'Backup NAS VM '.$vmId.' ditemukan: '.$newestName
                    .' (terakhir diubah '.date('Y-m-d H:i:s', $newest).' UTC). '
                    .'PASS berarti berkas backup tersedia, bukan bukti restore berhasil.'];
        } catch (Throwable $exception) {
            report($exception);
            return $this->unavailable('Bukti backup NAS gagal dibaca.');
        }
    }

    private function unavailable(string $message): array
    {
        return ['status' => 'unavailable', 'value' => null, 'message' => $message];
    }
}
