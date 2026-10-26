@extends('layouts.admin')
@section('title','Pengelolaan Laporan Preventive Maintenance')
@section('page-title','Laporan Preventive Maintenance')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h2 class="font-heading text-2xl font-bold text-slate-950">Pengelolaan Laporan Preventive Maintenance</h2>
        <p class="mt-1 text-sm text-slate-500">Arsip, review, finalisasi, dan unduh laporan PM yang sudah dijalankan.</p>
    </div>
    <a href="{{ route('admin.pm.monthly.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100"><i class="fa-solid fa-table-cells-large"></i> Rekap Bulanan</a>
</div>
<form method="GET" action="{{ route('admin.reports.pm.index') }}" class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <label class="block text-xs font-semibold text-slate-600">Dari tanggal
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">Sampai tanggal
            <input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">Sistem
            <select name="system" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
                <option value="">Semua sistem</option>
                @foreach($systems as $system)
                    <option value="{{ $system->id }}" @selected((int)($input['system']??0)===$system->id)>{{ $system->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-semibold text-slate-600">Status dokumen
            <select name="review_status" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
                <option value="">Semua status</option>
                @foreach(['draft'=>'Draft','under_review'=>'Dalam Review','approved'=>'Disetujui','finalized'=>'Final'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($input['review_status']??'')===$value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-semibold text-slate-600">Tindak lanjut
            <select name="corrective" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
                <option value="">Semua corrective</option>
                <option value="open" @selected(($input['corrective']??'')==='open')>Masih terbuka</option>
                <option value="resolved" @selected(($input['corrective']??'')==='resolved')>Seluruhnya Closed</option>
                <option value="none" @selected(($input['corrective']??'')==='none')>Tanpa corrective</option>
            </select>
        </label>
        <label class="block text-xs font-semibold text-slate-600">Cari laporan
            <input type="search" name="search" value="{{ $input['search']??'' }}" maxlength="100" placeholder="Nama, kode atau nomor run" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">
        </label>
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i class="fa-solid fa-filter"></i> Terapkan Filter</button>
        <a href="{{ route('admin.reports.pm.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
        <span class="ml-auto text-xs text-slate-500">Periode berdasarkan waktu mulai PM (WIB)</span>
    </div>
</form>
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach([
        ['Total Laporan',$stats['total'],'fa-file-lines'],
        ['Sudah Final',$stats['finalized'],'fa-file-circle-check'],
        ['Belum Final',$stats['waiting'],'fa-clock'],
        ['Ada Corrective Terbuka',$stats['open_correctives'],'fa-triangle-exclamation']
    ] as $stat)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500"><i class="fa-solid {{ $stat[2] }} mr-1 text-blue-600"></i>{{ $stat[0] }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat[1] }}</p>
        </div>
    @endforeach
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-5">
        <h3 class="font-semibold text-slate-900">Daftar Laporan PM</h3>
        <span class="text-xs text-slate-500">{{ $runs->total() }} laporan ditemukan</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr><th class="p-4">Nomor / Jadwal</th><th class="p-4">Tanggal PM</th><th class="p-4">Sistem</th><th class="p-4">Pemeriksaan</th><th class="p-4">Dokumen</th><th class="p-4">Tindak Lanjut</th><th class="p-4">Pelaksana</th><th class="p-4">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($runs as $run)
                @php($label=match($run->review_status){
                    'finalized'=>['FINAL','bg-emerald-50 text-emerald-700'],
                    'approved'=>['DISETUJUI','bg-blue-50 text-blue-700'],
                    'under_review'=>['DALAM REVIEW','bg-amber-50 text-amber-700'],
                    default=>['DRAFT','bg-slate-100 text-slate-600']
                })
                @php($names=$run->checks->pluck('system_name')->filter()->unique()->values())
                <tr class="hover:bg-slate-50">
                    <td class="p-4 align-top">
                        @if($run->review_status==='finalized')
                            <span class="block font-mono text-xs font-semibold text-slate-800">REKA/SIGIT/PM/{{ $run->started_at?->timezone('Asia/Jakarta')->format('Y') }}/{{ str_pad((string)$run->id,5,'0',STR_PAD_LEFT) }}</span>
                        @else
                            <span class="block font-semibold text-slate-800">Run #{{ $run->id }}</span>
                        @endif
                        <span class="mt-1 block font-medium text-slate-700">{{ $run->schedule_name }}</span>
                        <span class="text-xs text-slate-500">{{ $run->schedule_code }}</span>
                    </td>
                    <td class="whitespace-nowrap p-4 align-top text-slate-600">{{ $run->started_at?->timezone('Asia/Jakarta')->format('d M Y') ?? '—' }}<span class="block text-xs">{{ $run->started_at?->timezone('Asia/Jakarta')->format('H:i') }} WIB</span></td>
                    <td class="p-4 align-top text-slate-700">{{ $names->take(3)->join(', ') ?: '—' }} @if($names->count()>3)<span class="text-xs text-slate-500">+{{ $names->count()-3 }} lainnya</span>@endif</td>
                    <td class="p-4 align-top"><span class="block font-semibold text-slate-800">{{ $run->checks_count }} parameter</span><span class="text-xs {{ $run->status==='completed'?'text-emerald-700':'text-amber-700' }}">{{ $run->result_label }}</span></td>
                    <td class="p-4 align-top"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $label[1] }}">{{ $label[0] }}</span></td>
                    <td class="p-4 align-top">
                        @if($run->corrective_total)
                            @php($closed=$run->corrective_total-$run->corrective_open)
                            <span class="font-semibold {{ $run->corrective_open===0?'text-emerald-700':'text-amber-700' }}">{{ $closed }}/{{ $run->corrective_total }} Closed</span>
                            @if($run->corrective_open>0)<span class="block text-xs text-amber-700">{{ $run->corrective_open }} belum selesai</span>@endif
                        @else<span class="text-slate-400">Tidak ada</span>@endif
                    </td>
                    <td class="p-4 align-top text-slate-600">{{ $run->initiator?->name ?? 'Scheduler' }}</td>
                    <td class="p-4 align-top">
                        <a href="{{ route('admin.pm.runs.show',$run) }}" class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:underline"><i class="fa-solid fa-pen-to-square"></i>{{ $run->review_status==='finalized'?'Lihat':'Kelola / Review' }}</a>
                        @if($run->review_status==='finalized')
                            <div class="mt-2 flex items-center gap-3 text-xs">
                                <a class="font-semibold text-red-600 hover:underline" href="{{ route('admin.pm.runs.pdf',$run) }}"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</a>
                                <a class="font-semibold text-emerald-700 hover:underline" href="{{ route('admin.pm.runs.excel',$run) }}"><i class="fa-solid fa-file-excel mr-1"></i>Excel</a>
                            </div>
                        @else<span class="mt-1 block text-xs text-slate-500">Unduhan setelah finalisasi</span>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="p-10 text-center text-slate-500">Tidak ada laporan PM sesuai filter.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-4">{{ $runs->links() }}</div>
</div>
<p class="mt-4 text-xs leading-5 text-slate-500">Status FINAL menunjukkan dokumen sudah disahkan, bukan semua parameter PASS. Corrective Closed tetap terpisah dari hasil pemeriksaan. PDF/Excel menggunakan snapshot laporan final yang terkunci.</p>
@endsection
