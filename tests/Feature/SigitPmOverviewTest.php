<?php

namespace Tests\Feature;

use App\Models\PmRun;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\PmParameterTemplate;
use App\Services\PmMonitoringOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_matrix_uses_real_latest_run_and_does_not_label_missing_checks_ok(): void
    {
        $system = PmSystem::create([
            'code'=>'T001','name'=>'Test App','type'=>'application',
            'environment'=>'production','criticality'=>'high','status'=>'active',
        ]);
        $schedule = PmSchedule::create([
            'code'=>'WEEKLY','name'=>'Weekly','frequency'=>'weekly',
            'run_time'=>'08:00','timezone'=>'Asia/Jakarta','starts_on'=>'2026-10-01','status'=>'ready',
        ]);
        $run = PmRun::create([
            'pm_schedule_id'=>$schedule->id,'schedule_code'=>'WEEKLY','schedule_name'=>'Weekly',
            'trigger'=>'manual','status'=>'needs_review','started_at'=>'2026-10-22 01:00:00',
            'completed_at'=>'2026-10-22 01:05:00',
        ]);
        $run->checks()->create([
            'pm_system_id'=>$system->id,'system_code'=>'T001','system_name'=>'Test App',
            'parameter_code'=>'DB_CONNECTION','parameter_name'=>'DB','method'=>'database_connection',
            'status'=>'pass','checked_at'=>'2026-10-22 01:01:00',
        ]);
        $overview = app(PmMonitoringOverviewService::class)->build(2026,10,4);
        $row = $overview['rows']->first();
        $this->assertSame('pass',$row['statuses']['database']);
        $this->assertNull($row['statuses']['ssl']);
        $this->assertSame('incomplete',$row['overall']);
        $this->assertSame(1,$overview['stats']['checked']);
        $this->assertSame(0,$overview['stats']['ok']);
    }


    public function test_finalized_report_with_unavailable_is_finished_but_preserves_technical_exception(): void
    {
        $system = PmSystem::create([
            'code' => 'PADUKA', 'name' => 'PADUKA', 'type' => 'application',
            'environment' => 'production', 'criticality' => 'high', 'status' => 'active',
        ]);
        $run = PmRun::create([
            'schedule_code' => 'PM-WEEKLY', 'schedule_name' => 'Monitoring',
            'trigger' => 'manual', 'status' => 'needs_review', 'review_status' => 'finalized',
            'started_at' => '2026-10-23 01:00:00', 'completed_at' => '2026-10-23 02:00:00',
            'finalized_at' => '2026-10-23 02:30:00',
        ]);
        foreach ([
            'DB_CONNECTION' => 'pass',
            'WEB_SSL_EXPIRY' => 'pass',
            'MANUAL_APP_ERROR_LOG' => 'pass',
            'MANUAL_PATCH_REVIEW' => 'pass',
            'BACKUP_AGE_WEEKLY' => 'unavailable',
        ] as $code => $status) {
            $run->checks()->create([
                'pm_system_id' => $system->id,
                'system_code' => $system->code,
                'system_name' => $system->name,
                'parameter_code' => $code,
                'parameter_name' => $code,
                'method' => 'manual_check',
                'status' => $status,
                'checked_at' => '2026-10-23 01:30:00',
            ]);
        }
        $overview = app(PmMonitoringOverviewService::class)->build(2026, 10, 4);
        $row = $overview['rows']->first();
        $this->assertSame('incomplete', $row['overall']);
        $this->assertSame('final_exception', $row['display_status']);
        $this->assertSame('unavailable', $row['statuses']['backup']);
        $this->assertSame(1, $overview['stats']['finalized']);
        $this->assertSame(1, $overview['stats']['exceptions']);
        $this->assertSame(0, $overview['stats']['incomplete']);
        $this->assertSame(0, $overview['stats']['ok']);
    }

    public function test_finalized_report_with_closed_corrective_is_operationally_complete(): void
    {
        $system = PmSystem::create([
            'code' => 'PADUKA', 'name' => 'PADUKA', 'type' => 'application',
            'environment' => 'production', 'criticality' => 'high', 'status' => 'active',
        ]);
        $run = PmRun::create([
            'schedule_code' => 'PM-1', 'schedule_name' => 'PM Weekly',
            'trigger' => 'manual', 'status' => 'needs_review', 'review_status' => 'finalized',
            'started_at' => '2026-10-23 01:00:00', 'completed_at' => '2026-10-23 01:02:00',
            'finalized_at' => '2026-10-23 02:00:00',
        ]);
        foreach ([
            'MANUAL_APP_ERROR_LOG' => 'pass',
            'BACKUP_AGE_WEEKLY' => 'unavailable',
            'MANUAL_PATCH_REVIEW' => 'pass',
            'DB_CONNECTION' => 'pass',
            'WEB_SSL_EXPIRY' => 'pass',
        ] as $code => $status) {
            $check = $run->checks()->create([
                'pm_system_id' => $system->id,
                'system_code' => $system->code, 'system_name' => $system->name,
                'parameter_code' => $code, 'parameter_name' => $code,
                'method' => 'manual_check', 'status' => $status,
                'checked_at' => '2026-10-23 01:01:00',
            ]);
            if ($status !== 'pass') {
                $corrective = $run->correctives()->create([
                    'pm_run_check_id' => $check->id,
                    'status' => 'resolved', 'priority' => 'medium',
                    'finding' => 'Backup belum terhubung',
                    'resolution_notes' => 'Bukti backup telah ditindaklanjuti',
                ]);
            }
        }
        $service = app(PmMonitoringOverviewService::class);
        $overview = $service->build(2026, 10, 4);
        $row = $overview['rows']->first();

        $this->assertSame('incomplete', $row['overall']);
        $this->assertSame('unavailable', $row['statuses']['backup']);
        $this->assertTrue($row['resolved_flags']['backup']);
        $this->assertSame('final_resolved', $row['display_status']);
        $this->assertSame(1, $overview['stats']['finalized']);
        $this->assertSame(1, $overview['stats']['resolved']);
        $this->assertSame(0, $overview['stats']['exceptions']);
        $this->assertSame(0, $overview['stats']['ok']);

        // Reopening an action must immediately restore the exception on the overview.
        $corrective->update(['status' => 'open']);
        $reopened = $service->build(2026, 10, 4);
        $this->assertSame('final_exception', $reopened['rows']->first()['display_status']);
        $this->assertSame(0, $reopened['stats']['resolved']);
        $this->assertSame(1, $reopened['stats']['exceptions']);
    }

    public function test_finalized_fail_is_not_mislabeled_as_clean_report(): void
    {
        $system = PmSystem::create([
            'code'=>'SIPO', 'name'=>'SIPO', 'type'=>'application',
            'environment'=>'production', 'criticality'=>'high', 'status'=>'active',
        ]);
        $run = PmRun::create([
            'schedule_code'=>'PM-WEEKLY', 'schedule_name'=>'Monitoring',
            'trigger'=>'manual', 'status'=>'needs_review', 'review_status'=>'finalized',
            'started_at'=>'2026-10-23 01:00:00', 'completed_at'=>'2026-10-23 02:00:00',
        ]);
        $run->checks()->create([
            'pm_system_id'=>$system->id, 'system_code'=>'SIPO', 'system_name'=>'SIPO',
            'parameter_code'=>'DB_CONNECTION', 'parameter_name'=>'Database',
            'method'=>'database_connection', 'status'=>'fail',
            'checked_at'=>'2026-10-23 01:30:00',
        ]);
        $overview=app(PmMonitoringOverviewService::class)->build(2026,10,4);
        $this->assertSame('final_finding',$overview['rows']->first()['display_status']);
        $this->assertSame(1,$overview['stats']['finding']);
        $this->assertSame(0,$overview['stats']['ok']);
    }

    public function test_week_ranges_stay_within_selected_month(): void
    {
        $service = app(PmMonitoringOverviewService::class);
        $this->assertSame(5, $service->weeksInMonth(2026,9));
        $range = $service->build(2026,9,1);
        $this->assertSame('2026-09-01',$range['start']->toDateString());
        $this->assertSame('2026-09-06',$range['end']->toDateString());
    }
}
