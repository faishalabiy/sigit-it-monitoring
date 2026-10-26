<?php

namespace Tests\Feature;

use App\Models\PmRun;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use App\Services\PmMainDashboardService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitMainDashboardPmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('iams.admin_user_ids', '');
        Carbon::setTestNow('2026-10-23 09:00:00');
        CarbonImmutable::setTestNow('2026-10-23 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'Dashboard '.uniqid(),
            'slug' => 'dashboard-'.uniqid(),
            'permissions' => $permissions,
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    private function system(string $code): PmSystem
    {
        return PmSystem::create([
            'code' => $code, 'name' => $code,
            'type' => 'application', 'environment' => 'production',
            'criticality' => 'high', 'status' => 'active',
        ]);
    }

    private function completedRun(PmSystem $system): PmRun
    {
        $run = PmRun::create([
            'schedule_code' => 'PM-WEEKLY', 'schedule_name' => 'PM All Systems',
            'trigger' => 'manual', 'status' => 'needs_review',
            'review_status' => 'finalized',
            'started_at' => '2026-10-23 01:00:00',
            'completed_at' => '2026-10-23 01:25:00',
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
                'method' => $code==='BACKUP_AGE_WEEKLY' ? 'backup_age' : 'manual_check',
                'status' => $status, 'checked_at' => '2026-10-23 01:05:00',
            ]);
            if ($status === 'unavailable') {
                $run->correctives()->create([
                    'pm_run_check_id' => $check->id, 'status' => 'resolved',
                    'priority' => 'medium', 'finding' => 'Backup NAS',
                    'resolution_notes' => 'Tindak lanjut backup dinyatakan selesai',
                ]);
            }
        }
        return $run;
    }

    public function test_home_dashboard_displays_live_pm_summary_without_changing_existing_borrowing_data(): void
    {
        $user = $this->userWith(['pm.view']);
        $paduka = $this->system('PADUKA');
        $this->system('SIPO');
        $run = $this->completedRun($paduka);

        $summary = app(PmMainDashboardService::class)->build();
        $this->assertSame(2, $summary['systems']);
        $this->assertSame(1, $summary['checked']);
        $this->assertSame(50, $summary['coverage_percent']);
        $this->assertSame(1, $summary['handled']);
        $this->assertSame(1, $summary['finalized_month']);
        $this->assertSame(0, $summary['open_correctives']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="pm-summary"', false)
            ->assertSee('Preventive Maintenance')
            ->assertSee('Aplikasi Diperiksa Minggu Ini')
            ->assertSee('Laporan Final Bulan Ini')
            ->assertSee('Corrective Terbuka')
            ->assertSee('PADUKA')
            ->assertSee('SIPO')
            ->assertSee('FINAL · TUNTAS')
            ->assertSee('Closed')
            ->assertSee('Peminjaman Aktif')
            ->assertSee(route('admin.pm.runs.show', $run))
            ->assertSee(route('admin.pm.monthly.index'));
    }

    public function test_pm_panel_is_hidden_without_pm_view_permission(): void
    {
        $user = $this->userWith(['reports.view']);
        $this->completedRun($this->system('PADUKA'));

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('pmSummary', null)
            ->assertDontSee('id="pm-summary"', false)
            ->assertDontSee('PM All Systems')
            ->assertSee('Peminjaman Aktif');
    }

    public function test_infrastructure_server_is_reported_separately_without_polluting_application_metrics(): void
    {
        $user = $this->userWith(['pm.view']);
        $paduka = $this->system('PADUKA');
        $this->completedRun($paduka);
        PmSystem::create([
            'code' => 'SERVER-191', 'name' => 'Server TI 1',
            'type' => 'server', 'environment' => 'production',
            'criticality' => 'high', 'status' => 'active',
        ]);
        $serverRun = PmRun::create([
            'schedule_code' => 'PM-SERVER', 'schedule_name' => 'PM Server',
            'trigger' => 'manual', 'status' => 'needs_review',
            'review_status' => 'finalized',
            'started_at' => '2026-10-23 02:00:00',
            'completed_at' => '2026-10-23 02:10:00',
        ]);
        foreach (['MANUAL_PATCH_REVIEW'=>'pass', 'WEB_SSL_EXPIRY'=>'unavailable'] as $code => $status) {
            $check = $serverRun->checks()->create([
                'system_code'=>'SERVER-191', 'system_name'=>'Server TI 1',
                'parameter_code'=>$code, 'parameter_name'=>$code,
                'method'=>'manual_check', 'status'=>$status,
                'checked_at'=>'2026-10-23 02:01:00',
            ]);
            if ($status !== 'pass') {
                $serverRun->correctives()->create([
                    'pm_run_check_id'=>$check->id,
                    'status'=>'resolved','priority'=>'medium',
                    'finding'=>'SSL belum dikonfigurasi',
                    'resolution_notes'=>'Telah ditindaklanjuti',
                ]);
            }
        }

        $summary = app(PmMainDashboardService::class)->build();
        $this->assertSame(1, $summary['systems']);
        $this->assertSame(1, $summary['infrastructure_count']);
        $this->assertSame(1, $summary['infrastructure_checked']);
        $this->assertSame('final_resolved', $summary['infrastructure']->first()['status']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Monitoring Infrastruktur Server')
            ->assertSee('Server TI 1')
            ->assertSee(route('admin.pm.runs.show', $serverRun));
    }

    public function test_empty_pm_systems_do_not_inflate_coverage_or_show_green_success(): void
    {
        $user = $this->userWith(['pm.view']);
        $summary = app(PmMainDashboardService::class)->build();
        $this->assertSame(0, $summary['systems']);
        $this->assertSame(0, $summary['coverage_percent']);
        $this->assertSame(0, $summary['handled']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada sistem aplikasi aktif untuk dimonitor.');
    }

    public function test_unresolved_corrective_stays_open_and_week_without_runs_is_not_treated_as_pass(): void
    {
        $system = $this->system('PADUKA');
        $run = $this->completedRun($system);
        $run->correctives()->update(['status' => 'open']);

        $summary = app(PmMainDashboardService::class)->build();
        $this->assertSame(1, $summary['checked']);
        $this->assertSame(1, $summary['open_correctives']);
        $this->assertSame(0, $summary['handled']);
        $this->assertSame('final_exception', $summary['rows']->first()['display_status']);

        $otherMonth = app(PmMainDashboardService::class)
            ->build(CarbonImmutable::parse('2026-11-09 09:00:00', 'Asia/Jakarta'));
        $this->assertSame(0, $otherMonth['checked']);
        $this->assertSame(0, $otherMonth['coverage_percent']);
        $this->assertSame(0, $otherMonth['handled']);
    }
}
