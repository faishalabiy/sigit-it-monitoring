@extends('layouts.admin')
@section('title','Monitoring Bukti Backup')
@section('page-title','Monitoring Backup')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
 <div><a href="{{ route('admin.pm.index') }}" class="text-sm text-blue-600">← Dashboard PM</a><h2 class="mt-2 text-2xl font-bold">Monitoring Bukti Backup Sistem TI</h2>
 <p class="mt-1 text-sm text-slate-500">Memantau keberadaan arsip Proxmox di NAS Synology serta sumber backup aplikasi lain yang terdaftar.</p></div>
</div>
<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
 <i class="fa-solid fa-circle-info mr-1"></i> Untuk PADUKA dan SIPO yang menggunakan VM bersama, PASS berarti ditemukan arsip vzdump VM tersebut di NAS Synology. Hasil ini bukan bukti uji restore. Sumber yang tidak dapat dibaca berstatus N/A.
</div>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600">
<tr><th class="p-4">Aplikasi</th><th class="p-4">Sumber Backup</th><th class="p-4">Parameter</th><th class="p-4">Hasil</th><th class="p-4">Keterangan</th><th class="p-4">Pengaturan</th></tr></thead><tbody>
@forelse($systems as $system)
<tr class="border-t border-slate-100">
<td class="p-4 font-semibold">{{ $system['name'] }}<span class="block text-xs font-normal text-slate-500">{{ $system['code'] }}</span></td>
<td class="p-4"><span class="block font-semibold">{{ $system['source'] }}</span><span class="text-xs {{ $system['configured']?'text-emerald-700':'text-amber-700' }}">{{ $system['configured']?'Sumber Terdaftar':'Belum Terhubung' }}</span></td>
<td class="p-4">{{ $system['parameter'] ?? 'Belum Dipilih' }}</td>
<td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $system['result']['status']==='pass'?'bg-emerald-50 text-emerald-700':($system['result']['status']==='fail'?'bg-red-50 text-red-700':'bg-amber-50 text-amber-800') }}">{{ strtoupper($system['result']['status']) }}</span>
@if($system['result']['value']!==null)<span class="block mt-1 text-xs">{{ $system['result']['value'] }}{{ $system['unit']==='hari'?' hari':'' }}</span>@endif</td>
<td class="p-4 text-slate-600">{{ $system['result']['message'] }}</td>
<td class="p-4"><a class="font-semibold text-blue-600" href="{{ route('admin.pm.systems.edit',$system['id']) }}">Master Sistem →</a></td>
</tr>
@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada aplikasi aktif yang didaftarkan.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-5 rounded-xl border border-slate-200 bg-white p-5 text-sm">
 <p class="font-bold">Cara mengaktifkan pemantauan backup</p>
 <p class="mt-2 text-slate-600">Untuk PADUKA/SIPO, mount read-only NFS Synology <code>192.168.10.10:/volume1/Proxmox_Backup</code> ke SIGIT, arahkan <code>PM_PROXMOX_BACKUP_DIR</code> ke subfolder <code>dump</code>, lalu set <code>PM_PROXMOX_SHARED_VM_ID</code> sesuai VM yang benar-benar menjalankan keduanya. Jangan mengisi VM ID berdasarkan tebakan. Untuk aplikasi lain, tetap gunakan sumber backup terpisah.</p>
</div>
@endsection