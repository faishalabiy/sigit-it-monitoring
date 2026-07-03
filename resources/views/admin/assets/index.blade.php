@extends('layouts.admin')

@section('page-title', 'Manajemen Aset')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">Aset IT</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $assets->total() }} aset terdaftar</p>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <button
            type="button"
            onclick="document.getElementById('importAssetModal').classList.remove('hidden')"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700"
        >
            <i class="fa-solid fa-file-import"></i>
            <span>Import Excel</span>
        </button>

        <a href="{{ route('admin.assets.export', request()->only(['search', 'category', 'status'])) }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            <i class="fa-solid fa-file-excel"></i>
            <span>Export Excel</span>
        </a>

        <a href="{{ route('admin.assets.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Aset</span>
        </a>
    </div>
</div>

{{-- Modal Import --}}
<div
    id="importAssetModal"
    class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 px-4 py-8"
>
    <div class="mx-auto max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-slate-950">Import Data Aset</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Upload file Excel atau CSV sesuai format template.
                </p>
            </div>

            <button
                type="button"
                onclick="document.getElementById('importAssetModal').classList.add('hidden')"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                <div class="mb-2 font-semibold">Format kolom:</div>

                <div class="overflow-x-auto rounded-lg bg-white p-3 font-mono text-xs text-slate-700">
                    asset_code, name, category, brand, model, serial_number,
                    description, condition, status, asset_pic, location,
                    purchase_date, purchase_price
                </div>

                <div class="mt-3 text-xs text-blue-700">
                    <strong>category:</strong> laptop, projector, camera, network, other
                    <br>
                    <strong>condition:</strong> good, fair, poor
                    <br>
                    <strong>status:</strong> available, borrowed, maintenance, fixed_asset, retired
                    <br>
                    <strong>asset_pic:</strong> wajib jika status = fixed_asset
                </div>
            </div>

            <a
                href="{{ route('admin.assets.import.template') }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i class="fa-solid fa-download text-blue-600"></i>
                <span>Download Template Import</span>
            </a>

            <form
                action="{{ route('admin.assets.import') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-4"
            >
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        File Excel / CSV <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="file"
                        name="file"
                        accept=".xlsx,.xls,.csv"
                        required
                        class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-green-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-green-700 hover:file:bg-green-100"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Maksimal 5 MB. Gunakan template agar format data sesuai.
                    </p>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="document.getElementById('importAssetModal').classList.add('hidden')"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700"
                    >
                        <i class="fa-solid fa-upload"></i>
                        <span>Upload & Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-check mt-0.5"></i>
            <div>{{ session('success') }}</div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
            <div>{{ session('error') }}</div>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-circle-exclamation mt-0.5"></i>

            <div class="space-y-1">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if(session('import_failures'))
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <div class="mb-2 flex items-center gap-2 font-semibold">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Beberapa baris gagal diimport</span>
        </div>

        <div class="max-h-64 overflow-y-auto">
            <ul class="list-disc space-y-1 pl-5">
                @foreach(session('import_failures') as $failure)
                    <li>
                        Baris {{ $failure->row() }}:
                        {{ implode(', ', $failure->errors()) }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form method="GET" action="{{ route('admin.assets.index') }}" class="mb-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.3fr_1fr_1fr_auto] xl:items-end">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Cari</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nama, kode, brand, PIC..."
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Kategori</label>
                <select
                    name="category"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="">Semua Kategori</option>
                    @foreach (\App\Models\Asset::categoryLabels() as $k => $v)
                        <option value="{{ $k }}" {{ request('category') === $k ? 'selected' : '' }}>
                            {{ $v }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Status</label>
                <select
                    name="status"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="">Semua Status</option>
                    @foreach (\App\Models\Asset::statusLabels() as $k => $v)
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>
                            {{ $v['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 xl:flex-none"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('admin.assets.index') }}"
                   class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 xl:flex-none">
                    Reset
                </a>
            </div>
        </div>
    </div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Kode Aset</th>
                    <th class="px-4 py-3">Nama Aset</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Brand / Model</th>
                    <th class="px-4 py-3">Kondisi</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Lokasi</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($assets as $asset)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-600">
                            {{ $asset->asset_code }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-950">{{ $asset->name }}</div>
                            @if($asset->serial_number)
                                <div class="mt-0.5 text-xs text-slate-400">S/N: {{ $asset->serial_number }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-slate-700">{{ $asset->category_label }}</td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                @class([
                                    'bg-green-50 text-green-700' => $asset->condition === 'good',
                                    'bg-amber-50 text-amber-700' => $asset->condition === 'fair',
                                    'bg-orange-50 text-orange-700' => $asset->condition === 'poor',
                                    'bg-red-50 text-red-700' => $asset->condition === 'damaged',
                                    'bg-slate-100 text-slate-600' => ! in_array($asset->condition, ['good', 'fair', 'poor', 'damaged']),
                                ])">
                                {{ $asset->condition_label }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="space-y-1">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    @class([
                                        'bg-green-50 text-green-700' => $asset->status === 'available',
                                        'bg-amber-50 text-amber-700' => $asset->status === 'borrowed',
                                        'bg-orange-50 text-orange-700' => $asset->status === 'maintenance',
                                        'bg-blue-50 text-blue-700' => $asset->status === 'fixed_asset',
                                        'bg-slate-100 text-slate-600' => $asset->status === 'retired',
                                        'bg-slate-100 text-slate-600' => ! in_array($asset->status, ['available', 'borrowed', 'maintenance', 'fixed_asset', 'retired']),
                                    ])">
                                    {{ $asset->status_info['label'] }}
                                </span>

                                @if($asset->status === 'fixed_asset' && $asset->asset_pic)
                                    <div class="text-xs text-slate-500">
                                        PIC: {{ $asset->asset_pic }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $asset->location ?? '-' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('admin.assets.show', $asset) }}"
                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-blue-600"
                                   title="Detail">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>

                                <a href="{{ route('admin.assets.edit', $asset) }}"
                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-blue-600"
                                   title="Edit">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>

                                <form method="POST" action="{{ route('admin.assets.qrcode.generate', $asset) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-blue-600"
                                        title="Generate QR Code"
                                    >
                                        <i class="fa-solid fa-qrcode text-xs"></i>
                                    </button>
                                </form>

                                @if($asset->qr_code && Storage::disk('public')->exists($asset->qr_code))
                                    <a href="{{ route('admin.assets.qrcode.download', $asset) }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-blue-600"
                                       title="Download QR Code">
                                        <i class="fa-solid fa-download text-xs"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="fa-solid fa-box-open text-xl"></i>
                            </div>
                            <div class="text-sm font-medium text-slate-700">Belum ada aset terdaftar.</div>
                            <a href="{{ route('admin.assets.create') }}"
                               class="mt-1 inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700">
                                Tambah aset pertama
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if($assets->total() > 0)
                Menampilkan {{ $assets->firstItem() }}–{{ $assets->lastItem() }} dari {{ $assets->total() }}
            @else
                Tidak ada data aset
            @endif
        </div>

        <div>
            {{ $assets->links() }}
        </div>
    </div>
</div>
@endsection
