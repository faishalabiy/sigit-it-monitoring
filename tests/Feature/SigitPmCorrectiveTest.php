<?php

namespace Tests\Feature;

use App\Models\PmCorrectiveAction;
use App\Models\PmParameterTemplate;
use App\Models\PmRun;
use App\Models\PmRunCheck;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use App\Services\PmCorrectiveService;
use App\Services\PmExecutionService;
use App\Services\PmProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SigitPmCorrectiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('iams.admin_user_ids','');
    }

    private function user(array $permissions): User
    {
        $user=User::factory()->create();
        $role=Role::create([
            'name'=>'Role-'.uniqid(),'slug'=>'r-'.uniqid(),'permissions'=>$permissions,
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    private function fixture(string $status='warning',string $method='disk_usage'): array
    {
        $system=PmSystem::create([
            'code'=>'SYS-A','name'=>'Aplikasi A','type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
        ]);
        $parameter=PmParameterTemplate::create([
            'code'=>'METRIC-A','name'=>'Disk Usage','category'=>'capacity',
            'method'=>$method,'unit'=>'%','warning_max'=>80,'failure_max'=>90,
            'is_active'=>true,
        ]);
        $schedule=PmSchedule::create([
            'code'=>'PM-A','name'=>'PM Pengujian','frequency'=>'daily',
            'run_time'=>'08:00','starts_on'=>now()->toDateString(),
            'timezone'=>'Asia/Jakarta','status'=>'ready',
        ]);
        $schedule->systems()->attach($system);
        $system->parameters()->attach($parameter);
        $run=PmRun::create([
            'pm_schedule_id'=>$schedule->id,'schedule_code'=>$schedule->code,
            'schedule_name'=>$schedule->name,'trigger'=>'manual',
            'status'=>'needs_review','started_at'=>now(),'completed_at'=>now(),
            'summary'=>['total'=>1,'systems'=>1,$status=>1],
        ]);
        $check=$run->checks()->create([
            'pm_system_id'=>$system->id,'pm_parameter_template_id'=>$parameter->id,
            'system_code'=>'SYS-A','system_name'=>'Aplikasi A',
            'parameter_code'=>'METRIC-A','parameter_name'=>'Disk Usage',
            'method'=>$method,'status'=>$status,'value'=>'82','unit'=>'%',
            'message'=>'Penggunaan disk meningkat.','checked_at'=>now(),
        ]);
        return compact('system','parameter','schedule','run','check');
    }

    public function test_warning_and_failure_create_traceable_unique_actions_only(): void
    {
        $f=$this->fixture();
        $service=app(PmCorrectiveService::class);
        $this->assertSame(1,$service->createForRun($f['run']));
        $this->assertSame(0,$service->createForRun($f['run']));
        $this->assertDatabaseCount('pm_corrective_actions',1);
        $action=PmCorrectiveAction::firstOrFail();
        $this->assertSame('open',$action->status);
        $this->assertSame('medium',$action->priority);
        $this->assertSame($f['check']->id,$action->pm_run_check_id);
        $this->assertStringContainsString('kapasitas',$action->recommendation);
        $this->assertSame('created',$action->events()->firstOrFail()->event);
        $f['check']->update(['status'=>'fail']);
        $this->assertSame($action->id,$service->createFromCheck($f['check'])->id);
    }

    public function test_pass_pending_and_unavailable_not_automatically_created(): void
    {
        foreach (['pass','pending','unavailable'] as $status) {
            $f=$this->fixture($status);
            app(PmCorrectiveService::class)->createForRun($f['run']);
            $this->assertDatabaseCount('pm_corrective_actions',0);
            $f['check']->delete();
            $f['run']->delete();
            $f['schedule']->delete();
            $f['system']->delete();
            $f['parameter']->delete();
        }
    }

    public function test_unavailable_requires_manager_to_manually_create_action(): void
    {
        $f=$this->fixture('unavailable');
        $viewer=$this->user(['pm.view']);
        $manager=$this->user(['pm.view','pm.manage']);
        $url=route('admin.pm.correctives.from-check',[$f['run'],$f['check']]);
        $this->actingAs($viewer)->post($url)->assertForbidden();
        $this->actingAs($manager)->post($url)->assertRedirect();
        $this->assertDatabaseCount('pm_corrective_actions',1);
        $action=PmCorrectiveAction::firstOrFail();
        $this->assertSame($manager->id,$action->created_by);
        $this->assertSame('medium',$action->priority);
        $this->actingAs($manager)->post($url)->assertRedirect(route('admin.pm.correctives.show',$action));
        $this->assertDatabaseCount('pm_corrective_actions',1);
    }

    public function test_corrective_assignment_resolution_and_history(): void
    {
        $f=$this->fixture('fail');
        $manager=$this->user(['pm.view','pm.manage']);
        $assignee=User::factory()->create();
        $action=app(PmCorrectiveService::class)->createFromCheck($f['check']);
        $url=route('admin.pm.correctives.update',$action);
        $this->actingAs($manager)->patch($url,[
            'status'=>'resolved','priority'=>'high','assigned_to'=>$assignee->id,
            'due_on'=>'2026-11-12','recommendation'=>'Investigasi penyimpanan',
            'resolution_notes'=>'Partisi diperluas setelah approval; ruang disk diverifikasi ulang.',
        ])->assertRedirect(route('admin.pm.correctives.show',$action));
        $action->refresh();
        $this->assertSame('resolved',$action->status);
        $this->assertSame($assignee->id,$action->assigned_to);
        $this->assertSame($manager->id,$action->resolved_by);
        $this->assertNotNull($action->resolved_at);
        $this->assertSame(2,$action->events()->count());
        $this->assertSame('warning'=== $f['check']->status ? 'warning' : 'fail',$f['check']->fresh()->status);
        $this->actingAs($manager)->get(route('admin.pm.correctives.show',$action))->assertOk()
            ->assertSee('Histori Perubahan')->assertSee('Partisi diperluas');
        $this->actingAs($manager)->get(route('admin.pm.runs.show',$f['run']))->assertOk()
            ->assertSee('Ringkasan Kondisi per Sistem')
            ->assertSee('Temuan dan Rekomendasi Tindak Lanjut')
            ->assertSee('Corrective CA-');
    }

    public function test_resolved_action_can_be_reopened_with_audited_transition(): void
    {
        $f=$this->fixture('warning');
        $manager=$this->user(['pm.view','pm.manage']);
        $action=app(PmCorrectiveService::class)->createFromCheck($f['check']);
        $url=route('admin.pm.correctives.update',$action);
        $this->actingAs($manager)->patch($url,[
            'status'=>'resolved','priority'=>'medium',
            'resolution_notes'=>'Pembersihan terkontrol selesai dan ruang disk diverifikasi.',
        ])->assertRedirect();
        $this->actingAs($manager)->patch($url,[
            'status'=>'in_progress','priority'=>'high',
            'recommendation'=>'Lakukan analisis pertumbuhan disk.',
        ])->assertRedirect();
        $action->refresh();
        $this->assertSame('in_progress',$action->status);
        $this->assertNull($action->resolved_at);
        $this->assertNull($action->resolution_notes);
        $this->assertSame('reopened',$action->events()->latest('id')->firstOrFail()->event);
    }

    public function test_final_document_with_historical_findings_displays_distinct_result_and_resolved_corrective(): void
    {
        $fixture=$this->fixture('warning');
        $run=$fixture['run'];
        $run->update(['review_status'=>'finalized']);
        $manager=$this->user(['pm.view','pm.manage']);
        $action=app(PmCorrectiveService::class)->createFromCheck($fixture['check']);
        $action->update(['status'=>'resolved','resolved_at'=>now(),
            'resolution_notes'=>'Tindakan sudah diverifikasi.']);

        $this->actingAs($manager)->get(route('admin.pm.runs.index'))
            ->assertOk()->assertSee('Ada Temuan')
            ->assertSee('FINAL')->assertSee('1/1 selesai')
            ->assertDontSee('Perlu Review');
        $this->actingAs($manager)->get(route('admin.pm.runs.show',$run))
            ->assertOk()->assertSee('Laporan sudah FINAL dan disetujui.')
            ->assertSee('1 dari 1 corrective telah diselesaikan')
            ->assertSee('Ada Temuan');
        $this->assertSame('needs_review',$run->fresh()->status);
        $this->assertSame('finalized',$run->fresh()->review_status);
        $this->assertSame('warning',$fixture['check']->fresh()->status);
    }

    public function test_resolution_requires_evidence_and_is_permission_protected(): void
    {
        $f=$this->fixture('warning');
        $action=app(PmCorrectiveService::class)->createFromCheck($f['check']);
        $viewer=$this->user(['pm.view']);
        $manager=$this->user(['pm.view','pm.manage']);
        $this->actingAs($viewer)->get(route('admin.pm.correctives.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.pm.correctives.show',$action))->assertOk();
        $this->actingAs($viewer)->patch(route('admin.pm.correctives.update',$action),[])->assertForbidden();
        $this->actingAs($manager)->patch(route('admin.pm.correctives.update',$action),[
            'status'=>'resolved','priority'=>'medium',
        ])->assertSessionHasErrors('resolution_notes');
        $this->assertSame('open',$action->fresh()->status);
    }

    public function test_manual_review_warning_creates_corrective(): void
    {
        $f=$this->fixture('pending','manual_check');
        $manager=$this->user(['pm.view','pm.manage']);
        $this->actingAs($manager)->patch(route('admin.pm.runs.checks.review',[$f['run'],$f['check']]),[
            'status'=>'warning','notes'=>'Review akses memerlukan tindak lanjut.',
        ])->assertRedirect(route('admin.pm.runs.show',$f['run']));
        $this->assertSame('warning',$f['check']->fresh()->status);
        $this->assertSame(1,PmCorrectiveAction::count());
        $this->assertSame($manager->id,PmCorrectiveAction::first()->created_by);
    }

    public function test_run_automatically_creates_findings_from_measured_failures(): void
    {
        $f=$this->fixture('pass');
        $f['run']->delete();
        $mock=Mockery::mock(PmProbeService::class);
        $mock->shouldReceive('check')->once()->andReturn([
            'status'=>'warning','value'=>'82','message'=>'Disk hampir penuh',
        ]);
        app()->instance(PmProbeService::class,$mock);
        app()->forgetInstance(PmExecutionService::class);
        $run=app(PmExecutionService::class)->start($f['schedule'],'manual',null);
        $this->assertSame('needs_review',$run->status);
        $this->assertSame(1,$run->correctives()->count());
        $this->assertSame('warning',$run->checks()->first()->status);
    }
}
