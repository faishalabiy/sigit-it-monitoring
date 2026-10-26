<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use Database\Seeders\PmInformationSystemParametersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmTemplateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_information_system_catalog_is_created_with_supported_methods(): void
    {
        $this->seed(PmInformationSystemParametersSeeder::class);

        $this->assertDatabaseCount('pm_parameter_templates', 16);

        foreach (PmParameterTemplate::all() as $parameter) {
            $this->assertArrayHasKey($parameter->method, PmParameterTemplate::METHODS);
            $this->assertArrayHasKey($parameter->category, PmParameterTemplate::CATEGORIES);
            $this->assertTrue($parameter->is_active);
        }

        $http = PmParameterTemplate::where('code','WEB_HTTP_STATUS')->firstOrFail();
        $this->assertSame('http_status', $http->method);
        $this->assertSame('200', $http->expected_value);

        $ssl = PmParameterTemplate::where('code','WEB_SSL_EXPIRY')->firstOrFail();
        $this->assertEquals(30, $ssl->warning_min);
        $this->assertEquals(7, $ssl->failure_min);

        $daily = PmParameterTemplate::where('code','BACKUP_AGE_DAILY')->firstOrFail();
        $weekly = PmParameterTemplate::where('code','BACKUP_AGE_WEEKLY')->firstOrFail();
        $this->assertEquals(2, $daily->warning_max);
        $this->assertEquals(8, $weekly->warning_max);
    }

    public function test_seeded_parameter_catalog_is_visible_to_authorized_pm_user_on_one_page(): void
    {
        $this->seed(PmInformationSystemParametersSeeder::class);
        config()->set('iams.admin_user_ids', '1');
        $user = \App\Models\User::factory()->create(['id'=>1]);

        $this->actingAs($user)
            ->get(route('admin.pm.parameters.index'))
            ->assertOk()
            ->assertSee('WEB_HTTP_STATUS')
            ->assertSee('MANUAL_BACKUP_RESTORE')
            ->assertSee('BACKUP_AGE_WEEKLY')
            ->assertSee('Parameter standar untuk Preventive Maintenance Sistem Informasi');
    }

    public function test_seeding_twice_does_not_overwrite_administrator_thresholds(): void
    {
        $this->seed(PmInformationSystemParametersSeeder::class);
        PmParameterTemplate::where('code','WEB_RESPONSE_TIME')->update([
            'warning_max'=>950, 'failure_max'=>2000,
        ]);
        $this->seed(PmInformationSystemParametersSeeder::class);

        $this->assertDatabaseCount('pm_parameter_templates', 16);
        $parameter = PmParameterTemplate::where('code','WEB_RESPONSE_TIME')->firstOrFail();
        $this->assertEquals(950, $parameter->warning_max);
        $this->assertEquals(2000, $parameter->failure_max);
    }
}
