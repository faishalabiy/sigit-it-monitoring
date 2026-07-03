<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Public\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('public.landing');
Route::get('/pinjam', [LandingController::class, 'borrowForm'])->name('public.borrow.form');
Route::get('/kembali', [LandingController::class, 'returnForm'])->name('public.return.form');
Route::post('/pinjam', [LandingController::class, 'submitBorrow'])->name('public.borrow.submit');
Route::post('/kembali', [LandingController::class, 'submitReturn'])->name('public.return.submit');
Route::get('/pinjam/berhasil/{code}', [LandingController::class, 'borrowSuccess'])->name('public.borrow.success');
Route::get('/kembali/berhasil/{code}', [LandingController::class, 'returnSuccess'])->name('public.return.success');
Route::get('/api/cek-status', [LandingController::class, 'checkStatus'])->name('public.check.status');
Route::get('/api/aset-tersedia', [LandingController::class, 'getAvailableAssets'])->name('public.assets.available');

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('qrcode/peminjaman/download', [DashboardController::class, 'downloadBorrowQrCode'])->name('dashboard.borrow.qrcode.download');

    Route::resource('aset', AssetController::class)
        ->names([
            'index' => 'assets.index',
            'create' => 'assets.create',
            'store' => 'assets.store',
            'show' => 'assets.show',
            'edit' => 'assets.edit',
            'update' => 'assets.update',
            'destroy' => 'assets.destroy',
        ])
        ->parameters(['aset' => 'asset']);

    Route::post('aset/{asset}/qrcode', [AssetController::class, 'generateQrCode'])->name('assets.qrcode.generate');
    Route::get('aset/{asset}/qrcode/download', [AssetController::class, 'downloadQrCode'])->name('assets.qrcode.download');
    Route::get('peminjaman', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('peminjaman/{borrowing}', [BorrowingController::class, 'show'])->name('borrowings.show');
    Route::patch('peminjaman/{borrowing}/status', [BorrowingController::class, 'updateStatus'])->name('borrowings.status');
    Route::patch('aset/{asset}/return-active', [BorrowingController::class, 'returnActiveByAsset'])->name('assets.return-active');
    Route::get('peminjaman/{borrowing}/kembalikan', [BorrowingController::class, 'returnForm'])->name('borrowings.return.form');
    Route::get('peminjaman/export/pdf', [BorrowingController::class, 'export'])->name('borrowings.export');
    Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('laporan/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export');

    // import/export assets
    Route::post('aset/import', [AssetController::class, 'import'])->name('assets.import');
    Route::get('aset/import/template', [AssetController::class, 'downloadImportTemplate'])->name('assets.import.template');
    Route::get('aset/export/excel', [AssetController::class, 'exportExcel'])->name('assets.export');
});

require __DIR__.'/auth.php';
