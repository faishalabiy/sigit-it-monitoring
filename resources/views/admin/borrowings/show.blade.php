@extends('layouts.admin')

@section('page-title', 'Detail Peminjaman')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">
            {{ $borrowing->borrow_code }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">Detail transaksi peminjaman aset</p>
    </div>

    <a href="{{ route('admin.borrowings.index') }}"
       class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali</span>
    </a>
</div>

<div class="grid gap-5 xl:grid-cols-[2fr_1fr] xl:items-start">
    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="flex items-center gap-2 font-semibold text-slate-950">
                    <i class="fa-solid fa-clipboard-list text-blue-600"></i>
                    Info Peminjaman
                </h3>
            </div>

            <div class="p-5">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kode Peminjaman</p>
                        <p class="mt-1 font-mono text-base font-semibold text-blue-600">{{ $borrowing->borrow_code }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="mt-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                @class([
                                    'bg-green-50 text-green-700' => in_array($borrowing->status, ['returned']),
                                    'bg-amber-50 text-amber-700' => in_array($borrowing->status, ['borrowed']),
                                    'bg-red-50 text-red-700' => in_array($borrowing->status, ['overdue']),
                                    'bg-blue-50 text-blue-700' => in_array($borrowing->status, ['approved']),
                                    'bg-slate-100 text-slate-600' => in_array($borrowing->status, ['cancelled']),
                                ])">
                                {{ $borrowing->status_info['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Aset</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $borrowing->asset->name }} ({{ $borrowing->asset->asset_code }})
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kategori</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->asset->category_label }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tanggal Pinjam</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrow_date->format('d F Y') }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Rencana Kembali</p>
                        <p class="mt-1 text-sm font-semibold {{ $borrowing->is_overdue ? 'text-red-600' : 'text-slate-800' }}">
                            {{ $borrowing->planned_return_date->format('d F Y') }}
                        </p>
                    </div>

                    @if($borrowing->actual_return_date)
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tanggal Dikembalikan</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $borrowing->actual_return_date->format('d F Y') }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kondisi Kembali</p>
                            <div class="mt-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    @class([
                                        'bg-green-50 text-green-700' => $borrowing->return_condition === 'good',
                                        'bg-amber-50 text-amber-700' => $borrowing->return_condition === 'fair',
                                        'bg-orange-50 text-orange-700' => $borrowing->return_condition === 'poor',
                                        'bg-red-50 text-red-700' => $borrowing->return_condition === 'damaged',
                                        'bg-slate-100 text-slate-600' => ! in_array($borrowing->return_condition, ['good', 'fair', 'poor', 'damaged']),
                                    ])">
                                    {{ ['good'=>'Baik','fair'=>'Cukup','poor'=>'Rusak Ringan','damaged'=>'Rusak'][$borrowing->return_condition] ?? '-' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tujuan / Keperluan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->purpose }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Lokasi Penggunaan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->destination ?? '-' }}</p>
                    </div>
                </div>

                @if($borrowing->return_notes)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <span class="font-semibold">Catatan Pengembalian:</span>
                        {{ $borrowing->return_notes }}
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="flex items-center gap-2 font-semibold text-slate-950">
                    <i class="fa-solid fa-user text-blue-600"></i>
                    Info Peminjam
                </h3>
            </div>

            <div class="p-5">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Nama Lengkap</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrower_name }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrower_email }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">No. Telepon</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrower_phone }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Departemen</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrower_department ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4 md:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">NIP/NIM/NIK</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $borrowing->borrower_id_number ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="flex items-center gap-2 font-semibold text-slate-950">
                    <i class="fa-solid fa-gear text-blue-600"></i>
                    Update Status
                </h3>
            </div>

            <div class="p-5">
                <form method="POST" action="{{ route('admin.borrowings.status', $borrowing) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                        <select
                            name="status"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >
                            @foreach (\App\Models\Borrowing::statusLabels() as $k => $v)
                                <option value="{{ $k }}" {{ $borrowing->status === $k ? 'selected' : '' }}>
                                    {{ $v['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan Admin</label>
                        <textarea
                            name="admin_notes"
                            rows="3"
                            placeholder="Catatan internal..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                        >{{ $borrowing->admin_notes }}</textarea>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Update Status</span>
                    </button>
                </form>
            </div>
        </div>

        @if(!in_array($borrowing->status, ['returned','cancelled']))
            <div class="rounded-2xl border bg-white p-6 text-center shadow-sm
                {{ $borrowing->is_overdue ? 'border-red-200' : 'border-slate-200' }}">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full
                    @class([
                        'bg-red-50 text-red-600' => $borrowing->is_overdue,
                        'bg-amber-50 text-amber-600' => ! $borrowing->is_overdue && $borrowing->days_info['type'] === 'today',
                        'bg-green-50 text-green-600' => ! $borrowing->is_overdue && $borrowing->days_info['type'] !== 'today',
                    ])">
                    <i class="fa-solid {{ $borrowing->is_overdue ? 'fa-triangle-exclamation' : ($borrowing->days_info['type'] === 'today' ? 'fa-clock' : 'fa-calendar-days') }} text-xl"></i>
                </div>

                <div class="font-heading text-2xl font-bold
                    @class([
                        'text-red-600' => $borrowing->is_overdue,
                        'text-amber-600' => ! $borrowing->is_overdue && $borrowing->days_info['type'] === 'today',
                        'text-green-600' => ! $borrowing->is_overdue && $borrowing->days_info['type'] !== 'today',
                    ])">
                    {{ $borrowing->days_info['label'] }}
                </div>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="flex items-center gap-2 font-semibold text-slate-950">
                    <i class="fa-solid fa-laptop text-blue-600"></i>
                    Info Aset
                </h3>
            </div>

            <div class="p-5 text-sm">
                <div class="font-semibold text-slate-950">{{ $borrowing->asset->name }}</div>
                <div class="mt-1 font-mono text-xs text-blue-600">{{ $borrowing->asset->asset_code }}</div>

                <div class="mt-4 space-y-3">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Brand</span>
                        <span class="font-medium text-slate-800">{{ $borrowing->asset->brand ?? '-' }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500">Kondisi</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                            @class([
                                'bg-green-50 text-green-700' => $borrowing->asset->condition === 'good',
                                'bg-amber-50 text-amber-700' => $borrowing->asset->condition === 'fair',
                                'bg-orange-50 text-orange-700' => $borrowing->asset->condition === 'poor',
                                'bg-red-50 text-red-700' => $borrowing->asset->condition === 'damaged',
                                'bg-slate-100 text-slate-600' => ! in_array($borrowing->asset->condition, ['good', 'fair', 'poor', 'damaged']),
                            ])">
                            {{ $borrowing->asset->condition_label }}
                        </span>
                    </div>
                </div>

                <a href="{{ route('admin.assets.show', $borrowing->asset) }}"
                   class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-blue-600">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Lihat Detail Aset</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
