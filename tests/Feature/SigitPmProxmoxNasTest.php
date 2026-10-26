<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use App\Services\PmProbeService;
use App\Services\PmProxmoxNasBackupService;
use Database\Seeders\PmProxmoxNasSharedVmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmProxmoxNasTest extends TestCase
{
    use RefreshDatabase;

    private function system(string $code, string $url): PmSystem
    {
        return PmSystem::create([
            'code' => $code,
            'name' => $code,
            'type' => 'application',
            'environment' => 'production',
            'criticality' => 'high',
            'status' => 'active',
            'target_url' => $url,
        ]);
    }

    public function test_one_nas_archive_passes_paduka_and_sipo_but_not_wrong_vm(): void
    {
        $paduka = $this->system('PADUKA', 'https://paduka.ptrekaindo.co.id');
        $sipo = $this->system('SIPO', 'https://sipo.ptrekaindo.co.id');
        $this->seed(PmProxmoxNasSharedVmSeeder::class);
        $parameter = PmParameterTemplate::where('code', 'PROXMOX_NAS_BACKUP')->firstOrFail();

        $directory = sys_get_temp_dir().'/test-pm-nas-'.uniqid();
        mkdir($directory);
        config()->set('pm.proxmox_backup.dump_directory', $directory);
        config()->set('pm.proxmox_backup.shared_vm_id', '101');
        $file = $directory.'/vzdump-qemu-101-2026_10_23-01_00_00.vma.zst';
        file_put_contents($file, 'valid-size');
        $other = $directory.'/vzdump-qemu-100-2026_10_23-01_00_00.vma.zst';
        file_put_contents($other, 'other-vm');

        try {
            $probe = app(PmProbeService::class);
            $this->assertSame('pass', $probe->check($paduka, $parameter)['status']);
            $this->assertSame('pass', $probe->check($sipo, $parameter)['status']);

            unlink($file);
            $this->assertSame('fail', $probe->check($paduka, $parameter)['status']);
            $this->assertSame('fail', $probe->check($sipo, $parameter)['status']);
        } finally {
            if (is_file($file)) unlink($file);
            if (is_file($other)) unlink($other);
            rmdir($directory);
        }
    }

    public function test_unmapped_or_unmounted_nas_is_unavailable_not_pass(): void
    {
        $system = $this->system('PADUKA', 'https://paduka.ptrekaindo.co.id');
        $this->seed(PmProxmoxNasSharedVmSeeder::class);
        $parameter = PmParameterTemplate::where('code', 'PROXMOX_NAS_BACKUP')->firstOrFail();
        config()->set('pm.proxmox_backup.shared_vm_id', null);
        $this->assertSame('unavailable', app(PmProbeService::class)->check($system, $parameter)['status']);
        config()->set('pm.proxmox_backup.shared_vm_id', '101');
        config()->set('pm.proxmox_backup.dump_directory', '/this-path-does-not-exist/test-nas');
        $this->assertSame('unavailable', app(PmProbeService::class)->check($system, $parameter)['status']);
    }

    public function test_shared_parameter_attached_to_both_applications_without_touching_other_systems(): void
    {
        $a = $this->system('PADUKA', 'https://paduka.ptrekaindo.co.id');
        $b = $this->system('SIPO', 'https://sipo.ptrekaindo.co.id');
        $other = $this->system('OTHER', 'https://other.ptrekaindo.co.id');
        $this->seed(PmProxmoxNasSharedVmSeeder::class);
        $this->seed(PmProxmoxNasSharedVmSeeder::class);
        $this->assertTrue($a->parameters()->where('code', 'PROXMOX_NAS_BACKUP')->exists());
        $this->assertTrue($b->parameters()->where('code', 'PROXMOX_NAS_BACKUP')->exists());
        $this->assertFalse($other->parameters()->where('code', 'PROXMOX_NAS_BACKUP')->exists());
    }
}
