@extends('layouts.admin')
@section('title','Jadwal PM')
@section('page-title','Jadwal Preventive Maintenance')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><a class="text-sm font-medium text-blue-600" href="{{ route('admin.pm.index') }}">← Preventive Maintenance</a><h2 class="mt-2 text-2xl font-bold">Jadwal PM</h2><p class="text-sm text-slate-500">Atur frekuensi pemeriksaan, target sistem dan PIC.</p></div>
    @if(auth()->user()->hasPermission('pm.manage'))<a href="{{ route('admin.pm.schedules.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i> Buat Jadwal</a>@endif
</div>
<div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"><i class="fa-solid fa-circle-info mr-1"></i> Jadwal berstatus <b>Siap</b> dapat berjalan otomatis sesuai waktu atau dijalankan langsung melalui tombol <b>Jalankan Sekarang</b>. <a class="font-semibold underline" href="{{ route('admin.pm.runs.index') }}">Lihat riwayat pemeriksaan</a>.</div>
@if($errors->any())
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">Jadwal</th><th class="p-4">Frekuensi</th><th class="p-4">Jam & Zona</th><th class="p-4">Target</th><th class="p-4">PIC</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead><tbody>
@forelse($schedules as $schedule)
<tr class="border-t border-slate-100">
<td class="p-4"><span class="block font-semibold">{{ $schedule->name }}</span><span class="text-xs text-slate-500">{{ $schedule->code }}</span></td>
<td class="p-4">{{ \App\Models\PmSchedule::FREQUENCIES[$schedule->frequency] ?? $schedule->frequency }} @if($schedule->frequency==='weekly') ({{ [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'][$schedule->day_of_week] ?? '-' }}) @elseif($schedule->frequency==='monthly') (tgl {{ $schedule->day_of_month }}) @endif</td>
<td class="p-4">{{ substr($schedule->run_time,0,5) }} · {{ $schedule->timezone }}</td><td class="p-4">{{ $schedule->systems_count }} sistem</td>
<td class="p-4">{{ $schedule->assignee->name ?? 'Belum ditetapkan' }}</td>
<td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $schedule->status==='ready'?'bg-blue-50 text-blue-700':'bg-slate-100 text-slate-600' }}">{{ $schedule->status==='ready'?'Siap':'Draft' }}</span></td>
<td class="p-4">
@if(auth()->user()->hasPermission('pm.manage'))
<div class="flex flex-wrap items-center gap-2">
    @if($schedule->status==='ready')
    <form method="POST" action="{{ route('admin.pm.schedules.run',$schedule) }}" onsubmit="return confirm('Jalankan monitoring sekarang untuk seluruh target pada jadwal ini?')">
        @csrf
        <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
            <i class="fa-solid fa-play"></i> Jalankan Sekarang
        </button>
    </form>
    @endif
    @if($schedule->runs->first())
    <a class="text-sm font-semibold text-emerald-700" href="{{ route('admin.pm.runs.show',$schedule->runs->first()) }}">Hasil Terakhir</a>
    @endif
    <a class="text-sm font-semibold text-blue-600" href="{{ route('admin.pm.schedules.edit',$schedule) }}">Edit</a>
    <form method="POST" action="{{ route('admin.pm.schedules.destroy',$schedule) }}" onsubmit="return confirm('Hapus jadwal PM?')">
        @csrf @method('DELETE')<button class="text-sm text-red-600">Hapus</button>
    </form>
</div>@else—@endif</td>
</tr>
@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">Belum ada jadwal PM.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $schedules->links() }}</div>
@endsection