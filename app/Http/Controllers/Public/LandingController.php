<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Mail\BorrowingConfirmation;
use App\Mail\ReturnConfirmation;
use App\Mail\AdminBorrowingNotification;
use App\Mail\AdminReturnNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    /**
     * Show the public landing page
     */
    public function index()
    {
        $availableAssets = Asset::where('status', 'available')->orderBy('category')->orderBy('name')->get();

        $stats = [
            'total_assets' => Asset::count(),
            'available_assets' => Asset::where('status', 'available')->count(),
            'borrowed_assets' => Asset::where('status', 'borrowed')->count(),
            'total_borrowings' => Borrowing::whereIn('status', ['borrowed', 'returned'])->count(),
        ];

        return view('public.landing', compact('availableAssets', 'stats'));
    }

    /**
     * Show borrow form page
     */
    public function borrowForm()
    {
        $assets = Asset::where('status', 'available')->get();
        return view('public.borrow-form', compact('assets'));
    }

    /**
     * Show return form page
     */
    public function returnForm()
    {
        return view('public.return-form');
    }

    /**
     * Handle borrowing form submission
     */
    public function submitReturn(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'borrow_code' => 'required|string',
                'return_condition' => 'required|in:good,fair,poor,damaged',
                'return_notes' => 'nullable|string|max:500',
            ],
            [
                'borrow_code.required' => 'Kode peminjaman wajib diisi.',
                'return_condition.required' => 'Kondisi aset saat kembali wajib dipilih.',
                'return_condition.in' => 'Kondisi tidak valid.',
            ],
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $borrowing = Borrowing::where('borrow_code', strtoupper(trim($request->borrow_code)))
            ->whereIn('status', ['approved', 'borrowed', 'overdue'])
            ->first();

        if (!$borrowing) {
            return back()
                ->withErrors(['borrow_code' => 'Kode peminjaman tidak ditemukan atau aset sudah dikembalikan.'])
                ->withInput();
        }

        $borrowing->update([
            'status' => 'returned',
            'actual_return_date' => today(),
            'return_condition' => $request->return_condition,
            'return_notes' => $request->return_notes,
        ]);

        $conditionMap = [
            'good' => 'good',
            'fair' => 'fair',
            'poor' => 'poor',
            'damaged' => 'poor',
        ];

        $borrowing->asset->update([
            'status' => 'available',
            'condition' => $conditionMap[$request->return_condition] ?? 'good',
        ]);

        try {
            Mail::to($borrowing->borrower_email)->send(new ReturnConfirmation($borrowing));

            $adminEmail = config('iams.admin_email', env('ADMIN_EMAIL', 'admin@example.com'));
            Mail::to($adminEmail)->send(new AdminReturnNotification($borrowing));
        } catch (\Exception $e) {
            Log::warning('Email failed for return ' . $borrowing->borrow_code . ': ' . $e->getMessage());
        }

        return redirect()->route('public.return.success', $borrowing->borrow_code)->with('success', 'Pengembalian berhasil dicatat! Terima kasih.');
    }

    /**
     * Borrowing success page
     */
    public function borrowSuccess(string $code)
    {
        $borrowing = Borrowing::where('borrow_code', $code)->with('asset')->firstOrFail();

        return view('public.borrow-success', compact('borrowing'));
    }

    /**
     * Return success page
     */
    public function returnSuccess(string $code)
    {
        $borrowing = Borrowing::where('borrow_code', $code)->with('asset')->firstOrFail();

        return view('public.return-success', compact('borrowing'));
    }

    /**
     * Check borrowing status (AJAX)
     * Hanya memerlukan borrow_code, tanpa email
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'borrow_code' => 'required|string',
        ]);

        $borrowing = Borrowing::where('borrow_code', strtoupper(trim($request->borrow_code)))
            ->with('asset')
            ->first();

        if (!$borrowing) {
            return response()->json(['found' => false, 'message' => 'Kode peminjaman tidak ditemukan.']);
        }

        return response()->json([
            'found' => true,
            'status' => $borrowing->status,
            'statusInfo' => $borrowing->status_info,
            'daysInfo' => $borrowing->days_info,
            'isReturned' => $borrowing->status === 'returned',
            'asset' => [
                'name' => $borrowing->asset->name,
                'code' => $borrowing->asset->asset_code,
                'category' => $borrowing->asset->category_label,
                'icon' => $borrowing->asset->category_icon,
            ],
            'borrowDate' => $borrowing->borrow_date->format('d M Y'),
            'plannedReturn' => $borrowing->planned_return_date->format('d M Y'),
            'actualReturn' => $borrowing->actual_return_date?->format('d M Y'),
            'borrowCode' => $borrowing->borrow_code,
            'borrowerName' => $borrowing->borrower_name,
        ]);
    }

    /**
     * Get available assets (AJAX)
     */
    public function getAvailableAssets(Request $request)
    {
        $assets = Asset::where('status', 'available')->select('id', 'asset_code', 'name', 'category', 'brand', 'condition')->get()->map(
            fn($a) => [
                'id' => $a->id,
                'code' => $a->asset_code,
                'name' => $a->name,
                'category' => $a->category_label,
                'icon' => $a->category_icon,
                'brand' => $a->brand,
                'condition' => $a->condition_label,
                'label' => $a->category_icon . ' ' . $a->name . ' (' . $a->asset_code . ')',
            ],
        );

        return response()->json($assets);
    }

    public function submitBorrow(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'asset_id' => 'required|exists:assets,id',
                'tipe_peminjaman' => 'required|in:pribadi,kantor',
                'borrower_name' => 'required|string|max:255',
                'borrower_email' => 'required|email|max:255',
                'borrower_phone' => 'required|string|max:30',
                'borrower_department' => 'nullable|string|max:255',
                'borrower_id_number' => 'nullable|string|max:100',
                'borrow_date' => 'required|date|after_or_equal:today',
                'planned_return_date' => 'required|date|after_or_equal:borrow_date',
                'purpose' => 'required|string|max:1000',
                'destination' => 'nullable|string|max:255',
            ],
            [
                'asset_id.required' => 'Aset wajib dipilih.',
                'asset_id.exists' => 'Aset yang dipilih tidak valid.',
                'tipe_peminjaman.required' => 'Tipe peminjaman wajib dipilih.',
                'tipe_peminjaman.in' => 'Tipe peminjaman tidak valid.',
                'borrower_name.required' => 'Nama lengkap wajib diisi.',
                'borrower_email.required' => 'Email wajib diisi.',
                'borrower_email.email' => 'Format email tidak valid.',
                'borrower_phone.required' => 'Nomor telepon wajib diisi.',
                'borrow_date.required' => 'Tanggal pinjam wajib diisi.',
                'borrow_date.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
                'planned_return_date.required' => 'Rencana tanggal kembali wajib diisi.',
                'planned_return_date.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
                'purpose.required' => 'Keperluan peminjaman wajib diisi.',
            ],
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $borrowing = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                $asset = Asset::where('id', $request->asset_id)->lockForUpdate()->first();

                if (!$asset || $asset->status !== 'available') {
                    throw new \Exception('Aset sudah tidak tersedia atau sedang dipinjam.');
                }

                do {
                    $borrowCode = 'BRW-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
                } while (Borrowing::where('borrow_code', $borrowCode)->exists());

                $borrowing = Borrowing::create([
                    'borrow_code' => $borrowCode,
                    'asset_id' => $asset->id,
                    'tipe_peminjaman' => $request->tipe_peminjaman,
                    'borrower_name' => $request->borrower_name,
                    'borrower_email' => $request->borrower_email,
                    'borrower_phone' => $request->borrower_phone,
                    'borrower_department' => $request->borrower_department,
                    'borrower_id_number' => $request->borrower_id_number,
                    'borrow_date' => $request->borrow_date,
                    'planned_return_date' => $request->planned_return_date,
                    'purpose' => $request->purpose,
                    'destination' => $request->destination,

                    // Kalau sistemmu butuh approval dulu, ganti menjadi 'pending'
                    'status' => 'borrowed',
                ]);

                $asset->update([
                    'status' => 'borrowed',
                ]);

                return $borrowing->load('asset');
            });

            try {
                Mail::to($borrowing->borrower_email)->send(new BorrowingConfirmation($borrowing));

                $adminEmail = config('iams.admin_email', env('ADMIN_EMAIL', 'admin@example.com'));

                Mail::to($adminEmail)->send(new AdminBorrowingNotification($borrowing));
            } catch (\Exception $e) {
                Log::warning('Email failed for borrow ' . $borrowing->borrow_code . ': ' . $e->getMessage());
            }

            return redirect()->route('public.borrow.success', $borrowing->borrow_code)->with('success', 'Pengajuan peminjaman berhasil dikirim!');
        } catch (\Exception $e) {
            Log::warning('Borrow submission failed: ' . $e->getMessage());

            return back()
                ->withErrors(['asset_id' => $e->getMessage()])
                ->withInput();
        }
    }
}
