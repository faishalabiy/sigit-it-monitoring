@extends('layouts.public')

@section('title', 'Peminjaman Berhasil')

@section('nav')
    <a href="{{ route('public.landing') }}"
       class="inline-flex items-center gap-2 text-sm text-ink-soft hover:text-ink px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
        <i class="fas fa-arrow-left text-xs"></i>Kembali ke Beranda
    </a>
@endsection

@section('content')

<div class="min-h-[calc(100vh-128px)] flex items-center justify-center px-6 py-16">
    <div class="bg-surface border border-border rounded-2xl shadow-lift w-full max-w-lg p-10 text-center animate-fadeup">

        {{-- Success icon --}}
        <div class="w-20 h-20 rounded-full bg-emerald-light flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-circle-check text-emerald text-4xl"></i>
        </div>

        <h1 class="font-display font-bold text-2xl text-ink mb-2">Peminjaman Berhasil!</h1>
        <p class="text-sm text-ink-soft leading-relaxed mb-8">
            Permohonan Anda telah diterima dan disetujui secara otomatis.<br>
            Email konfirmasi telah dikirim ke
            <strong class="text-ink">{{ $borrowing->borrower_email }}</strong>.
        </p>

        {{-- Borrow code box --}}
        <div class="bg-s2 border border-dashed border-border rounded-xl px-6 py-5 mb-6">
            <div class="text-[10px] font-semibold uppercase tracking-widest text-ink-muted mb-1.5">Kode Peminjaman</div>
            <div class="font-mono font-medium text-3xl text-accent tracking-[3px]">
                {{ $borrowing->borrow_code }}
            </div>
        </div>

        {{-- Info grid --}}
        <div class="grid grid-cols-2 gap-3 text-left mb-6">
            @php
                $infoItems = [
                    ['label' => 'Aset',            'value' => $borrowing->asset->name,                          'icon' => 'fa-box-archive'],
                    ['label' => 'Kode Aset',        'value' => $borrowing->asset->asset_code,                   'icon' => 'fa-barcode'],
                    ['label' => 'Tanggal Pinjam',   'value' => $borrowing->borrow_date->format('d M Y'),        'icon' => 'fa-calendar'],
                    ['label' => 'Rencana Kembali',  'value' => $borrowing->planned_return_date->format('d M Y'),'icon' => 'fa-calendar-check'],
                ];
            @endphp
            @foreach ($infoItems as $item)
                <div class="bg-s2 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-1">
                        <i class="fas {{ $item['icon'] }} text-[9px]"></i>
                        {{ $item['label'] }}
                    </div>
                    <div class="text-sm font-medium text-ink">{{ $item['value'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Notice --}}
        <div class="flex items-start gap-3 bg-accent-light border border-accent/20 text-accent rounded-xl px-4 py-3 text-sm text-left mb-8">
            <i class="fas fa-circle-info mt-0.5 shrink-0"></i>
            <span>Simpan kode peminjaman di atas. Kode ini diperlukan saat mengembalikan aset.</span>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('public.return.form') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-border text-ink-soft hover:border-emerald hover:text-emerald hover:bg-emerald-light font-medium text-sm px-6 py-2.5 rounded-xl transition-all duration-150">
                <i class="fas fa-arrow-down-to-bracket"></i>Kembalikan Aset
            </a>
            <a href="{{ route('public.landing') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-accent hover:bg-accent-2 text-white font-semibold text-sm px-6 py-2.5 rounded-xl transition-all duration-200 hover:shadow-gbtn">
                <i class="fas fa-house"></i>Kembali ke Beranda
            </a>
        </div>

    </div>
</div>

@endsection
