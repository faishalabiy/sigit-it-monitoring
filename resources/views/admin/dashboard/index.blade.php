@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">Dashboard</h2>
        <p class="mt-1 text-sm text-slate-500">
            Ringkasan kondisi aset IT hari ini — {{ now()->format('d F Y') }}
        </p>
    </div>


    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.assets.create') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Aset</span>
        </a>

        <a href="{{ route('admin.dashboard.borrow.qrcode.download') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100">
            <i class="fa-solid fa-qrcode"></i>
            <span>QR Code Peminjaman</span>
        </a>
    </div>
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total Aset</p>
            <i class="fa-solid fa-laptop text-2xl text-blue-500"></i>
        </div>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $assetStats['total'] }}</div>
        <p class="mt-1 text-xs text-slate-500">Seluruh aset terdaftar</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tersedia</p>
            <i class="fa-solid fa-circle-check text-2xl text-green-500"></i>
        </div>
        <div class="mt-3 font-heading text-3xl font-bold text-green-600">{{ $assetStats['available'] }}</div>
        <p class="mt-1 text-xs text-slate-500">Siap dipinjam</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Dipinjam</p>
            <i class="fa-solid fa-arrow-up-from-bracket text-2xl text-amber-500"></i>
        </div>
        <div class="mt-3 font-heading text-3xl font-bold text-amber-600">{{ $assetStats['borrowed'] }}</div>
        <p class="mt-1 text-xs text-slate-500">Sedang digunakan</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Maintenance</p>
            <i class="fa-solid fa-screwdriver-wrench text-2xl text-orange-500"></i>
        </div>
        <div class="mt-3 font-heading text-3xl font-bold text-orange-600">{{ $assetStats['maintenance'] }}</div>
        <p class="mt-1 text-xs text-slate-500">Dalam perbaikan</p>
    </div>
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-blue-600 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Pinjam Hari Ini</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $borrowingStats['today'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-green-600 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Bulan Ini</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $borrowingStats['this_month'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-amber-500 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Sedang Aktif</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $borrowingStats['active'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-red-600 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Terlambat</p>
        <div class="mt-3 font-heading text-3xl font-bold text-red-600">{{ $borrowingStats['overdue'] }}</div>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-2">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Peminjaman Aktif</h3>
            <a href="{{ route('admin.borrowings.index') }}"
               class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                Lihat Semua
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Aset</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Kembali</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentBorrowings as $b)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $b->borrow_code }}</td>
                            <td class="px-4 py-3">{{ $b->asset->name }}</td>
                            <td class="px-4 py-3">{{ $b->borrower_name }}</td>
                            <td class="px-4 py-3 text-xs {{ $b->is_overdue ? 'font-semibold text-red-600' : 'text-slate-600' }}">
                                {{ $b->planned_return_date->format('d M') }}
                                @if($b->is_overdue)
                                    <i class="fa-solid fa-circle-exclamation ml-1"></i>
                                @endif
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
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Tidak ada peminjaman aktif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col gap-5">
        @if($overdueBorrowings->count() > 0)
            <div class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                    <h3 class="flex items-center gap-2 font-semibold text-red-700">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Terlambat ({{ $overdueBorrowings->count() }})
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Aset</th>
                                <th class="px-4 py-3">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($overdueBorrowings as $b)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $b->borrower_name }}</td>
                                    <td class="px-4 py-3">{{ $b->asset->name }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-red-600">
                                        {{ $b->days_info['label'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Aset per Kategori</h3>
            </div>

            <div class="space-y-4 p-5">
                @foreach (\App\Models\Asset::categoryLabels() as $key => $label)
                    @php
                        $count = $byCategory[$key] ?? 0;
                        $total = $assetStats['total'] ?: 1;
                        $icon = match($key) {
                            'laptop' => 'fa-laptop',
                            'projector' => 'fa-video',
                            'camera' => 'fa-camera',
                            'network' => 'fa-network-wired',
                            default => 'fa-box',
                        };
                    @endphp

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-700">
                                <i class="fa-solid {{ $icon }} w-5 text-center text-slate-400"></i>
                                {{ $label }}
                            </span>
                            <span class="font-semibold text-slate-950">{{ $count }}</span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-600 transition-all"
                                 style="width: {{ ($count / $total) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="mt-5 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Tren Peminjaman (6 Bulan Terakhir)</h3>
    </div>

    <div class="p-5">
        <div class="flex h-36 items-end gap-3">
            @php $maxCount = max(max(array_column($monthlyData, 'count')), 1); @endphp

            @foreach ($monthlyData as $m)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-xs font-semibold text-slate-700">{{ $m['count'] }}</span>
                    <div class="w-full rounded-t bg-blue-600 transition-all"
                         style="height: {{ ($m['count'] / $maxCount) * 80 }}px; min-height: 4px"></div>
                    <span class="whitespace-nowrap text-[10px] text-slate-400">{{ $m['month'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
