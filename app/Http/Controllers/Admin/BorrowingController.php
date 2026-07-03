<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Asset;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $query = Borrowing::with('asset')->latest();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('borrow_code', 'like', '%' . $request->search . '%')
                  ->orWhere('borrower_name', 'like', '%' . $request->search . '%')
                  ->orWhere('borrower_email', 'like', '%' . $request->search . '%')
                  ->orWhere('borrower_department', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('borrow_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('borrow_date', '<=', $request->date_to);
        }

        // Auto mark overdue
        Borrowing::whereIn('status', ['approved', 'borrowed'])
            ->where('planned_return_date', '<', today())
            ->update(['status' => 'overdue']);

        $borrowings = $query->paginate(15)->withQueryString();

        $stats = [
            'approved' => Borrowing::where('status', 'approved')->count(),
            'borrowed' => Borrowing::where('status', 'borrowed')->count(),
            'overdue'  => Borrowing::where('status', 'overdue')->count(),
            'returned' => Borrowing::whereDate('actual_return_date', today())->count(),
        ];

        return view('admin.borrowings.index', compact('borrowings', 'stats'));
    }

    public function show(Borrowing $borrowing)
    {
        $borrowing->load('asset', 'processor');
        return view('admin.borrowings.show', compact('borrowing'));
    }

    public function updateStatus(Request $request, Borrowing $borrowing)
    {
        $request->validate([
            'status'      => 'required|in:approved,borrowed,returned,overdue,cancelled',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $borrowing->status;
        $borrowing->update([
            'status'       => $request->status,
            'admin_notes'  => $request->admin_notes,
            'processed_by' => auth()->id(),
        ]);

        // Update asset status accordingly
        if ($request->status === 'cancelled' || $request->status === 'returned') {
            $borrowing->asset->update(['status' => 'available']);
            if ($request->status === 'returned') {
                $borrowing->update(['actual_return_date' => today()]);
            }
        } elseif ($request->status === 'borrowed') {
            $borrowing->asset->update(['status' => 'borrowed']);
        }

        return back()->with('success', 'Status peminjaman berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        $query = Borrowing::with('asset')->latest();

        if ($request->date_from) $query->whereDate('borrow_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('borrow_date', '<=', $request->date_to);
        if ($request->status)    $query->where('status', $request->status);

        $borrowings = $query->get();
        $title      = 'Laporan Peminjaman Aset IT';
        $dateRange  = ($request->date_from && $request->date_to)
            ? 'Periode: ' . \Carbon\Carbon::parse($request->date_from)->format('d M Y')
              . ' s/d ' . \Carbon\Carbon::parse($request->date_to)->format('d M Y')
            : 'Semua Periode';

        $pdf = Pdf::loadView('admin.reports.pdf', compact('borrowings', 'title', 'dateRange'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-peminjaman-' . now()->format('Ymd') . '.pdf');
    }

    public function returnActiveByAsset(Request $request, Asset $asset)
    {
        $request->validate([
            'return_condition' => 'nullable|in:good,fair,poor,damaged',
            'return_notes' => 'nullable|string|max:500',
        ]);

        $borrowing = Borrowing::where('asset_id', $asset->id)
            ->whereIn('status', ['approved', 'borrowed', 'overdue'])
            ->latest('borrow_date')
            ->first();

        if (! $borrowing) {
            return back()->with('error', 'Tidak ada peminjaman aktif untuk aset ini.');
        }

        $returnCondition = $request->return_condition ?? 'good';

        $conditionMap = [
            'good' => 'good',
            'fair' => 'fair',
            'poor' => 'poor',
            'damaged' => 'poor',
        ];

        DB::transaction(function () use ($borrowing, $asset, $request, $returnCondition, $conditionMap) {
            $borrowing->update([
                'status' => 'returned',
                'actual_return_date' => today(),
                'return_condition' => $returnCondition,
                'return_notes' => $request->return_notes,
            ]);

            $asset->update([
                'status' => 'available',
                'condition' => $conditionMap[$returnCondition] ?? 'good',
            ]);
        });

        return back()->with('success', 'Aset berhasil dikembalikan.');
    }

    public function returnForm(Borrowing $borrowing)
    {
        if (! in_array($borrowing->status, ['approved', 'borrowed', 'overdue'])) {
            return redirect()
                ->route('admin.borrowings.index')
                ->with('error', 'Peminjaman ini sudah tidak aktif atau sudah dikembalikan.');
        }

        return redirect()->route('public.return.form', [
            'borrow_code' => $borrowing->borrow_code,
        ]);
    }
}
