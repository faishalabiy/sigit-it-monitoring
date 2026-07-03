<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AssetsExport;
use App\Exports\AssetsImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\AssetsImport;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('asset_code', 'like', '%'.$request->search.'%')
                    ->orWhere('brand', 'like', '%'.$request->search.'%')
                    ->orWhere('serial_number', 'like', '%'.$request->search.'%')
                    ->orWhere('asset_pic', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $assets = $query->orderBy('id')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.assets.index', compact('assets'));
    }

    public function create()
    {
        return view('admin.assets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_code' => 'required|string|max:100|unique:assets,asset_code',
            'name' => 'required|string|max:100',
            'category' => 'required|in:laptop,monitor,pc,printer,projector,camera,network,other',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50|unique:assets',
            'description' => 'nullable|string|max:500',
            'condition' => 'required|in:good,fair,poor',
            'status' => 'required|in:available,borrowed,maintenance,fixed_asset,retired',
            'asset_pic' => 'nullable|required_if:status,fixed_asset|string|max:100',
            'location' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
        ]);

        $asset = new Asset($request->except(['photo', '_token']));
        // $asset->asset_code = Asset::generateCode($request->category);
        // $asset->status     = 'available';

        if ($request->hasFile('photo')) {
            $asset->photo = $request->file('photo')->store('assets/photos', 'public');
        }

        $asset->save();

        // Generate QR Code
        $this->saveQrCode($asset);

        return redirect()->route('admin.assets.index')
            ->with('success', 'Aset berhasil ditambahkan: '.$asset->asset_code);
    }

    public function show(Asset $asset)
    {
        $asset->load(['borrowings' => fn ($q) => $q->with('asset')->latest()->limit(10)]);

        return view('admin.assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        return view('admin.assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'asset_code' => 'required|string|max:100|unique:assets,asset_code,'.$asset->id,
            'name' => 'required|string|max:100',
            'category' => 'required|in:laptop,monitor,pc,printer,projector,camera,network,other',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50|unique:assets,serial_number,'.$asset->id,
            'description' => 'nullable|string|max:500',
            'condition' => 'required|in:good,fair,poor',
            'status' => 'required|in:available,borrowed,maintenance,fixed_asset,retired',
            'asset_pic' => 'nullable|required_if:status,fixed_asset|string|max:100',
            'location' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
        ]);

        $oldAssetCode = $asset->asset_code;
        $oldQrCode = $asset->qr_code;

        if ($request->hasFile('photo')) {
            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }

            $asset->photo = $request->file('photo')->store('assets/photos', 'public');
        }

        $asset->fill($request->except(['photo', '_token', '_method']));
        $asset->save();

        if ($oldAssetCode !== $asset->asset_code) {
            if ($oldQrCode) {
                Storage::disk('public')->delete($oldQrCode);
            }

            $this->saveQrCode($asset);
        }

        return redirect()->route('admin.assets.show', $asset)
            ->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->status === 'borrowed') {
            return back()->with('error', 'Aset sedang dipinjam, tidak dapat dihapus.');
        }
        $asset->delete();

        return redirect()->route('admin.assets.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    public function generateQrCode(Asset $asset)
    {
        if ($asset->qr_code && Storage::disk('public')->exists($asset->qr_code)) {
            Storage::disk('public')->delete($asset->qr_code);
        }

        $this->saveQrCode($asset);

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', 'QR Code berhasil digenerate.');
    }

    public function downloadQrCode(Asset $asset)
    {
        if (! $asset->qr_code || ! Storage::disk('public')->exists($asset->qr_code)) {
            return back()->with('error', 'QR Code belum digenerate.');
        }

        return Storage::disk('public')->download($asset->qr_code, $asset->asset_code.'-qrcode.svg');
    }

    private function saveQrCode(Asset $asset): void
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $url = $baseUrl . route('public.borrow.form', [], false) . '?' . http_build_query([
            'asset' => $asset->asset_code,
        ]);

        $safeAssetCode = Str::slug($asset->asset_code, '-');

        $filename = 'assets/qrcodes/' . $asset->id . '-' . $safeAssetCode . '.svg';

        Storage::disk('public')->makeDirectory('assets/qrcodes');

        $svg = QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($url);

        Storage::disk('public')->put($filename, (string) $svg);

        $asset->forceFill([
            'qr_code' => $filename,
        ])->save();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new AssetsImport;

        Excel::import($import, $request->file('file'));

        if ($import->failures()->isNotEmpty()) {
            return back()
                ->with('error', 'Sebagian data gagal diimport. Periksa format dan duplikasi data.')
                ->with('import_failures', $import->failures());
        }

        return redirect()->route('admin.assets.index')
            ->with('success', 'Data aset berhasil diimport.');
    }

    public function downloadImportTemplate()
    {
        return Excel::download(
            new AssetsImportTemplateExport,
            'template-import-aset.xlsx'
        );
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new AssetsExport($request->only(['search', 'category', 'status'])),
            'data-aset-'.now()->format('Ymd').'.xlsx'
        );
    }
}
