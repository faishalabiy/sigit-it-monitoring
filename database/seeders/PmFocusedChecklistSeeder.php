<?php

namespace Database\Seeders;

use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PmFocusedChecklistSeeder extends Seeder
{
    // Preserve prior measurements, reports, and template rows. Disable rather than delete.
    public const KEEP = [
        'MANUAL_APP_ERROR_LOG',
        'BACKUP_AGE_WEEKLY',
        'BACKUP_AGE_DAILY',
        'PROXMOX_NAS_BACKUP',
        'MANUAL_PATCH_REVIEW',
        'DB_CONNECTION',
        'WEB_SSL_EXPIRY',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (PmInformationSystemParametersSeeder::templates() as $data) {
                if (!in_array($data['code'], self::KEEP, true)) continue;
                PmParameterTemplate::firstOrCreate(['code' => $data['code']], ['is_active' => true] + $data);
            }

            PmParameterTemplate::whereNotIn('code', self::KEEP)->update(['is_active' => false]);
            PmParameterTemplate::whereIn('code', self::KEEP)->update(['is_active' => true]);

            $keepIds = PmParameterTemplate::whereIn('code', self::KEEP)->pluck('id')->all();
            // All application targets share the five-column checklist. Existing historical
            // checks remain unchanged. Only weekly backup is attached by default.
            $backupId = PmParameterTemplate::where('code', 'BACKUP_AGE_WEEKLY')->value('id');
            foreach (PmSystem::where('type', 'application')->get() as $system) {
                $system->parameters()->detach(
                    $system->parameters()->whereNotIn('pm_parameter_templates.id', $keepIds)
                        ->pluck('pm_parameter_templates.id')->all()
                );
                $system->parameters()->syncWithoutDetaching(
                    collect(['MANUAL_APP_ERROR_LOG', 'MANUAL_PATCH_REVIEW', 'DB_CONNECTION', 'WEB_SSL_EXPIRY'])
                        ->mapWithKeys(fn ($code) => [
                            PmParameterTemplate::where('code', $code)->value('id') => ['is_required' => true],
                        ])->all()
                );
                if ($backupId && !$system->parameters()->whereIn('code', ['BACKUP_AGE_DAILY','BACKUP_AGE_WEEKLY','PROXMOX_NAS_BACKUP'])->exists()) {
                    $system->parameters()->attach($backupId, ['is_required' => true]);
                }
            }
        });
    }
}
