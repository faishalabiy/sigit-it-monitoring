@extends('layouts.public')

@section('title', 'Pengembalian Berhasil')

@section('nav')
    <a href="{{ route('public.landing') }}"
       class="inline-flex items-center gap-2 text-sm text-ink-soft hover:text-ink px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
        <i class="fas fa-arrow-left text-xs"></i>Kembali ke Beranda
    </a>
@endsection

@section('content')

@php
    $condMap = [
        'good'    => ['label' => 'Baik',         'icon' => 'fa-circle-check',         'color' => 'text-emerald', 'bg' => 'bg-emerald-light'],
        'fair'    => ['label' => 'Cukup',         'icon' => 'fa-circle-minus',         'color' => 'text-amber',   'bg' => 'bg-amber/10'],
        'poor'    => ['label' => 'Rusak Ringan',  'icon' => 'fa-triangle-exclamation', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50'],
        'damaged' => ['label' => 'Rusak',         'icon' => 'fa-circle-xmark',         'color' => 'text-danger',  'bg' => 'bg-danger-light'],
    ];
    $cond = $condMap[$borrowing->return_condition] ?? $condMap['good'];
@endphp

<div class="min-h-[calc(100vh-128px)] flex items-center justify-center px-6 py-16">
    <div class="bg-surface border border-border rounded-2xl shadow-lift w-full max-w-md p-10 text-center animate-fadeup">

        {{-- Icon --}}
        <div class="w-20 h-20 rounded-full bg-emerald-light flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-hands-clapping text-emerald text-4xl"></i>
        </div>

        <h1 class="font-display font-bold text-2xl text-ink mb-2">Terima Kasih!</h1>
        <p class="text-sm text-ink-soft leading-relaxed mb-8">
            Pengembalian aset berhasil dicatat.<br>
            Email konfirmasi telah dikirim ke
            <strong class="text-ink">{{ $borrowing->borrower_email }}</strong>.
        </p>

        {{-- Info grid --}}
        <div class="grid grid-cols-2 gap-3 text-left mb-8">

            <div class="bg-s2 rounded-xl px-4 py-3">
                <div class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1">
                    <i class="fas fa-box-archive text-[9px]"></i>Aset
                </div>
                <div class="text-sm font-medium text-ink">{{ $borrowing->asset->name }}</div>
            </div>

            <div class="bg-s2 rounded-xl px-4 py-3">
                <div class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1">
                    <i class="fas fa-barcode text-[9px]"></i>Kode Pinjam
                </div>
                <div class="text-sm font-mono font-medium text-ink">{{ $borrowing->borrow_code }}</div>
            </div>

            <div class="bg-s2 rounded-xl px-4 py-3">
                <div class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1">
                    <i class="fas fa-calendar-check text-[9px]"></i>Tanggal Kembali
                </div>
                <div class="text-sm font-medium text-ink">{{ $borrowing->actual_return_date->format('d M Y') }}</div>
            </div>

            <div class="bg-s2 rounded-xl px-4 py-3">
                <div class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1">
                    <i class="fas fa-clipboard-check text-[9px]"></i>Kondisi Aset
                </div>
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full {{ $cond['bg'] }} {{ $cond['color'] }}">
                        <i class="fas {{ $cond['icon'] }} text-[10px]"></i>
                        {{ $cond['label'] }}
                    </span>
                </div>
            </div>

        </div>

        {{-- Action --}}
        <a href="{{ route('public.landing') }}"
           class="inline-flex items-center justify-center gap-2 bg-emerald hover:bg-emerald/90 text-white font-semibold text-sm px-8 py-3 rounded-xl transition-all duration-200 hover:shadow-gbtng w-full sm:w-auto">
            <i class="fas fa-house"></i>Kembali ke Beranda
        </a>

    </div>
</div>

@endsection
