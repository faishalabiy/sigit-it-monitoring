<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Public\LandingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Middleware\RequirePermission;
use App\Http\Controllers\Admin\PM\PmDashboardController;
use App\Http\Controllers\Admin\PM\PmSystemController;
use App\Http\Controllers\Admin\PM\PmParameterController;
use App\Http\Controllers\Admin\PM\PmScheduleController;
use App\Http\Controllers\Admin\PM\PmRunController;
use App\Http\Controllers\Admin\PM\PmNotificationController;
use App\Http\Controllers\Admin\PM\PmBackupMonitoringController;
use App\Http\Controllers\Admin\PM\PmReportApprovalController;
use App\Http\Controllers\Admin\PM\PmCorrectiveController;
use App\Http\Controllers\Admin\PM\PmReportManagementController;

Route::get('/', [LandingController::class, 'index'])->name('public.landing');
Route::get('/pinjam', [LandingController::class, 'borrowForm'])->name('public.borrow.form');
Route::get('/kembali', [LandingController::class, 'returnForm'])->name('public.return.form');
Route::post('/pinjam', [LandingController::class, 'submitBorrow'])->middleware('throttle:10,1')->name('public.borrow.submit');
Route::post('/kembali', [LandingController::class, 'submitReturn'])->middleware('throttle:10,1')->name('public.return.submit');
Route::get('/pinjam/berhasil/{code}', [LandingController::class, 'borrowSuccess'])->name('public.borrow.success');
Route::get('/kembali/berhasil/{code}', [LandingController::class, 'returnSuccess'])->name('public.return.success');
Route::get('/api/cek-status', [LandingController::class, 'checkStatus'])->middleware('throttle:20,1')->name('public.check.status');
Route::get('/api/aset-tersedia', [LandingController::class, 'getAvailableAssets'])->name('public.assets.available');

