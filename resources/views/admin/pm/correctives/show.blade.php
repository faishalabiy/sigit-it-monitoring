@extends('layouts.admin')
@section('title','Detail Tindak Lanjut PM')
@section('page-title','Corrective Maintenance')
@section('content')
<div class="max-w-5xl">
 <a class="text-sm font-semibold text-blue-600" href="{{ route('admin.pm.correctives.index') }}">← Daftar Tindak Lanjut</a>
 <div class="my-4 flex flex-wrap items-start justify-between gap-3"><div>
 <h2 class="text-2xl font-bold">Corrective CA-{{ str_pad($action->id,5,'0',STR_PAD_LEFT) }}</h2>
 <p class="mt-1 text-sm text-slate-500">{{ $action->check->system_name ?? 'Sistem' }} · {{ $action->check->parameter_name ?? 'Parameter' }} · PM #{{ $action->pm_run_id }}</p>
 </div><a class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold" href="{{ route('admin.pm.runs.show',$action->run) }}">Lihat Laporan PM</a></div>
 <div class="mb-5 rounded-xl border border-slate-200 bg-white p-5">
 <p class="text-xs font-semibold uppercase text-slate-500">Temuan Terdeteksi</p>
 <p class="mt-2 text-sm text-slate-900">{{ $action->finding }}</p>
 <p class="mt-2 text-xs text-slate-500">Nilai pemeriksaan: {{ $action->check->value ?? 'Tidak tersedia' }} {{ $action->check->unit }} · Status awal {{ strtoupper($action->check->status) }}</p>
 <p class="mt-3 text-xs font-semibold uppercase text-slate-500">Rekomendasi Awal</p>
 <p class="mt-1 text-sm">{{ $action->recommendation }}</p></div>
 @if($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <form method="POST" action="{{ route('admin.pm.correctives.update',$action) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
 @csrf @method('PATCH')
 <h3 class="mb-4 font-bold">Penugasan dan Penyelesaian</h3>
 <div class="grid gap-4 md:grid-cols-2">
 <div><label class="mb-1 block text-sm font-semibold">Status</label><select name="status" class="w-full rounded-lg border-slate-300 text-sm" @disabled(!auth()->user()->hasPermission('pm.manage'))>
 @foreach(['open'=>'Open','in_progress'=>'Dalam Proses','resolved'=>'Selesai'] as $key=>$val)<option value="{{ $key }}" @selected(old('status',$action->status)===$key)>{{ $val }}</option>@endforeach</select></div>
 <div><label class="mb-1 block text-sm font-semibold">Prioritas</label><select name="priority" class="w-full rounded-lg border-slate-300 text-sm" @disabled(!auth()->user()->hasPermission('pm.manage'))><option value="medium" @selected(old('priority',$action->priority)==='medium')>Sedang</option><option value="high" @selected(old('priority',$action->priority)==='high')>Tinggi</option></select></div>
 <div><label class="mb-1 block text-sm font-semibold">PIC</label><select name="assigned_to" class="w-full rounded-lg border-slate-300 text-sm" @disabled(!auth()->user()->hasPermission('pm.manage'))><option value="">Belum ditentukan</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((string)old('assigned_to',$action->assigned_to)===(string)$person->id)>{{ $person->name }}</option>@endforeach</select></div>
 <div><label class="mb-1 block text-sm font-semibold">Target Penyelesaian</label><input type="date" name="due_on" value="{{ old('due_on',$action->due_on?->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 text-sm" @disabled(!auth()->user()->hasPermission('pm.manage'))></div>
 <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Rencana / Rekomendasi Tindakan</label><textarea name="recommendation" rows="3" maxlength="3000" class="w-full rounded-lg border-slate-300 text-sm" @disabled(!auth()->user()->hasPermission('pm.manage'))>{{ old('recommendation',$action->recommendation) }}</textarea></div>
 <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Catatan Penyelesaian dan Bukti</label><textarea name="resolution_notes" rows="3" maxlength="3000" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Tuliskan tindakan yang dilakukan, hasil verifikasi, dan referensi bukti. Wajib jika status Selesai." @disabled(!auth()->user()->hasPermission('pm.manage'))>{{ old('resolution_notes',$action->resolution_notes) }}</textarea></div></div>
 <p class="mt-4 text-xs text-slate-500">Status Selesai hanya berarti tindak lanjut sudah dinyatakan selesai oleh petugas. Nilai hasil pemeriksaan historis tidak berubah. Verifikasi ulang bisa dilakukan melalui PM berikutnya.</p>
 @if(auth()->user()->hasPermission('pm.manage'))<button class="mt-4 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Simpan Tindak Lanjut</button>@endif
 </form>
 <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
 <h3 class="mb-4 font-bold">Histori Perubahan</h3>
 @forelse($action->events as $event)
 <div class="mb-3 border-b border-slate-100 pb-3 text-sm">
 <div class="flex flex-wrap justify-between gap-2"><strong>{{ ucfirst(str_replace('_',' ',$event->event)) }}</strong><span class="text-xs text-slate-500">{{ $event->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB — {{ $event->actor->name ?? 'Sistem' }}</span></div>
 <p class="mt-1 text-xs text-slate-500">Perubahan: {{ implode(', ',array_keys($event->changes??[])) }}</p></div>
 @empty<p class="text-sm text-slate-500">Belum ada perubahan.</p>@endforelse
 </div>
</div>
@endsection