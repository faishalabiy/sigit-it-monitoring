@extends('layouts.admin')
@section('title',$schedule->exists?'Edit Jadwal PM':'Buat Jadwal PM')
@section('page-title','Jadwal Preventive Maintenance')
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.pm.schedules.index') }}" class="text-sm font-semibold text-blue-600">← Kembali ke Jadwal</a>
    <h2 class="mb-5 mt-2 text-2xl font-bold">{{ $schedule->exists?'Edit Jadwal PM':'Buat Jadwal PM' }}</h2>
    @if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><strong>Periksa kembali:</strong><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $schedule->exists ? route('admin.pm.schedules.update',$schedule) : route('admin.pm.schedules.store') }}" class="space-y-6">
        @csrf @if($schedule->exists) @method('PUT') @endif
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold">Informasi Jadwal</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold">Kode Jadwal *</label><input name="code" required maxlength="50" value="{{ old('code',$schedule->code) }}" placeholder="PM-BULANAN-001" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nama Jadwal *</label><input name="name" required maxlength="200" value="{{ old('name',$schedule->name) }}" placeholder="Pemeriksaan Bulanan Aplikasi" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Frekuensi *</label><select name="frequency" required class="w-full rounded-lg border-slate-300 text-sm">@foreach(\App\Models\PmSchedule::FREQUENCIES as $value=>$label)<option value="{{ $value }}" @selected(old('frequency',$schedule->frequency ?? 'monthly')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Jam Pelaksanaan *</label><input type="time" name="run_time" value="{{ old('run_time',$schedule->run_time ? substr($schedule->run_time,0,5) : '08:00') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Hari Mingguan (jika mingguan)</label><select name="day_of_week" class="w-full rounded-lg border-slate-300 text-sm"><option value="">Tidak berlaku</option>@foreach([0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'] as $value=>$label)<option value="{{ $value }}" @selected((string)old('day_of_week',$schedule->day_of_week)===(string)$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Tanggal Bulanan (jika bulanan)</label><select name="day_of_month" class="w-full rounded-lg border-slate-300 text-sm"><option value="">Tidak berlaku</option>@for($day=1;$day<=28;$day++)<option value="{{ $day }}" @selected((string)old('day_of_month',$schedule->day_of_month)===(string)$day)>Tanggal {{ $day }}</option>@endfor</select><p class="mt-1 text-xs text-slate-500">Tanggal 1–28 agar berlaku setiap bulan.</p></div>
                <div><label class="mb-1 block text-sm font-semibold">Mulai Berlaku *</label><input type="date" name="starts_on" value="{{ old('starts_on',$schedule->starts_on?->format('Y-m-d') ?? now()->toDateString()) }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Zona Waktu *</label><select name="timezone" class="w-full rounded-lg border-slate-300 text-sm">@foreach(['Asia/Jakarta'=>'WIB — Asia/Jakarta','Asia/Makassar'=>'WITA — Asia/Makassar','Asia/Jayapura'=>'WIT — Asia/Jayapura','UTC'=>'UTC'] as $value=>$label)<option value="{{ $value }}" @selected(old('timezone',$schedule->timezone ?? 'Asia/Jakarta')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">PIC Pelaksanaan</label><select name="assigned_to" class="w-full rounded-lg border-slate-300 text-sm"><option value="">Belum ditetapkan</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((string)old('assigned_to',$schedule->assigned_to)===(string)$person->id)>{{ $person->name }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Status *</label><select name="status" class="w-full rounded-lg border-slate-300 text-sm"><option value="draft" @selected(old('status',$schedule->status ?? 'draft')==='draft')>Draft</option><option value="ready" @selected(old('status',$schedule->status)==='ready')>Siap (otomatis + manual)</option></select></div>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Catatan / Lingkup</label><textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('description',$schedule->description) }}</textarea></div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold">Target Pemeriksaan</h3><p class="mb-4 text-sm text-slate-500">Pilih satu atau lebih sistem. Untuk status <b>Siap</b>, tiap target wajib memiliki minimal satu parameter aktif.</p>
            @php $selectedSystems=old('systems',$schedule->exists?$schedule->systems->pluck('id')->all():[]); @endphp
            <div class="grid gap-3 md:grid-cols-2">
                @forelse($systems as $system)
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:bg-blue-50">
                    <input type="checkbox" name="systems[]" value="{{ $system->id }}" @checked(in_array($system->id,$selectedSystems)) class="mt-1 rounded border-slate-300 text-blue-600">
                    <span><strong class="block text-sm">{{ $system->name }}</strong><small class="text-slate-500">{{ $system->code }} · {{ $system->parameters_count }} parameter aktif</small></span>
                </label>
                @empty<p class="text-sm text-amber-700">Belum ada sistem aktif. Buat master sistem terlebih dahulu.</p>@endforelse
            </div>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <i class="fa-solid fa-circle-info mr-1"></i> Status <b>Siap</b> mengizinkan penjadwalan otomatis dan tombol Jalankan Sekarang. Hanya HTTP, Response Time, serta SSL yang dapat diperiksa tanpa agen tambahan.
        </div>
        <div class="flex flex-wrap gap-3"><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700">Simpan Jadwal</button><a href="{{ route('admin.pm.schedules.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold">Batal</a></div>
    </form>
</div>
@endsection