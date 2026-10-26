@extends('layouts.admin')
@section('title','Notifikasi Preventive Maintenance')
@section('page-title','Notifikasi PM')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
 <div><a href="{{ route('admin.pm.index') }}" class="text-sm text-blue-600">← Dashboard PM</a>
 <h2 class="mt-2 text-2xl font-bold">Pusat Notifikasi PM</h2>
 <p class="mt-1 text-sm text-slate-500">Pengingat jadwal, hasil pemeriksaan FAIL, backup, serta corrective overdue.</p></div>
 <span class="rounded-full bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">{{ $unread }} belum dibaca</span>
</div>
@if(!config('pm_notifications.email_enabled') || in_array(config('mail.default'),['log','array'],true))
 <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
 <i class="fa-solid fa-circle-info mr-1"></i> Notifikasi di SIGIT sudah aktif. Pengiriman email ke inbox belum aktif karena SMTP belum dikonfigurasi/diizinkan. Notifikasi tetap tersimpan di halaman ini.</div>
@endif
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
@forelse($notifications as $notification)
<div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 p-5 {{ $notification->read_at?'':'bg-blue-50/40' }}">
 <div class="flex min-w-0 flex-1 items-start gap-3">
 <span class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $notification->level==='danger'?'bg-red-50 text-red-700':($notification->level==='warning'?'bg-amber-50 text-amber-700':'bg-blue-50 text-blue-700') }}">
 <i class="fa-solid {{ $notification->kind==='backup'?'fa-box-archive':($notification->kind==='pm_reminder'?'fa-calendar-days':'fa-triangle-exclamation') }}"></i></span>
 <div class="min-w-0"><p class="font-bold text-slate-900">{{ $notification->subject }}</p>
 <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
 <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB · Email: {{ ['disabled'=>'Belum diaktifkan','pending'=>'Dalam antrean','sent'=>'Terkirim','failed'=>'Gagal dikirim'][$notification->email_status] ?? $notification->email_status }}</p>
 @if($notification->url)<a href="{{ $notification->url }}" class="mt-2 inline-block text-sm font-semibold text-blue-600">Lihat Tindak Lanjut <i class="fa-solid fa-arrow-right ml-1"></i></a>@endif
 </div></div>
 @if(!$notification->read_at)
 <form action="{{ route('admin.pm.notifications.read',$notification) }}" method="POST">@csrf @method('PATCH')
 <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">Tandai Dibaca</button></form>
 @else<span class="text-xs text-slate-400">Sudah dibaca</span>@endif
</div>
@empty
<div class="p-10 text-center text-sm text-slate-500">Belum ada notifikasi untuk akun ini. Pemeriksaan berjalan melalui scheduler secara berkala.</div>
@endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection