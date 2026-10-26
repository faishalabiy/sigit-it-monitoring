<?php

namespace Tests\Feature;

use App\Models\PmRun;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('iams.admin_user_ids', '');
        \Carbon\Carbon::setTestNow('2026-10-23 09:30:00');
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'PM Report '.uniqid(),
            'slug' => 'pm-report-'.uniqid(),
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

    private function makeReport(PmSystem $system, string $name, string $time, string $review, ?string $corrective = null): PmRun
    {
        $run = PmRun::create([
            'schedule_code' => 'PM-'.strtoupper(substr($name, 0, 4)),
            'schedule_name' => $name,
            'status' => 'needs_review', 'review_status' => $review,
            'trigger' => 'manual', 'started_at' => $time, 'completed_at' => $time,
        ]);
        $check = $run->checks()->create([
            'pm_system_id' => $system->id,
            'system_code' => $system->code, 'system_name' => $system->name,
            'parameter_code' => 'DB_CONNECTION', 'parameter_name' => 'Database',
            'method' => 'database_connection', 'status' => $corrective ? 'unavailable' : 'pass',
            'checked_at' => $time,
        ]);
        if ($corrective) {
            $run->correctives()->create([
                'pm_run_check_id' => $check->id, 'status' => $corrective,
                'priority' => 'medium', 'finding' => 'Database belum tersedia',
                'resolution_notes' => $corrective === 'resolved' ? 'Selesai' : null,
            ]);
        }

        return $run;
    }

    public function test_sidebar_report_group_is_visible_for_pm_viewer_without_borrowing_report_access(): void
    {
        $viewer = $this->userWith(['pm.view']);
        $response = $this->actingAs($viewer)->get(route('admin.reports.pm.index'));

        $response->assertOk()
            ->assertSee('Pengelolaan Laporan Preventive Maintenance')
            ->assertSee('Laporan Preventive Maintenance')
            ->assertSee('data-sidebar-module="reports"', false)
            ->assertDontSee('Laporan Aset &amp; Peminjaman');
        $this->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_borrowing_report_viewer_cannot_access_pm_reports_or_see_pm_link(): void
    {
        $viewer = $this->userWith(['reports.view']);
        $this->actingAs($viewer)
            ->get(route('admin.reports.pm.index'))->assertForbidden();
        // The borrower report itself references Borrowing and Asset tables,
        // which are not required for the PM permission boundary.
        $response = $this->get(route('admin.dashboard'));
        $response->assertOk()->assertSee('Laporan Aset & Peminjaman', false)
            ->assertDontSee('Laporan Preventive Maintenance');
    }

    public function test_filters_show_only_matching_runs_and_final_only_download_actions(): void
    {
        $viewer = $this->userWith(['pm.view']);
        $paduka = $this->system('PADUKA');
        $sipo = $this->system('SIPO');
        $final = $this->makeReport($paduka, 'PM PADUKA FINAL', '2026-10-23 01:00:00', 'finalized', 'resolved');
        $this->makeReport($paduka, 'PM PADUKA DRAFT', '2026-10-23 02:00:00', 'draft', 'open');
        $this->makeReport($sipo, 'PM SIPO FINAL', '2026-10-23 03:00:00', 'finalized');

        $this->actingAs($viewer)->get(route('admin.reports.pm.index', [
            'date_from' => '2026-10-23', 'date_to' => '2026-10-23',
            'system' => $paduka->id, 'review_status' => 'finalized', 'corrective' => 'resolved',
        ]))->assertOk()
            ->assertSee('PM PADUKA FINAL')
            ->assertDontSee('PM PADUKA DRAFT')
            ->assertDontSee('PM SIPO FINAL')
            ->assertSee(route('admin.pm.runs.pdf', $final))
            ->assertSee(route('admin.pm.runs.excel', $final))
            ->assertSee('1/1 Closed')
            ->assertSee('1 laporan ditemukan');

        $this->get(route('admin.reports.pm.index', [
            'date_from' => '2026-10-23', 'date_to' => '2026-10-23', 'corrective' => 'open',
        ]))->assertOk()->assertSee('PM PADUKA DRAFT')
            ->assertDontSee('PM PADUKA FINAL');
    }

    public function test_wib_date_filter_and_invalid_range_validation(): void
    {
        $viewer = $this->userWith(['pm.view']);
        $system = $this->system('PADUKA');
        $this->makeReport($system, 'PM TANGGAL 23', '2026-10-22 17:30:00', 'draft');
        $this->makeReport($system, 'PM TANGGAL 22', '2026-10-22 16:30:00', 'draft');
        $this->actingAs($viewer)->get(route('admin.reports.pm.index', [
            'date_from' => '2026-10-23', 'date_to' => '2026-10-23',
        ]))->assertOk()->assertSee('PM TANGGAL 23')->assertDontSee('PM TANGGAL 22');

        $this->get(route('admin.reports.pm.index', [
            'date_from' => '2026-10-24', 'date_to' => '2026-10-23',
        ]))->assertSessionHasErrors('date_to');
    }
}
