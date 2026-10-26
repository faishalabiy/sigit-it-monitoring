<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPreventiveMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('iams.admin_user_ids', '');
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name'=>'PM Tester '.uniqid(), 'slug'=>'pm-test-'.uniqid(),
            'permissions'=>$permissions,
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    private function parameter(array $override = []): PmParameterTemplate
    {
        return PmParameterTemplate::create(array_merge([
            'code'=>'HTTP_STATUS', 'name'=>'HTTP Status', 'category'=>'availability',
            'method'=>'http_status', 'expected_value'=>'200', 'is_active'=>true,
        ],$override));
    }

    private function system(array $override = []): PmSystem
    {
        return PmSystem::create(array_merge([
            'code'=>'SYS-001','name'=>'SIGIT','type'=>'application',
            'environment'=>'production','criticality'=>'high','status'=>'active',
        ],$override));
    }

    private function scheduleData(array $override=[]): array
    {
        return array_merge([
            'code'=>'PM-001','name'=>'PM Bulanan Sistem',
            'frequency'=>'monthly','run_time'=>'08:30',
            'day_of_month'=>15,'timezone'=>'Asia/Jakarta',
            'starts_on'=>'2026-11-01','status'=>'draft',
        ],$override);
    }

    public function test_all_configuration_forms_render_for_authorized_administrator(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $this->get(route('admin.pm.systems.create'))->assertOk()->assertSee('Identitas Sistem');
        $this->get(route('admin.pm.parameters.create'))->assertOk()->assertSee('Batas Evaluasi Numeric');
        $this->get(route('admin.pm.schedules.create'))->assertOk()->assertSee('Target Pemeriksaan');

        $parameter=$this->parameter();
        $system=$this->system();
        $schedule=PmSchedule::create($this->scheduleData());
        $this->get(route('admin.pm.parameters.edit',$parameter))->assertOk();
        $this->get(route('admin.pm.systems.edit',$system))->assertOk();
        $this->get(route('admin.pm.schedules.edit',$schedule))->assertOk();
    }

    public function test_role_based_access_separates_read_and_write(): void
    {
        $viewer=$this->userWithPermissions(['pm.view']);
        $this->actingAs($viewer)->get(route('admin.pm.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.pm.systems.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.pm.parameters.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.pm.schedules.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.pm.systems.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.pm.systems.store'),[])->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.pm.parameters.store'),[])->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.pm.schedules.store'),[])->assertForbidden();
    }

    public function test_user_without_pm_permissions_is_forbidden(): void
    {
        $user=$this->userWithPermissions(['assets.view']);
        $this->actingAs($user)->get(route('admin.pm.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.pm.systems.index'))->assertForbidden();
        $this->get(route('admin.pm.systems.create'))->assertForbidden();
    }

    public function test_master_parameters_and_ready_schedule_can_be_configured(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $this->post(route('admin.pm.parameters.store'),[
            'code'=>'CPU_USAGE','name'=>'CPU Usage','category'=>'capacity',
            'method'=>'cpu_usage','unit'=>'%','warning_max'=>'80',
            'failure_max'=>'90','is_active'=>'1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.pm.parameters.index'));

        $template=PmParameterTemplate::firstOrFail();
        $this->post(route('admin.pm.systems.store'),[
            'code'=>'SYS-001','name'=>'SIGIT','type'=>'application',
            'environment'=>'production','criticality'=>'high','status'=>'active',
            'target_url'=>'https://sigit.example.test','parameters'=>[$template->id],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.pm.systems.index'));
        $system=PmSystem::firstOrFail();
        $this->assertTrue($system->parameters()->whereKey($template->id)->exists());

        $this->post(route('admin.pm.schedules.store'),$this->scheduleData([
            'status'=>'ready','systems'=>[$system->id],
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.pm.schedules.index'));
        $schedule=PmSchedule::firstOrFail();
        $this->assertSame('ready',$schedule->status);
        $this->assertTrue($schedule->systems()->whereKey($system->id)->exists());
        $this->assertDatabaseCount('pm_schedules',1);
    }

    public function test_ready_schedule_requires_targets_and_parameter_configuration(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $this->post(route('admin.pm.schedules.store'),$this->scheduleData([
            'status'=>'ready',
        ]))->assertSessionHasErrors('systems');

        $system=$this->system();
        $this->post(route('admin.pm.schedules.store'),$this->scheduleData([
            'status'=>'ready','systems'=>[$system->id],
        ]))->assertSessionHasErrors('systems');
        $this->assertDatabaseCount('pm_schedules',0);
        $this->post(route('admin.pm.schedules.store'),$this->scheduleData())
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pm_schedules',1);
    }

    public function test_ready_schedule_rejects_automatic_parameter_without_any_expected_value_or_threshold(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $system=$this->system();
        $parameter=$this->parameter(['expected_value'=>null]);
        $system->parameters()->attach($parameter->id,['is_required'=>true]);
        $this->post(route('admin.pm.schedules.store'),$this->scheduleData([
            'status'=>'ready','systems'=>[$system->id],
        ]))->assertSessionHasErrors('systems');
        $this->assertDatabaseCount('pm_schedules',0);
    }

    public function test_invalid_frequency_and_numeric_thresholds_are_rejected(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $this->post(route('admin.pm.schedules.store'),$this->scheduleData([
            'frequency'=>'weekly','day_of_month'=>null,'day_of_week'=>null,
        ]))->assertSessionHasErrors('day_of_week');
        $this->post(route('admin.pm.parameters.store'),[
            'code'=>'CPU_USAGE','name'=>'CPU Usage','category'=>'capacity',
            'method'=>'cpu_usage','warning_max'=>'90','failure_max'=>'80','is_active'=>'1',
        ])->assertSessionHasErrors('failure_max');
        $this->assertDatabaseCount('pm_parameter_templates',0);
    }

    public function test_configurations_in_use_cannot_be_removed_or_deactivated(): void
    {
        $this->actingAs($this->userWithPermissions(['pm.view','pm.manage']));
        $parameter=$this->parameter();
        $system=$this->system();
        $system->parameters()->attach($parameter->id,['is_required'=>true]);
        $schedule=PmSchedule::create($this->scheduleData(['status'=>'ready']));
        $schedule->systems()->attach($system->id);

        $this->delete(route('admin.pm.systems.destroy',$system))->assertSessionHas('error');
        $this->delete(route('admin.pm.parameters.destroy',$parameter))->assertSessionHas('error');
        $this->put(route('admin.pm.systems.update',$system),[
            'code'=>$system->code,'name'=>$system->name,'type'=>$system->type,
            'environment'=>$system->environment,'criticality'=>$system->criticality,
            'status'=>'inactive','parameters'=>[],
        ])->assertSessionHasErrors('parameters');
        $this->put(route('admin.pm.parameters.update',$parameter),[
            'code'=>$parameter->code,'name'=>$parameter->name,
            'category'=>$parameter->category,'method'=>$parameter->method,'is_active'=>'0',
        ])->assertSessionHasErrors('is_active');
        $this->assertSame('active',$system->fresh()->status);
        $this->assertTrue($parameter->fresh()->is_active);
    }
}