Route::prefix('admin')->name('admin.')->middleware(['auth', \App\Http\Middleware\ApprovedAdmin::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('pm')->name('pm.')->group(function () {
        Route::get('notifications', [PmNotificationController::class,'index'])
            ->middleware(RequirePermission::class.':pm.view')->name('notifications.index');
        Route::patch('notifications/{notification}/read', [PmNotificationController::class,'markRead'])
            ->middleware(RequirePermission::class.':pm.view')->name('notifications.read');
        Route::get('backups', [PmBackupMonitoringController::class,'index'])
            ->middleware(RequirePermission::class.':pm.view')->name('backups.index');

        Route::get('correctives', [PmCorrectiveController::class,'index'])
            ->middleware(RequirePermission::class.':pm.view')->name('correctives.index');
        Route::get('correctives/{action}', [PmCorrectiveController::class,'show'])
            ->middleware(RequirePermission::class.':pm.view')->name('correctives.show');
        Route::patch('correctives/{action}', [PmCorrectiveController::class,'update'])
            ->middleware(RequirePermission::class.':pm.manage')->name('correctives.update');
        Route::post('runs/{run}/checks/{check}/corrective', [PmCorrectiveController::class,'createFromCheck'])
            ->middleware(RequirePermission::class.':pm.manage')->name('correctives.from-check');

        Route::post('runs/{run}/approval/{event}', [PmReportApprovalController::class,'transition'])
            ->whereIn('event',['submit','approve','reject','finalize'])
            ->middleware('throttle:10,1')->name('runs.approval');
        Route::get('runs/{run}/download/pdf', [PmReportApprovalController::class,'pdf'])
            ->middleware(RequirePermission::class.':pm.view')->name('runs.pdf');
        Route::get('runs/{run}/download/excel', [PmReportApprovalController::class,'excel'])
            ->middleware(RequirePermission::class.':pm.view')->name('runs.excel');
        Route::get('runs', [PmRunController::class,'index'])
            ->middleware(RequirePermission::class.':pm.view')->name('runs.index');
        Route::get('runs/{run}', [PmRunController::class,'show'])
            ->middleware(RequirePermission::class.':pm.view')->name('runs.show');
        Route::patch('runs/{run}/checks/{check}', [PmRunController::class,'reviewManual'])
            ->middleware(RequirePermission::class.':pm.manage')->name('runs.checks.review');
        Route::post('schedules/{schedule}/run', [PmRunController::class,'execute'])
            ->middleware([RequirePermission::class.':pm.manage','throttle:4,1'])->name('schedules.run');

        Route::get('monthly', [PmDashboardController::class, 'monthly'])
            ->middleware(RequirePermission::class.':pm.view')->name('monthly.index');
        Route::get('/', [PmDashboardController::class, 'index'])
            ->middleware(RequirePermission::class.':pm.view')->name('index');
        Route::resource('systems', PmSystemController::class)->except('show')
            ->middlewareFor('index', RequirePermission::class.':pm.view')
            ->middlewareFor(['create','store','edit','update','destroy'], RequirePermission::class.':pm.manage');
        Route::resource('parameters', PmParameterController::class)->except('show')
            ->middlewareFor('index', RequirePermission::class.':pm.view')
            ->middlewareFor(['create','store','edit','update','destroy'], RequirePermission::class.':pm.manage');
        Route::resource('schedules', PmScheduleController::class)->except('show')
            ->middlewareFor('index', RequirePermission::class.':pm.view')
            ->middlewareFor(['create','store','edit','update','destroy'], RequirePermission::class.':pm.manage');
    });

    Route::resource('users',UserManagementController::class)->except('show')->middleware(RequirePermission::class.':users.manage');
    Route::resource('roles',RoleManagementController::class)->except('show')->middleware(RequirePermission::class.':roles.manage');

    Route::get('qrcode/peminjaman/download', [DashboardController::class, 'downloadBorrowQrCode'])->middleware(RequirePermission::class.':borrowings.view')->name('dashboard.borrow.qrcode.download');

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
        ->parameters(['aset' => 'asset'])->middlewareFor(['index','show'],RequirePermission::class.':assets.view')->middlewareFor(['create','store','edit','update','destroy'],RequirePermission::class.':assets.manage');

    Route::post('aset/{asset}/qrcode', [AssetController::class, 'generateQrCode'])->middleware(RequirePermission::class.':assets.manage')->name('assets.qrcode.generate');
    Route::get('aset/{asset}/qrcode/download', [AssetController::class, 'downloadQrCode'])->middleware(RequirePermission::class.':assets.view')->name('assets.qrcode.download');
    Route::get('peminjaman', [BorrowingController::class, 'index'])->middleware(RequirePermission::class.':borrowings.view')->name('borrowings.index');
    Route::get('peminjaman/{borrowing}', [BorrowingController::class, 'show'])->middleware(RequirePermission::class.':borrowings.view')->name('borrowings.show');
    Route::patch('peminjaman/{borrowing}/status', [BorrowingController::class, 'updateStatus'])->middleware(RequirePermission::class.':borrowings.manage')->name('borrowings.status');
    Route::patch('aset/{asset}/return-active', [BorrowingController::class, 'returnActiveByAsset'])->middleware(RequirePermission::class.':borrowings.manage')->name('assets.return-active');
    Route::get('peminjaman/{borrowing}/kembalikan', [BorrowingController::class, 'returnForm'])->middleware(RequirePermission::class.':borrowings.manage')->name('borrowings.return.form');
    Route::get('peminjaman/export/pdf', [BorrowingController::class, 'export'])->middleware(RequirePermission::class.':reports.view')->name('borrowings.export');
    Route::get('laporan/preventive-maintenance', [PmReportManagementController::class, 'index'])
        ->middleware(RequirePermission::class.':pm.view')->name('reports.pm.index');
    Route::get('laporan', [ReportController::class, 'index'])->middleware(RequirePermission::class.':reports.view')->name('reports.index');
    Route::get('laporan/export/pdf', [ReportController::class, 'exportPdf'])->middleware(RequirePermission::class.':reports.view')->name('reports.export');

    // import/export assets
    Route::post('aset/import', [AssetController::class, 'import'])->middleware(RequirePermission::class.':assets.manage')->name('assets.import');
    Route::get('aset/import/template', [AssetController::class, 'downloadImportTemplate'])->middleware(RequirePermission::class.':assets.manage')->name('assets.import.template');
    Route::get('aset/export/excel', [AssetController::class, 'exportExcel'])->middleware(RequirePermission::class.':assets.view')->name('assets.export');
});

require __DIR__.'/auth.php';
