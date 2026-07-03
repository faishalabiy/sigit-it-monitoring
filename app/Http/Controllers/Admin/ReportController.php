<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        // Borrowings in range
        $borrowings = Borrowing::with('asset')
            ->whereBetween('borrow_date', [$dateFrom, $dateTo])
            ->latest()
            ->get();

        // Summary by category
        $byCategory = $borrowings->groupBy(fn($b) => $b->asset->category ?? 'other')
            ->map(fn($group) => $group->count());

        // Summary by status
        $byStatus = $borrowings->groupBy('status')
            ->map(fn($group) => $group->count());

        // Most borrowed assets
        $topAssets = Borrowing::with('asset')
            ->whereBetween('borrow_date', [$dateFrom, $dateTo])
            ->select('asset_id', DB::raw('count(*) as borrow_count'))
            ->groupBy('asset_id')
            ->orderByDesc('borrow_count')
            ->limit(5)
            ->get();

        // Most active borrowers
        $topBorrowers = Borrowing::whereBetween('borrow_date', [$dateFrom, $dateTo])
            ->select('borrower_name', 'borrower_email', 'borrower_department',
                     DB::raw('count(*) as borrow_count'))
            ->groupBy('borrower_name', 'borrower_email', 'borrower_department')
            ->orderByDesc('borrow_count')
            ->limit(5)
            ->get();

        // Overdue count
        $overdueCount = $borrowings->where('status', 'overdue')->count();

        // Assets never borrowed
        $neverBorrowed = Asset::doesntHave('borrowings')->count();

        return view('admin.reports.index', compact(
            'borrowings', 'byCategory', 'byStatus',
            'topAssets', 'topBorrowers', 'overdueCount',
            'neverBorrowed', 'dateFrom', 'dateTo'
        ));
    }

    public function exportPdf(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $borrowings = Borrowing::with('asset')
            ->whereBetween('borrow_date', [$dateFrom, $dateTo])
            ->latest()
            ->get();

        $title     = 'Laporan Peminjaman Aset IT';
        $dateRange = \Carbon\Carbon::parse($dateFrom)->format('d M Y')
                   . ' s/d '
                   . \Carbon\Carbon::parse($dateTo)->format('d M Y');

        $pdf = Pdf::loadView('admin.reports.pdf', compact('borrowings', 'title', 'dateRange'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('laporan-peminjaman-' . $dateFrom . '-' . $dateTo . '.pdf');
    }
}
