<?php

namespace App\Services;

use App\Models\PmRun;
use App\Models\PmSystem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PmMonitoringOverviewService
{
    /** One dashboard column per inspection category, irrespective of probe type. */
    public const COLUMNS = [
        'log_error' => ['title' => 'Log Error', 'codes' => ['MANUAL_APP_ERROR_LOG']],
        'backup' => ['title' => 'Backup', 'codes' => ['PROXMOX_NAS_BACKUP', 'BACKUP_AGE_DAILY', 'BACKUP_AGE_WEEKLY']],
        'module' => ['title' => 'Module/Library Update', 'codes' => ['MANUAL_PATCH_REVIEW']],
        'database' => ['title' => 'Database', 'codes' => ['DB_CONNECTION']],
        'ssl' => ['title' => 'SSL/TLS Status', 'codes' => ['WEB_SSL_EXPIRY']],
    ];

    public function weeksInMonth(int $year, int $month): int
    {
        $monthStart = CarbonImmutable::createMidnightDate($year, $month, 1, 'Asia/Jakarta');
        $first = $monthStart->startOfWeek(CarbonInterface::MONDAY);
        $last = $monthStart->endOfMonth()->startOfWeek(CarbonInterface::MONDAY);

        return (int) $first->diffInWeeks($last) + 1;
    }

    public function build(int $year, int $month, int $week): array
    {
        $monthStart = CarbonImmutable::createMidnightDate($year, $month, 1, 'Asia/Jakarta');
        $weekStart = $monthStart->startOfWeek(CarbonInterface::MONDAY)->addWeeks($week - 1);
        $start = $weekStart->lessThan($monthStart) ? $monthStart : $weekStart;
        $monthEnd = $monthStart->endOfMonth();
        $weekEnd = $weekStart->endOfWeek(CarbonInterface::SUNDAY);
        $end = $weekEnd->greaterThan($monthEnd) ? $monthEnd : $weekEnd;

        $systems = PmSystem::query()->where('type', 'application')
            ->where('status', 'active')->with('owner')->orderBy('name')->get();

        // Read-only reporting. Use only completed runs, not current configurations,
        // so retired templates and finalized reports remain historically accurate.
        $runs = PmRun::query()
            ->whereBetween('started_at', [
                $start->startOfDay()->utc()->toDateTimeString(),
                $end->endOfDay()->utc()->toDateTimeString(),
            ])
            ->where('status', '!=', 'running')
            ->with(['checks.correctiveAction', 'initiator', 'schedule.assignee'])
            ->orderByDesc('started_at')->orderByDesc('id')->get();

        $latest = [];
        foreach ($runs as $run) {
            foreach ($run->checks->groupBy('system_code') as $code => $checks) {
                if (! isset($latest[$code])) {
                    $latest[$code] = ['run' => $run, 'checks' => $checks];
                }
            }
        }

        $rows = $systems->map(function (PmSystem $system) use ($latest) {
            $result = $latest[$system->code] ?? null;
            $checks = $result['checks'] ?? collect();
            $statuses = [];
            $resolvedFlags = [];
            $details = [];

            foreach (self::COLUMNS as $key => $column) {
                $matching = $checks->filter(fn ($check) =>
                    in_array($check->parameter_code, $column['codes'], true));
                $statuses[$key] = $this->inspectionStatus($matching);
                // A closed corrective marks the issue as handled, not a retroactive PASS.
                // Every non-PASS check in the category must have its own resolved action.
                $resolvedFlags[$key] = $matching->isNotEmpty()
                    && $matching->contains(fn ($check) => $check->status !== 'pass')
                    && $matching->every(fn ($check) => $check->status === 'pass'
                        || $check->correctiveAction?->status === 'resolved');
                $details[$key] = $matching->map(fn ($check) => [
                    'name' => $check->parameter_name,
                    'status' => $check->status,
                    'message' => $check->message,
                    'corrective_status' => $check->correctiveAction?->status,
                ])->values()->all();
            }

            $run = $result['run'] ?? null;
            if (! $run) {
                $overall = 'not_checked';
            } elseif ($run->status === 'failed') {
                $overall = 'failed';
            } elseif (in_array('fail', $statuses, true) || in_array('warning', $statuses, true)) {
                $overall = 'finding';
            } elseif (in_array(null, $statuses, true)
                || in_array('pending', $statuses, true)
                || in_array('unavailable', $statuses, true)) {
                $overall = 'incomplete';
            } else {
                $overall = 'ok';
            }

            // Finalization and corrective closure do not rewrite technical history.
            // Once every required category has PASS or a closed corrective, the
            // work is operationally complete, with documented exceptions.
            $allHandled = collect(array_keys(self::COLUMNS))
                ->every(fn ($key) => $statuses[$key] === 'pass' || $resolvedFlags[$key]);
            $displayStatus = $overall;
            if ($run?->review_status === 'finalized') {
                if ($overall === 'ok') {
                    $displayStatus = 'final_ok';
                } elseif ($run->status !== 'failed' && $allHandled) {
                    $displayStatus = 'final_resolved';
                } elseif (in_array($overall, ['finding', 'failed'], true)) {
                    $displayStatus = 'final_finding';
                } else {
                    $displayStatus = 'final_exception';
                }
            }

            return [
                'system' => $system,
                'run' => $run,
                'pic' => $run?->schedule?->assignee?->name
                    ?? $system->owner?->name ?? $run?->initiator?->name ?? '—',
                'checked_at' => $run?->started_at?->copy()->timezone('Asia/Jakarta'),
                'statuses' => $statuses,
                'resolved_flags' => $resolvedFlags,
                'details' => $details,
                'overall' => $overall,
                'display_status' => $displayStatus,
            ];
        });

        return [
            'columns' => self::COLUMNS,
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'stats' => [
                'total' => $rows->count(),
                'checked' => $rows->whereNotNull('run')->count(),
                'ok' => $rows->where('overall', 'ok')->count(),
                'finalized' => $rows->whereIn('display_status', ['final_ok', 'final_resolved', 'final_finding', 'final_exception'])->count(),
                'resolved' => $rows->where('display_status', 'final_resolved')->count(),
                'finding' => $rows->whereIn('display_status', ['finding', 'failed', 'final_finding'])->count(),
                'exceptions' => $rows->where('display_status', 'final_exception')->count(),
                'incomplete' => $rows->whereIn('display_status', ['incomplete', 'not_checked'])->count(),
            ],
        ];
    }

    private function inspectionStatus(Collection $checks): ?string
    {
        if ($checks->isEmpty()) {
            return null;
        }

        // A backup may have multiple probes; never hide a failed/incomplete probe
        // behind a successful one.
        foreach (['fail', 'warning', 'unavailable', 'pending'] as $status) {
            if ($checks->contains('status', $status)) {
                return $status;
            }
        }

        return $checks->every(fn ($check) => $check->status === 'pass') ? 'pass' : 'unavailable';
    }
}
