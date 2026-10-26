<?php

namespace Database\Seeders;

use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PmProxmoxNasSharedVmSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $parameter = PmParameterTemplate::firstOrCreate(
                ['code' => 'PROXMOX_NAS_BACKUP'],
                [
                    'name' => 'Backup VM Bersama PADUKA dan SIPO di NAS Synology',
                    'category' => 'backup',
                    'method' => 'backup_present',
                    'expected_value' => 'present',
                    'is_active' => true,
                    'description' => 'PASS jika arsip vzdump lengkap, tidak kosong, untuk VM Proxmox bersama PADUKA/SIPO tersedia di folder dump NAS Synology. Tidak membuktikan restore berhasil.',
                ]
            );
            $parameter->update(['is_active' => true]);

            $systems = PmSystem::where('type', 'application')
                ->whereIn('target_url', [
                    'https://paduka.ptrekaindo.co.id',
                    'https://sipo.ptrekaindo.co.id',
                ])->get();

            foreach ($systems as $system) {
                $oldBackupIds = $system->parameters()
                    ->whereIn('code', ['BACKUP_AGE_DAILY', 'BACKUP_AGE_WEEKLY'])
                    ->pluck('pm_parameter_templates.id')->all();
                if ($oldBackupIds) $system->parameters()->detach($oldBackupIds);
                $system->parameters()->syncWithoutDetaching([
                    $parameter->id => ['is_required' => true],
                ]);
            }
            $this->command?->info('Checklist NAS Proxmox diterapkan ke '.$systems->count().' aplikasi (tanpa mengubah riwayat).');
        });
    }
}
