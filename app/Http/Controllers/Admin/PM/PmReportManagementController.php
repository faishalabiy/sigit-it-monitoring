<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmRun;
use App\Models\PmSystem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PmReportManagementController extends Controller
{
    public function index(Request $request): View
    {
        $input = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'system' => ['nullable', 'integer', 'exists:pm_systems,id'],
            'review_status' => ['nullable', Rule::in(['draft', 'under_review', 'approved', 'finalized'])],
            'corrective' => ['nullable', Rule::in(['open', 'resolved', 'none'])],
        ]);

        $today = CarbonImmutable::now('Asia/Jakarta');
        $dateFrom = $input['date_from'] ?? $today->startOfMonth()->toDateString();
        $dateTo = $input['date_to'] ?? $today->toDateString();
        if ($dateFrom > $dateTo) {
            throw ValidationException::withMessages(['date_to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
        }

        // Dates are interpreted in WIB; PM timestamps are stored in UTC.
        $fromUtc = CarbonImmutable::parse($dateFrom.' 00:00:00', 'Asia/Jakarta')->utc();
        $toUtc = CarbonImmutable::parse($dateTo.' 23:59:59', 'Asia/Jakarta')->utc();

        $systems = PmSystem::query()->where('type', 'application')->orderBy('name')->get(['id', 'code', 'name']);
        $system = isset($input['system'])
            ? $systems->firstWhere('id', (int) $input['system'])
            : null;
        if (isset($input['system']) && ! $system) abort(404);

        $query = PmRun::query()
            ->whereBetween('started_at', [$fromUtc, $toUtc])
            ->when($system, fn ($q) => $q->whereHas('checks', fn ($c) => $c->where('system_code', $system->code)))
            ->when($input['review_status'] ?? null,
                fn ($q, $status) => $q->where('review_status', $status))
            ->when($input['search'] ?? null, function ($q, $search) {
                $term = '%'.trim($search).'%';
                $q->where(function ($q) use ($term, $search) {
                    $q->where('schedule_name', 'like', $term)
                        ->orWhere('schedule_code', 'like', $term);
                    if (ctype_digit(trim($search))) $q->orWhere('id', (int) trim($search));
                });
            })
            ->when($input['corrective'] ?? null, function ($q, $status) {
                if ($status === 'open') {
                    $q->whereHas('correctives', fn ($c) => $c->where('status', '!=', 'resolved'));
                } elseif ($status === 'resolved') {
                    $q->whereHas('correctives')
                        ->whereDoesntHave('correctives', fn ($c) => $c->where('status', '!=', 'resolved'));
                } else {
                    $q->whereDoesntHave('correctives');
                }
            });

        $stats = [
            'total' => (clone $query)->count(),
            'finalized' => (clone $query)->where('review_status', 'finalized')->count(),
            'waiting' => (clone $query)->whereIn('review_status', ['draft', 'under_review', 'approved'])->count(),
            'open_correctives' => (clone $query)->whereHas('correctives',
                fn ($c) => $c->where('status', '!=', 'resolved'))->count(),
        ];

        $runs = $query->with(['initiator', 'approver', 'checks'])
            ->withCount([
                'checks',
                'correctives as corrective_total',
                'correctives as corrective_open' => fn ($q) => $q->where('status', '!=', 'resolved'),
            ])
            ->orderByDesc('started_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.reports.pm.index', compact(
            'runs', 'stats', 'systems', 'dateFrom', 'dateTo', 'input'
        ));
    }
}
