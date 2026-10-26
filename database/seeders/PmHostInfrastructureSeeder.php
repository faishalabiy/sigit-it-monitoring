<?php

namespace Database\Seeders;

use App\Models\PmParameterTemplate;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use Illuminate\Database\Seeder;

class PmHostInfrastructureSeeder extends Seeder
{
    public static function templates(): array
    {
        return [
            ['code'=>'HOST_APACHE_STATUS','name'=>'Service Apache Server',
                'method'=>'systemd_unit','expected_value'=>'active'],
            ['code'=>'HOST_MYSQL_STATUS','name'=>'Service MySQL Server',
                'method'=>'systemd_unit','expected_value'=>'active'],
            ['code'=>'HOST_PHP83_STATUS','name'=>'Service PHP-FPM 8.3',
                'method'=>'systemd_unit','expected_value'=>'active'],
            ['code'=>'HOST_PHP84_STATUS','name'=>'Service PHP-FPM 8.4',
                'method'=>'systemd_unit','expected_value'=>'active'],
            ['code'=>'HOST_CRON_STATUS','name'=>'Service Cron Server',
                'method'=>'systemd_unit','expected_value'=>'active'],
            ['code'=>'HOST_SIGIT_SCHEDULER','name'=>'Heartbeat Laravel Scheduler SIGIT',
                'method'=>'scheduler_heartbeat','unit'=>'menit',
                'warning_max'=>3,'failure_max'=>10],
            ['code'=>'HOST_SIPO_QUEUE','name'=>'Queue Worker SIPO',
                'method'=>'queue_worker','expected_value'=>'running'],
        ];
    }

    public function run(): void
    {
        foreach (self::templates() as $data) {
            PmParameterTemplate::firstOrCreate(['code'=>$data['code']],[
                'category'=>'operational','is_active'=>true,
                'description'=>'Pemeriksaan read-only tingkat server bersama 192.168.13.191. '
                    .'Status service bukan bukti fungsi aplikasi; tinjau juga pemeriksaan aplikasi.',
            ]+$data);
        }

        $system=PmSystem::firstOrCreate(
            ['code'=>'SYS-SERVER-191'],
            ['name'=>'Server Aplikasi Bersama 192.168.13.191',
                'type'=>'server','environment'=>'production','criticality'=>'high',
                'status'=>'active','host'=>'192.168.13.191',
                'description'=>'Server Linux yang menjalankan SIGIT, SIPO dan PADUKA. '
                    .'Pemeriksaan service dan heartbeat; tidak mengubah konfigurasi layanan.']
        );
        $ids=PmParameterTemplate::whereIn('code',array_column(self::templates(),'code'))->pluck('id');
        $system->parameters()->syncWithoutDetaching($ids->mapWithKeys(
            fn ($id)=>[$id=>['is_required'=>true]]
        )->all());

        // Existing ready weekly plan already targets both applications on this exact host.
        // Extend its scope with one distinct host infrastructure target.
        $schedule=PmSchedule::where('code','PM-MINGGUAN-SYS001')
            ->where('status','ready')->first();
        if ($schedule && $schedule->systems()->where('host','192.168.13.191')->exists()) {
            $schedule->systems()->syncWithoutDetaching([$system->id]);
        }

        $this->command?->info('7 template pemeriksaan server siap; master server terdaftar.');
    }
}