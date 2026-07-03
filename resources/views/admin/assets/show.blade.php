@extends('layouts.admin')

@section('page-title', 'Detail Aset')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">
            {{ $asset->name }}
        </h2>
        <p class="mt-1 font-mono text-sm text-blue-600">{{ $asset->asset_code }}</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.assets.edit', $asset) }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-pen"></i>
            <span>Edit</span>
        </a>

        <a href="{{ route('admin.assets.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
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

<div class="grid gap-5 xl:grid-cols-[2fr_1fr] xl:items-start">
    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Informasi Aset</h3>
            </div>

            <div class="p-5">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kode Aset</p>
                        <p class="mt-1 font-mono text-sm font-semibold text-blue-600">{{ $asset->asset_code }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kategori</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $asset->category_label }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Brand</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $asset->brand ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Model</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $asset->model ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Serial Number</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $asset->serial_number ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kondisi</p>
                        <div class="mt-2">
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
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="mt-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                @class([
                                    'bg-green-50 text-green-700' => $asset->status === 'available',
                                    'bg-amber-50 text-amber-700' => $asset->status === 'borrowed',
                                    'bg-orange-50 text-orange-700' => $asset->status === 'maintenance',
                                    'bg-slate-100 text-slate-600' => $asset->status === 'retired',
                                    'bg-blue-50 text-blue-700' => ! in_array($asset->status, ['available', 'borrowed', 'maintenance', 'retired']),
                                ])">
                                {{ $asset->status_info['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Lokasi</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $asset->location ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tgl Pembelian</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $asset->purchase_date?->format('d F Y') ?? '-' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Harga</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $asset->purchase_price ? 'Rp ' . number_format($asset->purchase_price, 0, ',', '.') : '-' }}
                        </p>
                    </div>
                </div>

                @if($asset->description)
                    <div class="mt-4 rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Deskripsi</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $asset->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Riwayat Peminjaman (10 terakhir)</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Peminjam</th>
                            <th class="px-4 py-3">Tgl Pinjam</th>
                            <th class="px-4 py-3">Tgl Kembali</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($asset->borrowings as $b)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-600">
                                    <a href="{{ route('admin.borrowings.show', $b) }}" class="hover:text-blue-700">
                                        {{ $b->borrow_code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $b->borrower_name }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $b->borrow_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $b->actual_return_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        @class([
                                            'bg-green-50 text-green-700' => in_array($b->status, ['returned']),
                                            'bg-amber-50 text-amber-700' => in_array($b->status, ['borrowed']),
                                            'bg-red-50 text-red-700' => in_array($b->status, ['overdue']),
                                            'bg-blue-50 text-blue-700' => in_array($b->status, ['approved']),
                                            'bg-slate-100 text-slate-600' => in_array($b->status, ['cancelled']),
                                        ])">
                                        {{ $b->status_info['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                    Belum ada riwayat peminjaman
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        @if($asset->photo)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <img
                    src="{{ asset('storage/'.$asset->photo) }}"
                    class="max-h-64 w-full rounded-xl object-contain"
                    alt="{{ $asset->name }}"
                >
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="flex items-center gap-2 font-semibold text-slate-950">
                    <i class="fa-solid fa-qrcode text-blue-600"></i>
                    QR Code
                </h3>
            </div>

            <div class="p-5 text-center">
                @if($asset->qr_code && Storage::disk('public')->exists($asset->qr_code))
                    <img
                        src="{{ asset('storage/'.$asset->qr_code) }}"
                        class="mx-auto mb-4 h-40 w-40"
                        alt="QR Code {{ $asset->asset_code }}"
                    >

                    <div class="space-y-2">
                        <a href="{{ route('admin.assets.qrcode.download', $asset) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i class="fa-solid fa-download"></i>
                            <span>Download QR</span>
                        </a>

                        <form method="POST" action="{{ route('admin.assets.qrcode.generate', $asset) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <i class="fa-solid fa-rotate"></i>
                                <span>Regenerate</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mb-4 text-sm text-slate-500">QR Code belum digenerate</div>

                    <form method="POST" action="{{ route('admin.assets.qrcode.generate', $asset) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Generate QR Code</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">
            <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                <h3 class="font-semibold text-red-700">Hapus Aset</h3>
            </div>

            <div class="p-5">
                <p class="mb-4 text-sm leading-6 text-slate-500">
                    Aset yang dihapus tidak dapat dipulihkan. Hanya aset yang tidak sedang dipinjam yang dapat dihapus.
                </p>

                <form
                    method="POST"
                    action="{{ route('admin.assets.destroy', $asset) }}"
                    onsubmit="return confirm('Yakin ingin menghapus aset {{ $asset->name }}?')"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                    >
                        <i class="fa-solid fa-trash"></i>
                        <span>Hapus Aset</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
