<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index()
    {
        // Asset stats
        $assetStats = [
            'total'       => Asset::count(),
            'available'   => Asset::where('status', 'available')->count(),
            'borrowed'    => Asset::where('status', 'borrowed')->count(),
            'maintenance' => Asset::where('status', 'maintenance')->count(),
        ];

        // By category
        $byCategory = Asset::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get()
            ->mapWithKeys(fn($r) => [$r->category => $r->total]);

        // Borrowing stats
        $borrowingStats = [
            'today'     => Borrowing::whereDate('borrow_date', today())->count(),
            'this_month'=> Borrowing::whereMonth('borrow_date', now()->month)
                            ->whereYear('borrow_date', now()->year)->count(),
            'overdue'   => Borrowing::where('status', 'overdue')->count(),
            'active'    => Borrowing::whereIn('status', ['approved', 'borrowed'])->count(),
        ];

        // Recent borrowings
        $recentBorrowings = Borrowing::with('asset')
            ->whereIn('status', ['approved', 'borrowed', 'overdue'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Overdue borrowings
        $overdueBorrowings = Borrowing::with('asset')
            ->where('status', 'overdue')
            ->orderBy('planned_return_date')
            ->get();

        // Monthly borrowings chart data (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $count = Borrowing::whereMonth('borrow_date', $date->month)
                              ->whereYear('borrow_date', $date->year)
                              ->count();
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        return view('admin.dashboard.index', compact(
            'assetStats', 'byCategory', 'borrowingStats',
            'recentBorrowings', 'overdueBorrowings', 'monthlyData'
        ));
    }

    public function downloadBorrowQrCode()
    {
        $url = rtrim(config('app.url'), '/') . route('public.borrow.form', [], false);

        $svg = QrCode::format('svg')
            ->size(600)
            ->margin(2)
            ->generate($url);

        return response((string) $svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="qr-code-peminjaman-sigit.svg"');
    }
}
