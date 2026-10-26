<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Services\PmProbeService;
use Database\Seeders\PmHostInfrastructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SigitPmInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('pm.local_hosts',['192.168.13.191']);
    }

    private function system(string $type='server',string $host='192.168.13.191'): PmSystem
    {
        return new PmSystem(['type'=>$type,'host'=>$host,
            'target_url'=>'https://sipo.ptrekaindo.co.id']);
    }

    private function parameter(string $code,string $method,array $more=[]): PmParameterTemplate
    {
        return new PmParameterTemplate(array_replace([
            'code'=>$code,'name'=>$code,'method'=>$method,
            'expected_value'=>'active','is_active'=>true,
        ],$more));
    }

    public function test_service_checks_are_read_only_scoped_and_unknown_units_are_unavailable(): void
    {
        $probe=app(PmProbeService::class);
        $valid=$this->parameter('HOST_APACHE_STATUS','systemd_unit');
        $this->assertSame('unavailable',$probe->check($this->system('application'),$valid)['status']);
        $this->assertSame('unavailable',$probe->check($this->system('server','192.168.13.192'),$valid)['status']);
        $unknown=$this->parameter('ARBITRARY_ROOT_CMD','systemd_unit');
        $this->assertSame('unavailable',$probe->check($this->system(),$unknown)['status']);
    }

    public function test_scheduler_heartbeat_is_based_on_actual_last_tick(): void
    {
        $probe=app(PmProbeService::class);
        $parameter=$this->parameter('HOST_SIGIT_SCHEDULER','scheduler_heartbeat',[
            'expected_value'=>null,'warning_max'=>3,'failure_max'=>10,'unit'=>'menit',
        ]);
        Cache::forget('pm:sigit:scheduler-last-tick');
        $this->assertSame('unavailable',$probe->check($this->system(),$parameter)['status']);
        Cache::put('pm:sigit:scheduler-last-tick',time()-60,now()->addMinutes(30));
        $this->assertSame('pass',$probe->check($this->system(),$parameter)['status']);
        Cache::put('pm:sigit:scheduler-last-tick',time()-360,now()->addMinutes(30));
        $this->assertSame('warning',$probe->check($this->system(),$parameter)['status']);
        Cache::put('pm:sigit:scheduler-last-tick',time()-660,now()->addMinutes(30));
        $this->assertSame('fail',$probe->check($this->system(),$parameter)['status']);
    }

    public function test_backup_requires_explicit_directory_and_uses_actual_artifact_mtime(): void
    {
        $probe=app(PmProbeService::class);
        $parameter=$this->parameter('BACKUP_AGE_WEEKLY','backup_age',[
            'expected_value'=>null,'warning_max'=>8,'failure_max'=>15,'unit'=>'hari',
        ]);
        config()->set('pm.backup_directories',[]);
        $this->assertSame('unavailable',$probe->check($this->system('application'),$parameter)['status']);

        $dir=storage_path('framework/testing/pm-backup-test-'.getmypid());
        if (! is_dir($dir)) mkdir($dir,0700,true);
        $file=$dir.'/sample.sql.gz';
        try {
            file_put_contents($file,'test sample backup bytes');
            config()->set('pm.backup_directories',['sipo.ptrekaindo.co.id'=>$dir]);
            touch($file,time()-9*86400);
            $this->assertSame('warning',$probe->check($this->system('application'),$parameter)['status']);
            touch($file,time()-17*86400);
            clearstatcache(true,$file);
            $this->assertSame('fail',$probe->check($this->system('application'),$parameter)['status']);
            touch($file,time()-86400);
            clearstatcache(true,$file);
            $result=$probe->check($this->system('application'),$parameter);
            $this->assertSame('pass',$result['status']);
            $this->assertStringContainsString('tidak menjamin', $result['message']);
        } finally {
            @unlink($file);@rmdir($dir);
        }
    }

    public function test_host_seed_is_idempotent_and_preserves_existing_schedule_targets(): void
    {
        $a=PmSystem::create(['code'=>'A','name'=>'PADUKA','type'=>'application','host'=>'192.168.13.191']);
        $b=PmSystem::create(['code'=>'B','name'=>'SIPO','type'=>'application','host'=>'192.168.13.191']);
        $schedule=PmSchedule::create(['code'=>'PM-MINGGUAN-SYS001','name'=>'PM Weekly',
            'frequency'=>'weekly','run_time'=>'08:00','starts_on'=>now()->toDateString(),
            'timezone'=>'Asia/Jakarta','status'=>'ready']);
        $schedule->systems()->attach([$a->id,$b->id]);

        $this->seed(PmHostInfrastructureSeeder::class);
        $this->seed(PmHostInfrastructureSeeder::class);
        $this->assertDatabaseCount('pm_parameter_templates',7);
        $this->assertDatabaseCount('pm_systems',3);
        $this->assertSame(3,$schedule->systems()->count());
        $this->assertSame(7,PmSystem::where('code','SYS-SERVER-191')->firstOrFail()->parameters()->count());
    }
}
