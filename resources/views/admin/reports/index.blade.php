@extends('layouts.admin')

@section('page-title', 'Laporan')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">Laporan Peminjaman</h2>
        <p class="mt-1 text-sm text-slate-500">Rekap transaksi peminjaman aset IT</p>
    </div>

    <a href="{{ route('admin.reports.export') }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
       class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
        <i class="fa-solid fa-file-pdf"></i>
        <span>Export PDF</span>
    </a>
</div>

<form method="GET" action="{{ route('admin.reports.index') }}" class="mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Dari Tanggal</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Sampai Tanggal</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                <i class="fa-solid fa-filter"></i>
                <span>Terapkan</span>
            </button>
        </div>
    </div>
</form>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total Transaksi</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $borrowings->count() }}</div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Dikembalikan</p>
        <div class="mt-3 font-heading text-3xl font-bold text-green-600">{{ $byStatus['returned'] ?? 0 }}</div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Terlambat</p>
        <div class="mt-3 font-heading text-3xl font-bold text-red-600">{{ $overdueCount }}</div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Aset Tdk Dipinjam</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $neverBorrowed }}</div>
    </div>
</div>

<div class="mb-6 grid gap-5 xl:grid-cols-2">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Per Kategori</h3>
        </div>

        <div class="divide-y divide-slate-100 p-5">
            @foreach (\App\Models\Asset::categoryLabels() as $k => $v)
                @php
                    $count = $byCategory[$k] ?? 0;
                    $icon = match($k) {
                        'laptop' => 'fa-laptop',
                        'projector' => 'fa-video',
                        'camera' => 'fa-camera',
                        'network' => 'fa-network-wired',
                        default => 'fa-box',
                    };
                @endphp

                <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                    <span class="flex items-center gap-2 text-sm text-slate-700">
                        <i class="fa-solid {{ $icon }} w-5 text-center text-slate-400"></i>
                        {{ $v }}
                    </span>

                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                        {{ $count }} transaksi
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Aset Paling Sering Dipinjam</h3>
        </div>

        <div class="divide-y divide-slate-100 p-5">
            @forelse ($topAssets as $i => $ta)
                <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-600">
                        {{ $i + 1 }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-slate-800">
                            {{ $ta->asset->name ?? '-' }}
                        </div>
                        <div class="mt-0.5 font-mono text-xs text-slate-400">
                            {{ $ta->asset->asset_code ?? '' }}
                        </div>
                    </div>

                    <span class="text-sm font-bold text-slate-950">{{ $ta->borrow_count }}×</span>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-slate-500">Tidak ada data</div>
            @endforelse
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-950">
            Detail Transaksi ({{ $borrowings->count() }} data)
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Aset</th>
                    <th class="px-4 py-3">Peminjam</th>
                    <th class="px-4 py-3">Tgl Pinjam</th>
                    <th class="px-4 py-3">Tgl Kembali (Rencana)</th>
                    <th class="px-4 py-3">Tgl Kembali (Aktual)</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($borrowings as $b)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-600">
                            {{ $b->borrow_code }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700">
                            {{ $b->asset->name }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="text-sm font-semibold text-slate-800">{{ $b->borrower_name }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $b->borrower_department }}</div>
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $b->borrow_date->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $b->planned_return_date->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $b->actual_return_date?->format('d M Y') ?? '—' }}
                        </td>

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
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
