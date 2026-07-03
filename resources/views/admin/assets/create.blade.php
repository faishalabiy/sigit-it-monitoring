@extends('layouts.admin')

@section('page-title', isset($asset) ? 'Edit Aset' : 'Tambah Aset')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">
            {{ isset($asset) ? 'Edit Aset: ' . $asset->asset_code : 'Tambah Aset Baru' }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ isset($asset)
                ? 'Perbarui data aset IT'
                : 'Daftarkan peralatan IT baru ke sistem'
            }}
        </p>
    </div>

    <a href="{{ route('admin.assets.index') }}"
       class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali</span>
    </a>
</div>

<div class="max-w-5xl">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-6">
            <form
                method="POST"
                action="{{ isset($asset) ? route('admin.assets.update', $asset) : route('admin.assets.store') }}"
                enctype="multipart/form-data"
                class="space-y-5"
            >
                @csrf
                @if(isset($asset))
                    @method('PUT')
                @endif

                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>

                            <div class="space-y-1">
                                @foreach($errors->all() as $e)
                                    <div>{{ $e }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid gap-5 md:grid-cols-2">

                    {{-- kode aset --}}
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">
                                Kode Aset <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="asset_code"
                                required
                                value="{{ old('asset_code', $asset->asset_code ?? '') }}"
                                placeholder="Contoh: AST-IT-0001"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Masukkan kode aset secara manual. Pastikan kode tidak sama dengan aset lain.
                            </p>
                        </div>

                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Nama Aset <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="name"
                            required
                            value="{{ old('name', $asset->name ?? '') }}"
                            placeholder="Contoh: Laptop HP EliteBook 840 G9"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Kategori <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="category"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                            <option value="">-- Pilih kategori --</option>

                            @foreach (\App\Models\Asset::categoryLabels() as $k => $v)
                                <option value="{{ $k }}"
                                    {{ old('category', $asset->category ?? '') === $k ? 'selected' : '' }}>
                                    {{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Kondisi <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="condition"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                            @foreach (\App\Models\Asset::conditionLabels() as $k => $v)
                                <option value="{{ $k }}"
                                    {{ old('condition', $asset->condition ?? 'good') === $k ? 'selected' : '' }}>
                                    {{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Brand / Merek
                        </label>

                        <input
                            type="text"
                            name="brand"
                            value="{{ old('brand', $asset->brand ?? '') }}"
                            placeholder="HP, Dell, Canon, TP-Link..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Model / Tipe
                        </label>

                        <input
                            type="text"
                            name="model"
                            value="{{ old('model', $asset->model ?? '') }}"
                            placeholder="EliteBook 840 G9"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            value="{{ old('serial_number', $asset->serial_number ?? '') }}"
                            placeholder="SN-XXXXXX"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Lokasi Penyimpanan
                        </label>

                        <input
                            type="text"
                            name="location"
                            value="{{ old('location', $asset->location ?? '') }}"
                            placeholder="Ruang IT Lantai 2"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Tanggal Pembelian
                        </label>

                        <input
                            type="date"
                            name="purchase_date"
                            value="{{ old('purchase_date', isset($asset) ? $asset->purchase_date?->format('Y-m-d') : '') }}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Harga Pembelian (Rp)
                        </label>

                        <input
                            type="number"
                            name="purchase_price"
                            min="0"
                            step="1000"
                            value="{{ old('purchase_price', $asset->purchase_price ?? '') }}"
                            placeholder="0"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Status <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="status"
                            id="status"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                            @foreach (\App\Models\Asset::statusLabels() as $k => $v)
                                <option value="{{ $k }}"
                                    {{ old('status', $asset->status ?? 'available') === $k ? 'selected' : '' }}>
                                    {{ $v['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="asset-pic-wrapper">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            PIC Aset <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="asset_pic"
                            id="asset_pic"
                            value="{{ old('asset_pic', $asset->asset_pic ?? '') }}"
                            placeholder="Contoh: Budi Santoso / Divisi HRGA"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Wajib diisi jika status aset adalah Aset Tetap.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Deskripsi
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Spesifikasi, catatan, atau informasi tambahan..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >{{ old('description', $asset->description ?? '') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Foto Aset
                        </label>

                        <input
                            type="file"
                            name="photo"
                            accept="image/*"
                            class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        >

                        @if(isset($asset) && $asset->photo)
                            <div class="mt-4">
                                <img
                                    src="{{ Storage::url($asset->photo) }}"
                                    class="h-24 w-32 rounded-xl border border-slate-200 object-cover"
                                >
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>

                        <span>
                            {{ isset($asset) ? 'Simpan Perubahan' : 'Tambah Aset' }}
                        </span>
                    </button>

                    <a href="{{ route('admin.assets.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusField = document.getElementById('status');
        const picWrapper = document.getElementById('asset-pic-wrapper');
        const picField = document.getElementById('asset_pic');

        if (!statusField || !picWrapper || !picField) {
            return;
        }

        function toggleAssetPic() {
            const isFixedAsset = statusField.value === 'fixed_asset';

            picWrapper.classList.toggle('hidden', !isFixedAsset);
            picField.required = isFixedAsset;

            if (!isFixedAsset) {
                picField.value = '';
            }
        }

        statusField.addEventListener('change', toggleAssetPic);
        toggleAssetPic();
    });
</script>
@endpush

@endsection
