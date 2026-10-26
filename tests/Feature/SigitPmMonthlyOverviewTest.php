<?php

namespace Tests\Feature;

use App\Models\PmRun;
use App\Models\PmSystem;
use App\Models\User;
use App\Services\PmMonthlyOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmMonthlyOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\CarbonImmutable::setTestNow('2026-10-23 09:00:00');
    }

    protected function tearDown(): void
    {
        \Carbon\CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function seedRun(): PmRun
    {
        $system = PmSystem::create([
            'code'=>'PADUKA','name'=>'PADUKA','type'=>'application',
            'environment'=>'production','criticality'=>'high','status'=>'active',
        ]);
        $run=PmRun::create([
            'schedule_code'=>'PM-WEEKLY','schedule_name'=>'PM Weekly',
            'trigger'=>'manual','status'=>'needs_review',
            'review_status'=>'finalized','started_at'=>'2026-10-22 01:00:00',
            'completed_at'=>'2026-10-22 01:30:00',
        ]);
        foreach ([
            'MANUAL_APP_ERROR_LOG'=>'pass',
            'BACKUP_AGE_WEEKLY'=>'unavailable',
            'MANUAL_PATCH_REVIEW'=>'pass',
            'DB_CONNECTION'=>'pass',
            'WEB_SSL_EXPIRY'=>'pass',
        ] as $code=>$status) {
            $check=$run->checks()->create([
                'pm_system_id'=>$system->id,
                'system_code'=>'PADUKA','system_name'=>'PADUKA',
                'parameter_code'=>$code,'parameter_name'=>$code,
                'method'=>'manual_check','status'=>$status,
                'checked_at'=>'2026-10-22 01:05:00',
            ]);
            if ($status==='unavailable') {
                $run->correctives()->create([
                    'pm_run_check_id'=>$check->id,'status'=>'resolved',
                    'priority'=>'medium','finding'=>'Bukti backup NAS',
                    'resolution_notes'=>'Selesai ditindaklanjuti',
                ]);
            }
        }
        return $run;
    }

    public function test_monthly_matrix_uses_closed_correctives_and_future_week_without_marking_future_overdue(): void
    {
        $run=$this->seedRun();
        $result=app(PmMonthlyOverviewService::class)->build(2026,10);
        $row=$result['rows']->first();
        $this->assertSame(5,count($result['weeks']));
        $this->assertSame('final_resolved',$row['weeks'][4]['status']);
        $this->assertSame($run->id,$row['weeks'][4]['run']->id);
        $this->assertSame('future',$row['weeks'][5]['status']);
        $this->assertSame(1,$row['completed']);
        $this->assertSame(4,$row['due']);
        $this->assertSame('in_progress',$row['monthly_status']);
        $this->assertSame(1,$result['stats']['systems']);
    }

    public function test_monthly_details_page_and_dashboard_render_for_pm_authorized_user(): void
    {
        config()->set('iams.admin_user_ids','1');
        $user=User::factory()->create(['id'=>1]);
        $this->seedRun();
        $this->actingAs($user)
            ->get(route('admin.pm.monthly.index',['year'=>2026,'month'=>10]))
            ->assertOk()
            ->assertSee('Laporan Detail Preventive Maintenance Bulanan')
            ->assertSee('PADUKA')
            ->assertSee('Minggu 4')
            ->assertSee('Detail Pelaksanaan Bulanan')
            ->assertSee('Final')
            ->assertSee('data-sidebar-module="pm"',false);

        $this->get(route('admin.pm.index',['year'=>2026,'month'=>10,'week'=>4]))
            ->assertOk()
            ->assertSee('Overview PM Bulanan')
            ->assertSee('Final · Tuntas')
            ->assertSee('data-sidebar-module="assets"',false);
    }

    public function test_multiple_runs_same_week_keep_latest_matrix_but_all_runs_in_details(): void
    {
        config()->set('iams.admin_user_ids','1');
        $user=User::factory()->create(['id'=>1]);
        $first=$this->seedRun();
        $latest=PmRun::create([
            'schedule_code'=>'PM-WEEKLY','schedule_name'=>'PM Weekly',
            'trigger'=>'manual','status'=>'completed','review_status'=>'finalized',
            'started_at'=>'2026-10-23 01:00:00','completed_at'=>'2026-10-23 01:30:00',
        ]);
        foreach ([
            'MANUAL_APP_ERROR_LOG','BACKUP_AGE_WEEKLY','MANUAL_PATCH_REVIEW',
            'DB_CONNECTION','WEB_SSL_EXPIRY',
        ] as $code) {
            $latest->checks()->create([
                'pm_system_id'=>$first->checks()->first()->pm_system_id,
                'system_code'=>'PADUKA','system_name'=>'PADUKA',
                'parameter_code'=>$code,'parameter_name'=>$code,
                'method'=>'manual_check','status'=>'pass',
                'checked_at'=>'2026-10-23 01:05:00',
            ]);
        }

        $overview=app(PmMonthlyOverviewService::class)->build(2026,10);
        $this->assertSame($latest->id,$overview['rows']->first()['weeks'][4]['run']->id);
        $this->assertSame('final_ok',$overview['rows']->first()['weeks'][4]['status']);
        $this->actingAs($user)->get(route('admin.pm.monthly.index',['year'=>2026,'month'=>10]))
            ->assertOk()->assertSee('2 pelaksanaan dalam periode ini')
            ->assertSee('Run #'.$first->id)
            ->assertSee('Run #'.$latest->id);
    }

    public function test_monthly_page_rejects_invalid_month(): void
    {
        config()->set('iams.admin_user_ids','1');
        $user=User::factory()->create(['id'=>1]);
        $this->actingAs($user)->get(route('admin.pm.monthly.index',['year'=>2026,'month'=>13]))
            ->assertSessionHasErrors('month');
    }
}
