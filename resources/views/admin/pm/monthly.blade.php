@extends('layouts.admin')
@section('title','Overview PM Bulanan')
@section('page-title','Overview PM Bulanan')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a class="text-sm font-semibold text-blue-600" href="{{ route('admin.pm.index',['year'=>$selectedYear,'month'=>$selectedMonth]) }}">← Dashboard Preventive Maintenance</a>
        <h2 class="mt-2 font-heading text-2xl font-bold text-slate-900">Laporan Detail Preventive Maintenance Bulanan</h2>
        <p class="mt-1 text-sm text-slate-500">Perbandingan hasil setiap minggu dan riwayat seluruh pelaksanaan monitoring.</p>
    </div>
</div>
<form method="GET" action="{{ route('admin.pm.monthly.index') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <label class="block text-xs font-semibold text-slate-600">Tahun
        <select name="year" class="mt-1 block rounded-lg border-slate-300 text-sm">
            @for($year=now()->year+1;$year>=2024;$year--)
                <option value="{{ $year }}" @selected($year===$selectedYear)>{{ $year }}</option>
            @endfor
        </select>
    </label>
    <label class="block text-xs font-semibold text-slate-600">Bulan
        <select name="month" class="mt-1 block rounded-lg border-slate-300 text-sm">
            @for($month=1;$month<=12;$month++)
                <option value="{{ $month }}" @selected($month===$selectedMonth)>{{ \Carbon\Carbon::create(2026,$month,1)->locale('id')->translatedFormat('F') }}</option>
            @endfor
        </select>
    </label>
    <label class="block text-xs font-semibold text-slate-600">Sistem
        <select name="system" class="mt-1 block min-w-44 rounded-lg border-slate-300 text-sm">
            <option value="">Semua Sistem</option>
            @foreach($systems as $system)
                <option value="{{ $system->id }}" @selected($selectedSystemId===$system->id)>{{ $system->name }}</option>
            @endforeach
        </select>
    </label>
    <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
        <i class="fa-solid fa-filter mr-1"></i>Tampilkan
    </button>
</form>
@include('admin.pm.monthly-matrix-partial')

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-5">
        <div>
            <h3 class="text-lg font-bold text-slate-900"><i class="fa-solid fa-clipboard-list mr-2 text-blue-600"></i>Detail Pelaksanaan Bulanan</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $runs->total() }} pelaksanaan dalam periode ini. Semua run ditampilkan, termasuk beberapa pemeriksaan dalam minggu yang sama.</p>
        </div>
        <span class="text-xs text-slate-500">Data historis tidak diubah</span>
    </div>
    @php($visibleCodes=$monthlyOverview['rows']->pluck('system.code')->all())
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1120px] text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3">Waktu Monitoring</th>
                    <th class="px-4 py-3">Sistem</th>
                    @foreach(\App\Services\PmMonitoringOverviewService::COLUMNS as $column)
                        <th class="px-3 py-3 text-center">{{ $column['title'] }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-center">Dokumen</th>
                    <th class="px-4 py-3">Pelaksana</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($runs as $run)
                @foreach($run->checks->whereIn('system_code',$visibleCodes)->groupBy('system_code') as $code=>$systemChecks)
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                        <span class="block font-semibold">{{ $run->started_at?->timezone('Asia/Jakarta')->format('d M Y') }}</span>
                        <span class="text-xs text-slate-500">{{ $run->started_at?->timezone('Asia/Jakarta')->format('H:i') }} WIB · Run #{{ $run->id }}</span>
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $systemChecks->first()?->system_name }}</td>
                    @foreach(\App\Services\PmMonitoringOverviewService::COLUMNS as $column)
                        @php($matches=$systemChecks->filter(fn($check)=>in_array($check->parameter_code,$column['codes'],true)))
                        @php($hasClosed=$matches->isNotEmpty() && $matches->contains(fn($c)=>$c->status!=='pass') && $matches->every(fn($c)=>$c->status==='pass'||$c->correctiveAction?->status==='resolved'))
                        @php($hasFail=$matches->contains('status','fail'))
                        @php($hasWarning=$matches->contains('status','warning'))
                        @php($hasNA=$matches->contains(fn($c)=>in_array($c->status,['unavailable','pending'],true)))
                        <td class="px-3 py-3 text-center">
                            @if($matches->isEmpty())
                                <span class="text-slate-400">—</span>
                            @elseif($hasClosed)
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Closed</span>
                            @elseif($hasFail)
                                <span class="font-semibold text-red-600">FAIL</span>
                            @elseif($hasWarning)
                                <span class="font-semibold text-amber-700">WARNING</span>
                            @elseif($hasNA)
                                <span class="text-xs font-semibold text-slate-500">N/A</span>
                            @else
                                <span class="font-bold text-emerald-600">✓</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-center">
                        @php($review=$run->review_status)
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $review==='finalized'?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700' }}">
                            {{ $review==='finalized'?'Final':($review==='approved'?'Disetujui':($review==='under_review'?'Review':'Draft')) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $run->initiator?->name ?? 'Scheduler' }}</td>
                    <td class="px-4 py-3">
                        <a class="font-semibold text-blue-600 hover:underline" href="{{ route('admin.pm.runs.show',$run) }}">Detail <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a>
                    </td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="10" class="p-8 text-center text-slate-500">Belum ada pelaksanaan PM untuk periode dan sistem yang dipilih.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-4">{{ $runs->links() }}</div>
    <p class="px-5 pb-4 text-xs text-slate-500">Closed pada kolom detail menunjukkan corrective telah diselesaikan, bukan hasil PASS otomatis. Klik Detail untuk melihat catatan dan bukti pemeriksaan.</p>
</div>
@endsection
