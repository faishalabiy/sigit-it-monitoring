@extends('layouts.admin')

@section('page-title', 'Peminjaman')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-heading text-2xl font-bold tracking-tight text-slate-950">Data Peminjaman</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola seluruh transaksi peminjaman aset IT</p>
    </div>

    <a href="{{ route('admin.borrowings.export') }}"
       class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
        <i class="fa-solid fa-file-pdf text-red-500"></i>
        <span>Export PDF</span>
    </a>
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-blue-500 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Disetujui</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $stats['approved'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-amber-500 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Dipinjam</p>
        <div class="mt-3 font-heading text-3xl font-bold text-slate-950">{{ $stats['borrowed'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-red-600 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Terlambat</p>
        <div class="mt-3 font-heading text-3xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
    </div>

    <div class="rounded-2xl border border-l-4 border-slate-200 border-l-green-600 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kembali Hari Ini</p>
        <div class="mt-3 font-heading text-3xl font-bold text-green-600">{{ $stats['returned'] }}</div>
    </div>
</div>

<form method="GET" action="{{ route('admin.borrowings.index') }}" class="mb-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.25fr_1fr_1fr_1fr_auto] xl:items-end">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Cari</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Kode, nama, email..."
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Status</label>
                <select
                    name="status"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
                    <option value="">Semua</option>
                    @foreach (\App\Models\Borrowing::statusLabels() as $k => $v)
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>
                            {{ $v['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Dari Tanggal</label>
                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-600">Sampai Tanggal</label>
                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                >
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 xl:flex-none"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('admin.borrowings.index') }}"
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
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Aset</th>
                    <th class="px-4 py-3">Peminjam</th>
                    <th class="px-4 py-3">Departemen</th>
                    <th class="px-4 py-3">Tgl Pinjam</th>
                    <th class="px-4 py-3">Rencana Kembali</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($borrowings as $b)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-600">
                            {{ $b->borrow_code }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-950">{{ $b->asset->name }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $b->asset->asset_code }}</div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-950">{{ $b->borrower_name }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $b->borrower_email }}</div>
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $b->borrower_department ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $b->borrow_date->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3 text-xs {{ $b->is_overdue ? 'font-semibold text-red-600' : 'text-slate-600' }}">
                            {{ $b->planned_return_date->format('d M Y') }}

                            @if($b->is_overdue)
                                <span class="ml-1 text-[10px]">({{ $b->days_info['label'] }})</span>
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

                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.borrowings.show', $b) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-blue-600">
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Detail</span>
                                </a>

                                @if (in_array($b->status, ['borrowed', 'overdue']))
                                    <a href="{{ route('admin.borrowings.return.form', $b) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-green-700">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span>Kembalikan</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <i class="fa-solid fa-clipboard-list text-xl"></i>
                            </div>
                            <div class="text-sm font-medium text-slate-700">Belum ada data peminjaman.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <div>
            Menampilkan {{ $borrowings->firstItem() }}–{{ $borrowings->lastItem() }} dari {{ $borrowings->total() }}
        </div>

        <div>
            {{ $borrowings->links() }}
        </div>
    </div>
</div>
@endsection
