<?php

namespace App\Services;

use App\Models\PmSystem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PmMonthlyOverviewService
{
    public function __construct(private readonly PmMonitoringOverviewService $weekly) {}

    /**
     * Monthly matrix uses the same weekly status rules as the dashboard.
     * It groups by the local Jakarta calendar and preserves links to each run.
     */
    public function build(int $year, int $month, ?int $systemId = null): array
    {
        $start = CarbonImmutable::createMidnightDate($year, $month, 1, 'Asia/Jakarta');
        $end = $start->endOfMonth();
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $systems = PmSystem::query()->where('type', 'application')
            ->where('status', 'active')
            ->when($systemId, fn ($query) => $query->whereKey($systemId))
            ->orderBy('name')->get();
        $matrix = $systems->mapWithKeys(fn ($system) => [
            $system->code => [
                'system' => $system, 'weeks' => [],
                'completed' => 0, 'due' => 0, 'checked' => 0,
                'has_issue' => false, 'last_run' => null,
            ],
        ])->all();

        $weeks = [];
        for ($index = 1; $index <= $this->weekly->weeksInMonth($year, $month); $index++) {
            $report = $this->weekly->build($year, $month, $index);
            $isFuture = $report['start']->greaterThan($today);
            $weeks[] = [
                'number' => $index,
                'start' => $report['start'],
                'end' => $report['end'],
                'future' => $isFuture,
            ];

            foreach ($report['rows'] as $row) {
                $code = $row['system']->code;
                if (! isset($matrix[$code])) continue;
                $status = $isFuture && ! $row['run'] ? 'future' : $row['display_status'];
                $matrix[$code]['weeks'][$index] = [
                    'status' => $status,
                    'run' => $row['run'],
                    'date' => $row['checked_at'],
                    'statuses' => $row['statuses'],
                    'resolved_flags' => $row['resolved_flags'],
                ];
                if (! $isFuture) {
                    $matrix[$code]['due']++;
                    if (in_array($status, ['final_ok', 'final_resolved'], true)) {
                        $matrix[$code]['completed']++;
                    } elseif ($status !== 'not_checked') {
                        $matrix[$code]['has_issue'] = true;
                    }
                }
                if ($row['run']) {
                    $matrix[$code]['checked']++;
                    if (! $matrix[$code]['last_run']
                        || $row['run']->started_at->greaterThan($matrix[$code]['last_run']->started_at)) {
                        $matrix[$code]['last_run'] = $row['run'];
                    }
                }
            }
        }

        $rows = collect(array_values($matrix))->map(function (array $row) {
            if ($row['due'] === 0) {
                $row['monthly_status'] = 'scheduled';
            } elseif ($row['has_issue']) {
                $row['monthly_status'] = 'needs_attention';
            } elseif ($row['completed'] === $row['due']) {
                $row['monthly_status'] = 'completed';
            } else {
                $row['monthly_status'] = 'in_progress';
            }

            return $row;
        });

        return [
            'start' => $start, 'end' => $end,
            'weeks' => $weeks, 'rows' => $rows,
            'stats' => [
                'systems' => $rows->count(),
                'completed' => $rows->where('monthly_status', 'completed')->count(),
                'needs_attention' => $rows->where('monthly_status', 'needs_attention')->count(),
                'in_progress' => $rows->where('monthly_status', 'in_progress')->count(),
                'execution_weeks' => $rows->sum('checked'),
            ],
        ];
    }
}
