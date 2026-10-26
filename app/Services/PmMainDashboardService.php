<?php

namespace App\Services;

use App\Models\PmCorrectiveAction;
use App\Models\PmRun;
use App\Models\PmSystem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PmMainDashboardService
{
    public function __construct(private readonly PmMonitoringOverviewService $overview) {}

    /** Read-only, permission-gated summary for the SIGIT home dashboard. */
    public function build(?CarbonImmutable $date = null): array
    {
        $today = ($date ?? CarbonImmutable::now('Asia/Jakarta'))->setTimezone('Asia/Jakarta');
        $start = $today->startOfMonth();
        $weekStart = $start->startOfWeek(CarbonInterface::MONDAY);
        $week = min(
            $this->overview->weeksInMonth($today->year, $today->month),
            (int) $weekStart->diffInWeeks($today->startOfWeek(CarbonInterface::MONDAY)) + 1
        );
        $weekly = $this->overview->build($today->year, $today->month, $week);
        $rows = $weekly['rows'];
        $checked = $rows->whereNotNull('run')->count();
        $handled = $rows->whereIn('display_status', ['final_ok', 'final_resolved'])->count();

        $monthRuns = PmRun::query()
            ->whereBetween('started_at', [
                $start->startOfDay()->utc()->toDateTimeString(),
                $today->endOfMonth()->endOfDay()->utc()->toDateTimeString(),
            ]);

        $recentReports = (clone $monthRuns)->where('status', '!=', 'running')
            ->with('initiator')
            ->withCount([
                'correctives as corrective_total',
                'correctives as corrective_open' => fn ($query) => $query->where('status', '!=', 'resolved'),
            ])
            ->orderByDesc('started_at')->orderByDesc('id')->limit(5)->get();

        $totalSystems = $rows->count();

        // Application checklist and infrastructure checks differ. Keep server
        // monitoring separate instead of treating missing application probes as failures.
        $servers = PmSystem::query()->where('type', 'server')
            ->where('status', 'active')->orderBy('name')->get();
        $weeklyRuns = PmRun::query()
            ->whereBetween('started_at', [
                $weekly['start']->startOfDay()->utc()->toDateTimeString(),
                $weekly['end']->endOfDay()->utc()->toDateTimeString(),
            ])
            ->where('status', '!=', 'running')
            ->with('checks.correctiveAction')
            ->orderByDesc('started_at')->orderByDesc('id')->get();

        $infra = $servers->map(function (PmSystem $system) use ($weeklyRuns) {
            $run = $weeklyRuns->first(fn ($candidate) =>
                $candidate->checks->contains('system_code', $system->code));
            $checks = $run
                ? $run->checks->where('system_code', $system->code)
                : collect();
            $allHandled = $checks->isNotEmpty()
                && $checks->every(fn ($check) => $check->status === 'pass'
                    || $check->correctiveAction?->status === 'resolved');
            $allPass = $checks->isNotEmpty()
                && $checks->every(fn ($check) => $check->status === 'pass');

            $status = 'not_checked';
            if ($run) {
                if ($run->review_status === 'finalized' && $run->status !== 'failed' && $allPass) {
                    $status = 'final_ok';
                } elseif ($run->review_status === 'finalized'
                    && $run->status !== 'failed' && $allHandled) {
                    $status = 'final_resolved';
                } elseif ($run->review_status === 'finalized') {
                    $status = 'final_exception';
                } elseif ($run->status === 'failed') {
                    $status = 'failed';
                } else {
                    $status = 'review';
                }
            }

            return [
                'system' => $system,
                'run' => $run,
                'status' => $status,
                'checks' => $checks->count(),
                'closed' => $checks->filter(fn ($check) => $check->status !== 'pass'
                    && $check->correctiveAction?->status === 'resolved')->count(),
            ];
        });

        return [
            'year' => $today->year,
            'month' => $today->month,
            'week' => $week,
            'period' => $start->locale('id')->translatedFormat('F Y'),
            'week_start' => $weekly['start'],
            'week_end' => $weekly['end'],
            'systems' => $totalSystems,
            'infrastructure' => $infra,
            'infrastructure_count' => $infra->count(),
            'infrastructure_checked' => $infra->whereNotNull('run')->count(),
            'checked' => $checked,
            'not_checked' => $totalSystems - $checked,
            'handled' => $handled,
            'coverage_percent' => $totalSystems ? (int) round($checked * 100 / $totalSystems) : 0,
            'finalized_month' => (clone $monthRuns)->where('review_status', 'finalized')->count(),
            'open_correctives' => PmCorrectiveAction::where('status', '!=', 'resolved')->count(),
            'overdue_correctives' => PmCorrectiveAction::where('status', '!=', 'resolved')
                ->whereDate('due_on', '<', $today->toDateString())->count(),
            'rows' => $rows,
            'recent_reports' => $recentReports,
        ];
    }
}
