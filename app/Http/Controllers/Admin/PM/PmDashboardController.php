<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmParameterTemplate;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Services\PmMonitoringOverviewService;
use App\Services\PmMonthlyOverviewService;
use App\Models\PmRun;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class PmDashboardController extends Controller
{
    public function monthly(Request $request, PmMonthlyOverviewService $monthly)
    {
        $data = $request->validate([
            'year' => ['sometimes', 'integer', 'between:2024,'.(now()->year + 1)],
            'month' => ['sometimes', 'integer', 'between:1,12'],
            'system' => ['sometimes', 'nullable', 'integer', 'exists:pm_systems,id'],
        ]);
        $now = CarbonImmutable::now('Asia/Jakarta');
        $year = (int) ($data['year'] ?? $now->year);
        $month = (int) ($data['month'] ?? $now->month);
        $systemId = isset($data['system']) ? (int) $data['system'] : null;
        $selectedSystem = $systemId ? PmSystem::where('type', 'application')->where('status','active')->findOrFail($systemId) : null;
        $monthlyOverview = $monthly->build($year, $month, $systemId);

        $runs = PmRun::query()->where('status', '!=', 'running')
            ->whereBetween('started_at', [
                $monthlyOverview['start']->startOfDay()->utc()->toDateTimeString(),
                $monthlyOverview['end']->endOfDay()->utc()->toDateTimeString(),
            ])
            ->whereHas('checks', function ($query) use ($selectedSystem) {
                if ($selectedSystem) {
                    $query->where('system_code', $selectedSystem->code);
                } else {
                    // Only runs with checks on application systems are included.
                    $query->whereIn('pm_system_id', PmSystem::where('type', 'application')->where('status','active')->select('id'));
                }
            })
            ->with(['initiator', 'checks.correctiveAction'])
            ->orderByDesc('started_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.pm.monthly', [
            'monthlyOverview' => $monthlyOverview,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'selectedSystemId' => $systemId,
            'systems' => PmSystem::where('type', 'application')->where('status','active')->orderBy('name')->get(),
            'runs' => $runs,
        ]);
    }

    public function index(Request $request, PmMonitoringOverviewService $overview, PmMonthlyOverviewService $monthly)
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'between:2024,'.(now()->year + 1)],
            'month' => ['sometimes', 'integer', 'between:1,12'],
            'week' => ['sometimes', 'integer', 'between:1,6'],
        ]);

        $today = CarbonImmutable::now('Asia/Jakarta');
        $year = (int) $request->input('year', $today->year);
        $month = (int) $request->input('month', $today->month);
        $first = CarbonImmutable::createMidnightDate($year, $month, 1, 'Asia/Jakarta')
            ->startOfWeek(CarbonInterface::MONDAY);
        $currentWeek = (int) $first->diffInWeeks($today->startOfWeek(CarbonInterface::MONDAY)) + 1;
        $weekCount = $overview->weeksInMonth($year, $month);
        $week = min((int) $request->input('week', $year === $today->year && $month === $today->month ? $currentWeek : 1), $weekCount);

        return view('admin.pm.index', [
            'overview' => $overview->build($year, $month, $week),
            'monthlyOverview' => $monthly->build($year, $month),
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'selectedWeek' => $week,
            'weekCount' => $weekCount,
            'systemCount' => PmSystem::count(),
            'activeSystemCount' => PmSystem::where('status', 'active')->count(),
            'parameterCount' => PmParameterTemplate::where('is_active', true)->count(),
            'readyCount' => PmSchedule::where('status', 'ready')->count(),
            'openCorrectives' => \App\Models\PmCorrectiveAction::where('status', '!=', 'resolved')->count(),
            'overdueCorrectives' => \App\Models\PmCorrectiveAction::where('status', '!=', 'resolved')
                ->whereDate('due_on', '<', today())->count(),
            'schedules' => PmSchedule::withCount('systems')->latest()->limit(8)->get(),
            'backupReadiness' => PmSystem::where('type', 'application')->orderBy('name')->get()
                ->map(function ($system) {
                    $domain = strtolower((string) parse_url((string) $system->target_url, PHP_URL_HOST));
                    $isSharedVm = in_array($domain, ['paduka.ptrekaindo.co.id', 'sipo.ptrekaindo.co.id'], true);
                    $sources = config('pm.backup_directories', []);
                    $directory = $isSharedVm
                        ? config('pm.proxmox_backup.dump_directory')
                        : (is_array($sources) ? ($sources[$domain] ?? null) : null);
                    $vmId = (string) config('pm.proxmox_backup.shared_vm_id', '');
                    return [
                        'name' => $system->name, 'code' => $system->code,
                        'configured' => is_string($directory) && is_dir($directory) && is_readable($directory)
                            && (!$isSharedVm || preg_match('/^[1-9][0-9]{1,8}$/D', $vmId)),
                    ];
                }),
        ]);
    }
}
