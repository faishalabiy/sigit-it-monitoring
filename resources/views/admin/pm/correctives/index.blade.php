@extends('layouts.admin')
@section('title','Tindak Lanjut PM')
@section('page-title','Tindak Lanjut PM')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
 <div><a class="text-sm text-blue-600" href="{{ route('admin.pm.index') }}">← Dashboard PM</a>
 <h2 class="mt-2 text-2xl font-bold">Tindak Lanjut / Corrective Maintenance</h2>
 <p class="text-sm text-slate-500">Temuan WARNING dan FAIL otomatis membuat tiket; item belum tersedia dapat dibuat secara manual dari laporan.</p></div>
 <a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold" href="{{ route('admin.pm.runs.index') }}">Riwayat Pemeriksaan</a>
</div>
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
 <div><label class="mb-1 block text-xs font-semibold">Status</label>
 <select name="status" class="rounded-lg border-slate-300 text-sm"><option value="">Semua status</option>
 @foreach(['open'=>'Open','in_progress'=>'Dalam Proses','resolved'=>'Selesai'] as $key=>$value)
 <option value="{{ $key }}" @selected(request('status')===$key)>{{ $value }}</option>@endforeach</select></div>
 <div><label class="mb-1 block text-xs font-semibold">Prioritas</label>
 <select name="priority" class="rounded-lg border-slate-300 text-sm"><option value="">Semua prioritas</option><option value="high" @selected(request('priority')==='high')>Tinggi</option><option value="medium" @selected(request('priority')==='medium')>Sedang</option></select></div>
 <button class="rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white">Terapkan Filter</button>
</form>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500">
<tr><th class="p-4">Temuan / Sistem</th><th class="p-4">Prioritas</th><th class="p-4">PIC</th><th class="p-4">Tenggat</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead>
<tbody>@forelse($actions as $action)
<tr class="border-t border-slate-100">
 <td class="p-4"><span class="block max-w-lg font-semibold">{{ $action->check->system_name ?? 'Sistem' }} — {{ $action->check->parameter_name ?? 'Parameter' }}</span><span class="block text-xs text-slate-500">CA-{{ str_pad($action->id,5,'0',STR_PAD_LEFT) }} · PM #{{ $action->pm_run_id }}</span></td>
 <td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $action->priority==='high'?'bg-red-50 text-red-700':'bg-amber-50 text-amber-800' }}">{{ $action->priority==='high'?'Tinggi':'Sedang' }}</span></td>
 <td class="p-4">{{ $action->assignee->name ?? 'Belum ditentukan' }}</td>
 <td class="p-4">{{ $action->due_on?->format('d M Y') ?? 'Belum ditentukan' }} @if($action->due_on && $action->due_on->lt(today()) && $action->status!=='resolved')<span class="block text-xs font-semibold text-red-600">Lewat Tenggat</span>@endif</td>
 <td class="p-4">{{ ['open'=>'Open','in_progress'=>'Dalam Proses','resolved'=>'Selesai'][$action->status] ?? $action->status }}</td>
 <td class="p-4"><a class="font-semibold text-blue-600" href="{{ route('admin.pm.correctives.show',$action) }}">Detail →</a></td>
</tr>
@empty<tr><td class="p-6 text-center text-slate-500" colspan="6">Belum ada temuan corrective. Tindakan akan muncul setelah pemeriksaan WARNING atau FAIL.</td></tr>@endforelse</tbody></table>
</div>
<div class="mt-4">{{ $actions->links() }}</div>
@endsection