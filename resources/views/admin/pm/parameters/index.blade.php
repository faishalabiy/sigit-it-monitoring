@extends('layouts.admin')
@section('title','Template Parameter PM')
@section('page-title','Template Parameter')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><a class="text-sm font-medium text-blue-600" href="{{ route('admin.pm.index') }}">← Preventive Maintenance</a><h2 class="mt-2 text-2xl font-bold">Template Parameter</h2><p class="text-sm text-slate-500">Parameter reusable untuk berbagai sistem TI.</p></div>
    @if(auth()->user()->hasPermission('pm.manage'))<a href="{{ route('admin.pm.parameters.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i> Tambah Parameter</a>@endif
</div>
<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
    <p class="font-semibold"><i class="fa-solid fa-circle-info mr-1"></i> Parameter standar untuk Preventive Maintenance Sistem Informasi</p>
    <p class="mt-1">Template di bawah merupakan acuan awal dan dapat diubah sesuai kebutuhan sistem. <b>Nilai Harapan</b> untuk hasil berupa status, contohnya HTTP <b>200</b> atau layanan <b>running</b>. <b>WARNING / FAIL</b> untuk pengukuran angka, seperti CPU, RAM, response time, atau masa berlaku SSL.</p>
    <p class="mt-2 text-xs">Parameter belum melakukan pengukuran otomatis sebelum engine PM diaktifkan pada tahap berikutnya. Pilih parameter yang sesuai saat mendaftarkan sistem pada Master Sistem.</p>
</div>
<form method="GET" class="mb-4 flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Cari parameter..." class="w-full max-w-md rounded-lg border-slate-300 text-sm"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Cari</button></form>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">Parameter</th><th class="p-4">Kategori</th><th class="p-4">Metode</th><th class="p-4">Dipakai</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead><tbody>
@forelse($parameters as $parameter)
<tr class="border-t border-slate-100"><td class="p-4"><span class="block font-semibold">{{ $parameter->name }}</span><span class="text-xs text-slate-500">{{ $parameter->code }}</span></td>
<td class="p-4">{{ \App\Models\PmParameterTemplate::CATEGORIES[$parameter->category] ?? $parameter->category }}</td>
<td class="p-4">{{ \App\Models\PmParameterTemplate::METHODS[$parameter->method] ?? $parameter->method }}</td>
<td class="p-4">{{ $parameter->systems_count }} sistem</td>
<td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $parameter->is_active?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500' }}">{{ $parameter->is_active?'Aktif':'Nonaktif' }}</span></td>
<td class="p-4">@if(auth()->user()->hasPermission('pm.manage'))<div class="flex gap-3"><a class="font-semibold text-blue-600" href="{{ route('admin.pm.parameters.edit',$parameter) }}">Edit</a><form method="POST" action="{{ route('admin.pm.parameters.destroy',$parameter) }}" onsubmit="return confirm('Hapus template parameter?')">@csrf @method('DELETE')<button class="text-red-600">Hapus</button></form></div>@else—@endif</td></tr>
@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada template parameter.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $parameters->links() }}</div>
@endsection