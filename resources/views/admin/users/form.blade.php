@extends('layouts.admin')
@section('page-title',$user->exists?'Edit Pengguna':'Tambah Pengguna')
@section('content')
<div class="max-w-2xl"><a href="{{ route('admin.users.index') }}" class="text-blue-600">← Kembali</a><h2 class="my-4 text-2xl font-bold">{{ $user->exists?'Edit Pengguna':'Tambah Pengguna' }}</h2>
@if($errors->any())<div class="mb-4 rounded bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="space-y-5 rounded-xl bg-white p-6" method="POST" action="{{ $user->exists?route('admin.users.update',$user):route('admin.users.store') }}">@csrf @if($user->exists)@method('PUT')@endif
<div><label class="block">Nama</label><input class="w-full rounded-lg border-slate-300" name="name" value="{{ old('name',$user->name) }}" required></div>
<div><label class="block">Email</label><input class="w-full rounded-lg border-slate-300" name="email" type="email" value="{{ old('email',$user->email) }}" required></div>
<div><label class="block">Password {{ $user->exists?'(kosongkan jika tidak diubah)':'' }}</label><input class="w-full rounded-lg border-slate-300" type="password" name="password" {{ $user->exists?'':'required' }}><p class="text-xs text-slate-500">Minimal 8 karakter; huruf besar, kecil, angka dan simbol.</p></div>
<div><label class="block">Ulangi Password</label><input class="w-full rounded-lg border-slate-300" type="password" name="password_confirmation" {{ $user->exists?'':'required' }}></div>
<fieldset><legend class="mb-2 font-bold">Role</legend><div class="grid gap-3 sm:grid-cols-2">@foreach($roles as $role)<label class="rounded-lg border p-3"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id,old('roles',$user->roles->pluck('id')->all())))> {{ $role->name }}<small class="block text-slate-500">{{ $role->description }}</small></label>@endforeach</div></fieldset>
<button class="rounded-lg bg-blue-600 px-5 py-2 text-white">Simpan</button></form></div>
@endsection