<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use App\Models\PmRun;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use App\Services\PmExecutionService;
use App\Services\PmProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SigitPmExecutionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPm(bool $manage): User
    {
        config()->set('iams.admin_user_ids','');
        $user=User::factory()->create();
        $role=Role::create([
            'name'=>'PM Role','slug'=>'pm-tester',
            'permissions'=>$manage?['pm.view','pm.manage']:['pm.view'],
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    private function readySchedule(): PmSchedule
    {
        $system=PmSystem::create([
            'code'=>'SYS-001','name'=>'SIGIT','type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
            'target_url'=>'https://sigit.ptrekaindo.co.id',
        ]);
        $http=PmParameterTemplate::create([
            'code'=>'WEB_HTTP_STATUS','name'=>'Status HTTP','method'=>'http_status',
            'category'=>'availability','expected_value'=>'200','is_active'=>true,
        ]);
        $manual=PmParameterTemplate::create([
            'code'=>'MANUAL_LOGIN','name'=>'Uji Login','method'=>'manual_check',
            'category'=>'operational','expected_value'=>'PASS','is_active'=>true,
        ]);
        $system->parameters()->attach([$http->id,$manual->id]);

        $schedule=PmSchedule::create([
            'code'=>'PM001','name'=>'PM Uji Coba','frequency'=>'daily',
            'run_time'=>'08:00','starts_on'=>now()->subDay()->toDateString(),
            'timezone'=>'Asia/Jakarta','status'=>'ready',
        ]);
        $schedule->systems()->attach($system);
        return $schedule;
    }

    public function test_manual_run_saves_real_http_outcome_and_pending_manual_item(): void
    {
        $user=$this->userWithPm(true);
        $schedule=$this->readySchedule();

        Http::fake(['sigit.ptrekaindo.co.id/*'=>Http::response('healthy',200)]);
        $response=$this->actingAs($user)->post(route('admin.pm.schedules.run',$schedule));
        $run=PmRun::firstOrFail();
        $response->assertRedirect(route('admin.pm.runs.show',$run));

        $this->assertSame('manual',$run->trigger);
        $this->assertSame('needs_review',$run->status);
        $this->assertSame(2,$run->checks()->count());
        $this->assertSame('pass',$run->checks()->where('method','http_status')->firstOrFail()->status);
        $this->assertSame('pending',$run->checks()->where('method','manual_check')->firstOrFail()->status);
        $this->actingAs($user)->get(route('admin.pm.runs.show',$run))
            ->assertOk()->assertSee('Laporan Monitoring PM');
        $this->actingAs($user)->get(route('admin.pm.runs.index'))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_manual_execution_requires_manage_permission_and_ready_schedule(): void
    {
        $viewer=$this->userWithPm(false);
        $schedule=$this->readySchedule();
        $this->actingAs($viewer)->post(route('admin.pm.schedules.run',$schedule))->assertForbidden();
        $this->assertDatabaseCount('pm_runs',0);

        $manager=User::factory()->create();
        $role=Role::where('slug','pm-tester')->firstOrFail();
        $role->update(['permissions'=>['pm.view','pm.manage']]);
        $manager->roles()->attach($role);
        $schedule->update(['status'=>'draft']);
        $this->actingAs($manager)->post(route('admin.pm.schedules.run',$schedule))->assertSessionHasErrors('schedule');
        $this->assertDatabaseCount('pm_runs',0);
    }

    public function test_metrics_are_host_scoped_and_do_not_claim_application_specific_usage(): void
    {
        config()->set('pm.local_hosts',['192.168.13.191']);
        $system=PmSystem::create([
            'code'=>'SYS-HOST','name'=>'PADUKA','type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
            'host'=>'192.168.13.191','target_url'=>'https://paduka.ptrekaindo.co.id',
        ]);
        $probe=app(PmProbeService::class);
        foreach (['cpu_usage','memory_usage','disk_usage'] as $method) {
            $parameter=PmParameterTemplate::create([
                'code'=>strtoupper($method),'name'=>$method,
                'category'=>'capacity','method'=>$method,'unit'=>'%',
                'warning_max'=>80,'failure_max'=>95,'is_active'=>true,
            ]);
            $result=$probe->check($system,$parameter);
            $this->assertContains($result['status'],['pass','warning','fail']);
            $this->assertGreaterThanOrEqual(0,(float)$result['value']);
            $this->assertLessThanOrEqual(100,(float)$result['value']);
            $this->assertStringContainsString('server',strtolower($result['message']));
        }
        $system->update(['host'=>'192.168.13.192']);
        $parameter=PmParameterTemplate::firstOrFail();
        $result=$probe->check($system,$parameter);
        $this->assertSame('unavailable',$result['status']);
    }

    public function test_service_status_uses_authenticated_application_health_endpoint(): void
    {
        $system=PmSystem::create([
            'code'=>'SYS-WEB','name'=>'PADUKA','type'=>'application','status'=>'active',
            'environment'=>'production','criticality'=>'high',
            'target_url'=>'https://paduka.ptrekaindo.co.id',
            'host'=>'192.168.13.191',
        ]);
        config()->set('pm.local_hosts',['192.168.13.191']);
        config()->set('pm.health_clients',['paduka.ptrekaindo.co.id'=>str_repeat('k',64)]);
        $service=PmParameterTemplate::create([
            'code'=>'SERVICE_RUNNING','name'=>'Web Service','method'=>'service_status',
            'category'=>'availability','expected_value'=>'running','is_active'=>true,
        ]);
        Http::fake(['paduka.ptrekaindo.co.id/internal/pm/health'=>Http::response([
            'service'=>'running','database'=>'connected',
        ],200)]);
        $result=app(PmProbeService::class)->check($system,$service);
        $this->assertSame('pass',$result['status']);
        $this->assertSame('running',$result['value']);
        $this->assertStringContainsString('health',strtolower($result['message']));
        Http::assertSent(fn ($request) => $request->hasHeader('X-PM-Signature')
            && $request->url()==='https://paduka.ptrekaindo.co.id/internal/pm/health');
    }

    public function test_own_database_check_does_not_misrepresent_other_application(): void
    {
        config()->set('pm.local_hosts',['192.168.13.191']);
        $system=PmSystem::create([
            'code'=>'SYS-DATABASE','name'=>'SIGIT','type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
            'host'=>'192.168.13.191','target_url'=>config('app.url'),
        ]);
        $parameter=PmParameterTemplate::create([
            'code'=>'DB_CONNECTION','name'=>'DB Connection','method'=>'database_connection',
            'category'=>'availability','expected_value'=>'connected','is_active'=>true,
        ]);
        $result=app(PmProbeService::class)->check($system,$parameter);
        $this->assertSame('pass',$result['status']);
        $this->assertSame('connected',$result['value']);

        $system->update(['target_url'=>'https://paduka.ptrekaindo.co.id']);
        config()->set('pm.health_clients',['paduka.ptrekaindo.co.id'=>str_repeat('j',64)]);
        Http::fake(['paduka.ptrekaindo.co.id/internal/pm/health'=>Http::response([
            'service'=>'running','database'=>'disconnected',
        ],200)]);
        $result=app(PmProbeService::class)->check($system,$parameter);
        $this->assertSame('fail',$result['status']);
        $this->assertSame('disconnected',$result['value']);
        // The SIGIT database is working, but the target application's own
        // endpoint reports its database disconnected; do not infer from SIGIT.

    }

    public function test_probe_blocks_untrusted_and_non_https_targets(): void
    {
        $probe=app(PmProbeService::class);
        $this->assertFalse($probe->isAllowedTarget('http://sigit.ptrekaindo.co.id'));
        $this->assertFalse($probe->isAllowedTarget('https://127.0.0.1/admin'));
        $this->assertFalse($probe->isAllowedTarget('https://ptrekaindo.co.id.attacker.test/'));
        $this->assertFalse($probe->isAllowedTarget('https://sigit.ptrekaindo.co.id@evil.test/'));
        $this->assertFalse($probe->isAllowedTarget('https://sigit.ptrekaindo.co.id/?token=secret'));
        $this->assertTrue($probe->isAllowedTarget('https://sigit.ptrekaindo.co.id/'));
    }

    public function test_cron_only_runs_on_exact_due_minute_and_is_deduplicated(): void
    {
        $schedule=$this->readySchedule();
        $now=\Illuminate\Support\Carbon::now('Asia/Jakarta')->startOfMinute();
        $schedule->update(['frequency'=>'daily','run_time'=>$now->format('H:i')]);
        $probe=Mockery::mock(PmProbeService::class);
        $probe->shouldReceive('check')->andReturn(['status'=>'pass','value'=>'200','message'=>'ok']);
        app()->instance(PmProbeService::class,$probe);
        app()->forgetInstance(PmExecutionService::class);

        $this->artisan('pm:run-due')->assertExitCode(0);
        $this->artisan('pm:run-due')->assertExitCode(0);
        $this->assertDatabaseCount('pm_runs',1);
        $this->assertSame('automatic',PmRun::first()->trigger);
    }

    public function test_manual_checklist_can_be_completed_once_and_recalculates_run_status(): void
    {
        $user=$this->userWithPm(true);
        $schedule=$this->readySchedule();
        Http::fake(['sigit.ptrekaindo.co.id/*'=>Http::response('healthy',200)]);
        $this->actingAs($user)->post(route('admin.pm.schedules.run',$schedule));
        $run=PmRun::firstOrFail();
        $manual=$run->checks()->where('method','manual_check')->firstOrFail();

        $this->actingAs($user)->patch(route('admin.pm.runs.checks.review',[$run,$manual]),[
            'status'=>'pass','notes'=>'Login dan logout menggunakan akun uji berjalan normal.',
        ])->assertRedirect(route('admin.pm.runs.show',$run));

        $this->assertSame('pass',$manual->fresh()->status);
        $this->assertNotNull($manual->fresh()->reviewed_at);
        $this->assertSame('completed',$run->fresh()->status);

        $this->actingAs($user)->patch(route('admin.pm.runs.checks.review',[$run,$manual]),[
            'status'=>'fail','notes'=>'Perubahan kedua tidak diperbolehkan.',
        ])->assertSessionHasErrors('status');
        $this->assertSame('pass',$manual->fresh()->status);
    }

    public function test_schedule_with_history_cannot_be_deleted(): void
    {
        $user=$this->userWithPm(true);
        $schedule=$this->readySchedule();
        Http::fake(['sigit.ptrekaindo.co.id/*'=>Http::response('healthy',200)]);
        $this->actingAs($user)->post(route('admin.pm.schedules.run',$schedule));
        $this->actingAs($user)->delete(route('admin.pm.schedules.destroy',$schedule))
            ->assertSessionHas('error');
        $this->assertNotNull($schedule->fresh());
    }
}
