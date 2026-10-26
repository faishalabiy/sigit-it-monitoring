@extends('layouts.admin')
@section('title',$system->exists?'Edit Sistem PM':'Tambah Sistem PM')
@section('page-title','Master Sistem PM')
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.pm.systems.index') }}" class="text-sm font-semibold text-blue-600">← Kembali ke Master Sistem</a>
    <h2 class="mb-5 mt-2 text-2xl font-bold text-slate-950">{{ $system->exists ? 'Edit Sistem TI' : 'Tambah Sistem TI' }}</h2>
    @if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><strong>Periksa kembali isian:</strong><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $system->exists ? route('admin.pm.systems.update',$system) : route('admin.pm.systems.store') }}" class="space-y-6">
        @csrf @if($system->exists) @method('PUT') @endif
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-5 font-bold text-slate-900">Identitas Sistem</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold">Kode Sistem *</label><input name="code" value="{{ old('code',$system->code) }}" placeholder="SYS-001" required maxlength="50" class="w-full rounded-lg border-slate-300 text-sm"><p class="mt-1 text-xs text-slate-500">Huruf, angka, tanda hubung, underscore</p></div>
                <div><label class="mb-1 block text-sm font-semibold">Nama Sistem *</label><input name="name" value="{{ old('name',$system->name) }}" placeholder="Contoh: SIGIT" required maxlength="200" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Jenis Sistem *</label><select name="type" required class="w-full rounded-lg border-slate-300 text-sm">@foreach(\App\Models\PmSystem::TYPES as $value=>$label)<option value="{{ $value }}" @selected(old('type',$system->type ?? 'application')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Environment *</label><select name="environment" class="w-full rounded-lg border-slate-300 text-sm">@foreach(['production'=>'Production','staging'=>'Staging','development'=>'Development','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(old('environment',$system->environment ?? 'production')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Kritikalitas *</label><select name="criticality" class="w-full rounded-lg border-slate-300 text-sm">@foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $value=>$label)<option value="{{ $value }}" @selected(old('criticality',$system->criticality ?? 'medium')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Status *</label><select name="status" class="w-full rounded-lg border-slate-300 text-sm"><option value="active" @selected(old('status',$system->status ?? 'active')==='active')>Aktif</option><option value="inactive" @selected(old('status',$system->status)==='inactive')>Nonaktif</option></select></div>
                <div><label class="mb-1 block text-sm font-semibold">Alamat Sistem (URL)</label><input type="url" name="target_url" value="{{ old('target_url',$system->target_url) }}" placeholder="https://aplikasi.example.com" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Hostname / IP</label><input name="host" value="{{ old('host',$system->host) }}" placeholder="server.example.local" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Penanggung Jawab</label><select name="owner_id" class="w-full rounded-lg border-slate-300 text-sm"><option value="">— Pilih PIC —</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected((string)old('owner_id',$system->owner_id)===(string)$owner->id)>{{ $owner->name }}</option>@endforeach</select></div>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Deskripsi</label><textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('description',$system->description) }}</textarea></div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 font-bold text-slate-900">Parameter Pemeriksaan</h3>
            <p class="mb-5 text-sm text-slate-500">Pilih template parameter yang berlaku untuk sistem ini. Parameter baru dibuat melalui menu Template Parameter.</p>
            @php $selectedParameters=old('parameters',$system->exists ? $system->parameters->pluck('id')->all() : []); @endphp
            <div class="grid gap-3 md:grid-cols-2">
                @forelse($parameters as $parameter)
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 transition hover:bg-blue-50">
                    <input type="checkbox" name="parameters[]" value="{{ $parameter->id }}" @checked(in_array($parameter->id,$selectedParameters)) class="mt-1 rounded border-slate-300 text-blue-600">
                    <span><span class="block font-semibold text-sm text-slate-900">{{ $parameter->name }}</span><span class="text-xs text-slate-500">{{ $parameter->code }} · {{ \App\Models\PmParameterTemplate::METHODS[$parameter->method] ?? $parameter->method }}</span></span>
                </label>
                @empty<p class="text-sm text-amber-700">Belum ada template parameter aktif. Tambahkan terlebih dahulu.</p>@endforelse
            </div>
            <p class="mt-4 text-xs text-slate-500">Tahap 1: seluruh parameter terpilih dianggap wajib. Nilai pemeriksaan akan dicatat pada modul eksekusi.</p>
        </div>
        <div class="flex flex-wrap gap-3"><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700">Simpan Sistem</button><a href="{{ route('admin.pm.systems.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold">Batal</a></div>
    </form>
</div>
@endsection