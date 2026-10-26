@extends('layouts.admin')
@section('page-title',$role->exists?'Edit Role':'Tambah Role')
@section('content')
<div class="max-w-2xl"><a class="text-blue-600" href="{{ route('admin.roles.index') }}">← Kembali</a><h2 class="my-4 text-2xl font-bold">{{ $role->exists?'Edit Role':'Tambah Role' }}</h2>
@if($errors->any())<div class="mb-4 rounded bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="space-y-5 rounded-xl bg-white p-6" method="POST" action="{{ $role->exists?route('admin.roles.update',$role):route('admin.roles.store') }}">@csrf @if($role->exists)@method('PUT')@endif
<div><label class="block">Nama Role</label><input class="w-full rounded-lg border-slate-300" name="name" value="{{ old('name',$role->name) }}" required></div>
<div><label class="block">Deskripsi</label><textarea class="w-full rounded-lg border-slate-300" name="description">{{ old('description',$role->description) }}</textarea></div>
<fieldset><legend class="mb-2 font-bold">Permissions</legend><div class="grid gap-3 sm:grid-cols-2">@foreach($permissions as $key=>$label)<label class="rounded-lg border p-3"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key,old('permissions',$role->permissions??[])))> {{ $label }}</label>@endforeach</div></fieldset>
<button class="rounded-lg bg-blue-600 px-5 py-2 text-white">Simpan</button></form></div>
@endsection