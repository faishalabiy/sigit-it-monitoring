@extends('layouts.admin')
@section('title','Master Sistem PM')
@section('page-title','Master Sistem PM')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><a class="text-sm font-medium text-blue-600" href="{{ route('admin.pm.index') }}">← Preventive Maintenance</a><h2 class="mt-2 text-2xl font-bold">Master Sistem TI</h2><p class="text-sm text-slate-500">Daftar sistem yang dapat menjadi target preventive maintenance.</p></div>
    @if(auth()->user()->hasPermission('pm.manage'))
    <a href="{{ route('admin.pm.systems.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Sistem</a>
    @endif
</div>
<form method="GET" class="mb-4 flex gap-2">
    <input name="search" value="{{ request('search') }}" placeholder="Cari nama, kode atau host..." class="w-full max-w-md rounded-lg border-slate-300 text-sm">
    <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Cari</button>
</form>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm">
<thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">Sistem</th><th class="p-4">Jenis</th><th class="p-4">Prioritas</th><th class="p-4">Parameter</th><th class="p-4">Jadwal</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead>
<tbody>
@forelse($systems as $system)
<tr class="border-t border-slate-100">
    <td class="p-4"><span class="block font-semibold text-slate-900">{{ $system->name }}</span><span class="text-xs text-slate-500">{{ $system->code }} · {{ $system->environment }}</span></td>
    <td class="p-4">{{ \App\Models\PmSystem::TYPES[$system->type] ?? $system->type }}</td>
    <td class="p-4">{{ ucfirst($system->criticality) }}</td>
    <td class="p-4">{{ $system->parameters_count }}</td><td class="p-4">{{ $system->schedules_count }}</td>
    <td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $system->status==='active' ? 'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500' }}">{{ $system->status==='active'?'Aktif':'Nonaktif' }}</span></td>
    <td class="p-4">@if(auth()->user()->hasPermission('pm.manage'))<div class="flex gap-3"><a class="font-semibold text-blue-600" href="{{ route('admin.pm.systems.edit',$system) }}">Edit</a>
    <form method="POST" action="{{ route('admin.pm.systems.destroy',$system) }}" onsubmit="return confirm('Hapus master sistem ini?')">@csrf @method('DELETE')<button class="text-red-600">Hapus</button></form></div>@else<span class="text-slate-400">—</span>@endif</td>
</tr>
@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">Belum ada sistem terdaftar.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-4">{{ $systems->links() }}</div>
@endsection