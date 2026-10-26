<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmSystem;
use App\Services\PmProbeService;
use Illuminate\View\View;

class PmBackupMonitoringController extends Controller
{
    public function index(PmProbeService $probe): View
    {
        $sources = config('pm.backup_directories', []);
        $sharedVm = (string) config('pm.proxmox_backup.shared_vm_id', '');
        $nasFolder = (string) config('pm.proxmox_backup.dump_directory', '');

        $systems = PmSystem::where('type', 'application')->where('status', 'active')
            ->with(['parameters' => fn ($query) => $query->where('is_active', true)
                ->whereIn('method', ['backup_present', 'backup_age'])])
            ->orderBy('name')->get()->map(function ($system) use ($probe, $sources, $sharedVm, $nasFolder) {
                $host = strtolower((string) parse_url((string) $system->target_url, PHP_URL_HOST));
                $template = $system->parameters->firstWhere('method', 'backup_present')
                    ?? $system->parameters->first();
                $isProxmox = $template?->method === 'backup_present';
                $path = $isProxmox ? $nasFolder : (is_array($sources) ? ($sources[$host] ?? null) : null);

                $result = $template ? $probe->check($system, $template) : [
                    'status' => 'unavailable', 'value' => null,
                    'message' => 'Belum dipilih parameter backup untuk sistem ini.',
                ];

                return [
                    'id' => $system->id, 'name' => $system->name, 'code' => $system->code,
                    'configured' => is_string($path) && is_dir($path) && is_readable($path)
                        && (!$isProxmox || preg_match('/^[1-9][0-9]{1,8}$/D', $sharedVm)),
                    'source' => $isProxmox ? 'NAS Synology · Proxmox VM '.($sharedVm ?: 'belum dipetakan') : 'Backup Aplikasi',
                    'parameter' => $template?->name, 'result' => $result,
                    'unit' => $isProxmox ? 'file' : 'hari',
                ];
            });

        return view('admin.pm.backups.index', compact('systems'));
    }
}
