@extends('layouts.admin')
@section('title',$parameter->exists?'Edit Template Parameter':'Tambah Template Parameter')
@section('page-title','Template Parameter')
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.pm.parameters.index') }}" class="text-sm font-semibold text-blue-600">← Kembali ke Template Parameter</a>
    <h2 class="mb-5 mt-2 text-2xl font-bold">{{ $parameter->exists?'Edit Template Parameter':'Tambah Template Parameter' }}</h2>
    @if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><strong>Periksa kembali:</strong><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
        <strong><i class="fa-solid fa-circle-info mr-1"></i> Cara mengisi:</strong>
        <p class="mt-1">1. <b>Kode & Nama</b> adalah identitas pemeriksaan. 2. <b>Metode</b> menentukan apa yang dicek. 3. <b>Nilai Harapan</b> digunakan untuk status teks (misalnya <b>200</b>, <b>connected</b>, <b>running</b>, atau <b>PASS</b>). 4. <b>Batas WARNING / FAIL</b> untuk metrik numerik. Keduanya tidak harus diisi bersamaan.</p>
        <p class="mt-2 text-xs">Contoh respons aplikasi: metode HTTP Status, Nilai Harapan 200. Contoh CPU: warning maksimum 80%, fail maksimum 90%. Contoh SSL: warning minimum 30 hari, fail minimum 7 hari.</p>
        <p class="mt-2 text-xs">Anda juga dapat memilih dan mengedit parameter standar yang sudah tersedia pada halaman daftar, tanpa membuat dari awal.</p>
    </div>
    <form method="POST" action="{{ $parameter->exists ? route('admin.pm.parameters.update',$parameter) : route('admin.pm.parameters.store') }}" class="space-y-6">
        @csrf @if($parameter->exists) @method('PUT') @endif
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-bold">Identitas & Metode</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold">Kode Parameter *</label><input name="code" required maxlength="50" value="{{ old('code',$parameter->code) }}" placeholder="HTTP_STATUS" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nama Parameter *</label><input name="name" required maxlength="200" value="{{ old('name',$parameter->name) }}" placeholder="Status HTTP aplikasi" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Kategori *</label><select name="category" class="w-full rounded-lg border-slate-300 text-sm">@foreach(\App\Models\PmParameterTemplate::CATEGORIES as $value=>$label)<option value="{{ $value }}" @selected(old('category',$parameter->category ?? 'availability')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Metode Pemeriksaan *</label><select name="method" class="w-full rounded-lg border-slate-300 text-sm">@foreach(\App\Models\PmParameterTemplate::METHODS as $value=>$label)<option value="{{ $value }}" @selected(old('method',$parameter->method ?? 'manual_check')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold">Satuan</label><input name="unit" maxlength="30" value="{{ old('unit',$parameter->unit) }}" placeholder="%, ms, hari" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nilai Harapan</label><input name="expected_value" maxlength="255" value="{{ old('expected_value',$parameter->expected_value) }}" placeholder="Contoh: 200, online, PASS" class="w-full rounded-lg border-slate-300 text-sm"></div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-2 font-bold">Batas Evaluasi Numeric</h3>
            <p class="mb-4 text-sm text-slate-500">Isi batas yang sesuai dengan arah risiko. Batas maksimum cocok untuk CPU/disk tinggi; batas minimum cocok untuk sisa SSL rendah. Kosongkan untuk parameter yang tidak numerik.</p>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach(['warning_min'=>'WARNING — minimum','warning_max'=>'WARNING — maksimum','failure_min'=>'FAIL — minimum','failure_max'=>'FAIL — maksimum'] as $field=>$label)
                <div><label class="mb-1 block text-sm font-semibold">{{ $label }}</label><input type="number" step="0.001" name="{{ $field }}" value="{{ old($field,$parameter->{$field}) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-slate-500">Contoh CPU: WARNING maksimum 80, FAIL maksimum 90. Contoh SSL: WARNING minimum 30, FAIL minimum 7. Pemeriksaan otomatis belum aktif pada tahap ini.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4"><label class="mb-1 block text-sm font-semibold">Deskripsi / Petunjuk Pemeriksaan</label><textarea name="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('description',$parameter->description) }}</textarea></div>
            <div><label class="mb-1 block text-sm font-semibold">Status Template</label><select name="is_active" class="w-full rounded-lg border-slate-300 text-sm"><option value="1" @selected((string)old('is_active',$parameter->exists ? (int)$parameter->is_active : 1)==='1')>Aktif</option><option value="0" @selected((string)old('is_active',$parameter->exists ? (int)$parameter->is_active : 1)==='0')>Nonaktif</option></select></div>
        </div>
        <div class="flex flex-wrap gap-3"><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700">Simpan Parameter</button><a href="{{ route('admin.pm.parameters.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold">Batal</a></div>
    </form>
</div>
@endsection