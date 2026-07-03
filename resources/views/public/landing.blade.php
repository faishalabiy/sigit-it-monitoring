@extends('layouts.public')

@section('title', 'SIGIT')

@section('nav')
    <a href="#cek-status"
       class="text-sm text-ink-soft hover:text-ink px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
        <i class="fas fa-magnifying-glass text-xs mr-1.5"></i>Cek Status
    </a>
    <a href="{{ route('admin.dashboard') }}"
       class="text-sm font-medium text-accent border border-accent px-4 py-1.5 rounded-lg hover:bg-accent hover:text-white transition-all duration-150">
        <i class="fas fa-lock text-xs mr-1.5"></i>Admin
    </a>
@endsection

@section('content')

{{-- ─────────────────── HERO ─────────────────── --}}
<section class="relative overflow-hidden bg-surface hero-grid hero-grain">

    {{-- Glow blobs --}}
    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-accent/7 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-emerald/6 rounded-full blur-[100px] translate-y-1/3 -translate-x-1/4 pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 w-[300px] h-[300px] bg-accent/4 rounded-full blur-[80px] -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>

    {{-- Scan line --}}
    <div class="scan-line absolute left-0 right-0 h-px top-2/5 pointer-events-none opacity-60"></div>

    <div class="max-w-6xl mx-auto px-6 pt-20 pb-10 md:pt-28 md:pb-14 relative z-10">

        {{-- Centered copy --}}
        <div class="text-center max-w-3xl mx-auto animate-fadeup">

            <div class="inline-flex items-center gap-2 bg-accent-light border border-accent/20 text-accent text-xs font-semibold tracking-widest uppercase px-4 py-1.5 rounded-full mb-8">
                <i class="fas fa-circle text-[5px] dot-pulse"></i>
                Smart Integrated Governance for Information Technology
            </div>

            <div class="mb-4">
                <span class="font-display font-extrabold text-[80px] md:text-[108px] leading-none tracking-tight text-ink select-none">
                    S<span class="text-accent">IGIT</span>
                </span>
            </div>

            <h1 class="font-display font-bold text-2xl md:text-3xl text-ink leading-snug tracking-tight mb-5">
                Peminjaman Aset IT yang
                <span class="text-accent">Mudah &amp; Terkelola</span>
            </h1>

            <p class="text-ink-soft text-base leading-relaxed mx-auto max-w-lg mb-10">
                Pinjam peralatan IT secara mandiri tanpa perlu login.
                Isi form, dapatkan kode peminjaman, dan kembalikan saat selesai.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-wrap items-center justify-center gap-3 mb-14">
                <a href="{{ route('public.borrow.form') }}"
                   class="inline-flex items-center gap-2 bg-accent hover:bg-accent-2 text-white font-semibold text-sm px-6 py-3 rounded-xl shadow-gbtn hover:shadow-none transition-all duration-200">
                    <i class="fas fa-upload"></i>
                    Ajukan Peminjaman
                </a>
                <a href="{{ route('public.return.form') }}"
                   class="inline-flex items-center gap-2 bg-surface border border-border hover:border-accent text-ink-soft hover:text-accent font-semibold text-sm px-6 py-3 rounded-xl transition-all duration-200">
                    <i class="fas fa-download"></i>
                    Kembalikan Aset
                </a>
            </div>
        </div>

        {{-- Stats bar --}}
        <div class="animate-fadeup-2 border border-border rounded-2xl bg-surface/80 backdrop-blur-sm shadow-card">
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-border">
                @php
                    $statItems = [
                        ['num' => $stats['total_assets'],     'label' => 'Total Aset',      'icon' => 'fa-box-archive',      'color' => 'text-ink',    'iconbg' => 'bg-s2 text-ink-muted'],
                        ['num' => $stats['available_assets'], 'label' => 'Tersedia',         'icon' => 'fa-circle-check',     'color' => 'text-emerald','iconbg' => 'bg-emerald-light text-emerald'],
                        ['num' => $stats['borrowed_assets'],  'label' => 'Sedang Dipinjam',  'icon' => 'fa-clock-rotate-left','color' => 'text-amber',  'iconbg' => 'bg-amber/10 text-amber'],
                        ['num' => $stats['total_borrowings'], 'label' => 'Total Transaksi',  'icon' => 'fa-chart-line',       'color' => 'text-accent', 'iconbg' => 'bg-accent-light text-accent'],
                    ];
                @endphp
                @foreach ($statItems as $s)
                    <div class="stat-item flex items-center gap-4 px-7 py-5 cursor-default select-none">
                        <div class="w-10 h-10 rounded-xl {{ $s['iconbg'] }} flex items-center justify-center shrink-0">
                            <i class="fas {{ $s['icon'] }} text-sm"></i>
                        </div>
                        <div>
                            <div class="stat-num font-display font-bold text-2xl leading-none {{ $s['color'] }}">{{ $s['num'] }}</div>
                            <div class="text-ink-muted text-xs mt-1">{{ $s['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
</section>

{{-- ─────────────────── SERVICE CARDS ─────────────────── --}}
<section class="max-w-6xl mx-auto px-6 py-16">

    <div class="text-center mb-12 animate-fadeup-3">
        <div class="inline-flex items-center gap-2 text-xs font-semibold text-ink-muted tracking-widest uppercase mb-3">
            <span class="w-8 h-px bg-border inline-block"></span>
            Layanan
            <span class="w-8 h-px bg-border inline-block"></span>
        </div>
        <h2 class="font-display font-bold text-2xl text-ink">Pilih Layanan</h2>
        <p class="text-ink-soft text-sm mt-2">Apa yang ingin Anda lakukan hari ini?</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeup-4">

        {{-- Borrow Card --}}
        <a href="{{ route('public.borrow.form') }}"
           class="service-card borrow-card bg-surface border border-border rounded-2xl overflow-hidden shadow-card hover:shadow-lift no-underline flex flex-col group">
            <div class="p-9 flex-1">
                <div class="relative w-16 h-16 mb-7">
                    <div class="w-16 h-16 rounded-2xl bg-accent-light flex items-center justify-center">
                        <i class="fas fa-upload text-accent text-2xl"></i>
                    </div>
                    <div class="icon-ring"></div>
                </div>
                <h3 class="font-display font-bold text-xl text-ink mb-2">Peminjaman Aset</h3>
                <p class="text-ink-soft text-sm leading-relaxed mb-7">
                    Ajukan peminjaman peralatan IT seperti laptop, proyektor, kamera, dan perangkat lainnya untuk kebutuhan kerja Anda.
                </p>
                <div class="space-y-2.5">
                    @foreach (['Pilih aset yang ingin dipinjam', 'Isi data diri & tujuan peminjaman', 'Dapatkan kode peminjaman via email'] as $i => $step)
                        <div class="flex items-center gap-3 text-sm text-ink-soft">
                            <span class="w-5 h-5 rounded-full bg-accent-light text-accent text-[11px] font-bold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                            {{ $step }}
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="px-9 pb-8 pt-5 border-t border-border">
                <div class="w-full flex items-center justify-center gap-2.5 bg-accent hover:bg-accent-2 text-white font-semibold text-sm py-3 rounded-xl transition-colors duration-200 group-hover:shadow-gbtn">
                    <i class="fas fa-upload text-sm"></i>
                    Ajukan Peminjaman
                    <i class="fas fa-arrow-right text-xs arrow-icon"></i>
                </div>
            </div>
        </a>

        {{-- Return Card --}}
        <a href="{{ route('public.return.form') }}"
           class="service-card return-card bg-surface border border-border rounded-2xl overflow-hidden shadow-card hover:shadow-lift no-underline flex flex-col group">
            <div class="p-9 flex-1">
                <div class="relative w-16 h-16 mb-7">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-light flex items-center justify-center">
                        <i class="fas fa-download text-emerald text-2xl"></i>
                    </div>
                    <div class="icon-ring"></div>
                </div>
                <h3 class="font-display font-bold text-xl text-ink mb-2">Pengembalian Aset</h3>
                <p class="text-ink-soft text-sm leading-relaxed mb-7">
                    Kembalikan peralatan IT yang sudah selesai Anda gunakan. Cukup masukkan kode peminjaman yang Anda terima via email.
                </p>
                <div class="space-y-2.5">
                    @foreach (['Masukkan kode peminjaman', 'Laporkan kondisi aset yang dikembalikan', 'Konfirmasi pengembalian selesai'] as $i => $step)
                        <div class="flex items-center gap-3 text-sm text-ink-soft">
                            <span class="w-5 h-5 rounded-full bg-emerald-light text-emerald text-[11px] font-bold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                            {{ $step }}
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="px-9 pb-8 pt-5 border-t border-border">
                <div class="w-full flex items-center justify-center gap-2.5 bg-emerald hover:bg-emerald/90 text-white font-semibold text-sm py-3 rounded-xl transition-colors duration-200 group-hover:shadow-gbtng">
                    <i class="fas fa-download text-sm"></i>
                    Kembalikan Aset
                    <i class="fas fa-arrow-right text-xs arrow-icon"></i>
                </div>
            </div>
        </a>

    </div>
</section>

{{-- ─────────────────── CEK STATUS ─────────────────── --}}
<section class="max-w-6xl mx-auto px-6 pb-20" id="cek-status">
    <div class="bg-surface border border-border rounded-2xl p-8 shadow-card">

        <div class="flex items-center gap-4 mb-6">
            <div class="w-11 h-11 rounded-xl bg-s2 border border-border flex items-center justify-center shrink-0">
                <i class="fas fa-magnifying-glass text-ink-soft text-base"></i>
            </div>
            <div>
                <h3 class="font-display font-semibold text-base text-ink">Cek Status Peminjaman</h3>
                <p class="text-xs text-ink-muted mt-0.5">Pantau status aset yang sedang Anda pinjam menggunakan kode peminjaman</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1 min-w-0">
                <label for="checkCode" class="block text-xs font-medium text-ink-soft mb-1.5">Kode Peminjaman</label>
                <input type="text" id="checkCode"
                       placeholder="BRW-20240101-001"
                       class="form-control w-full border border-border rounded-xl px-4 py-2.5 text-sm font-mono text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 uppercase">
            </div>
            <button onclick="checkBorrowStatus()"
                    class="flex items-center gap-2 bg-ink hover:bg-ink-soft text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-colors duration-150 whitespace-nowrap">
                <i class="fas fa-search text-xs"></i>Cek Status
            </button>
        </div>

        <div class="status-result mt-4 rounded-xl p-5 text-sm" id="statusResult"></div>
    </div>
</section>

@endsection

@push('scripts')
<script>
const code_input = document.getElementById('checkCode');

code_input.addEventListener('input',   () => code_input.value = code_input.value.toUpperCase());
code_input.addEventListener('keydown', e => { if (e.key === 'Enter') checkBorrowStatus(); });

async function checkBorrowStatus() {
    const code   = code_input.value.trim();
    const result = document.getElementById('statusResult');

    if (!code) {
        setResult(result, 'error', '<i class="fas fa-exclamation-circle mr-1.5"></i>Masukkan kode peminjaman terlebih dahulu.');
        return;
    }

    setResult(result, 'loading', '<i class="fas fa-spinner fa-spin mr-1.5"></i>Mencari data...');

    try {
        const params = new URLSearchParams({ borrow_code: code });
        const res    = await fetch('{{ route('public.check.status') }}?' + params);
        const data   = await res.json();

        if (!data.found) {
            setResult(result, 'error', `<i class="fas fa-times-circle mr-1.5"></i>${data.message}`);
            return;
        }

        const statusMap = {
            approved: { bg: 'bg-amber/10 border-amber/25',   badge: 'bg-amber/15 text-amber'     },
            borrowed: { bg: 'bg-amber/10 border-amber/25',   badge: 'bg-amber/15 text-amber'     },
            returned: { bg: 'bg-emerald/8 border-emerald/20',badge: 'bg-emerald/15 text-emerald' },
            overdue:  { bg: 'bg-danger/8 border-danger/20',  badge: 'bg-danger/15 text-danger'   },
        };
        const st = statusMap[data.status] || { bg: 'bg-s2 border-border', badge: 'bg-s3 text-ink' };

        const daysHtml = (!data.isReturned && data.daysInfo) ? `
            <div>
                <div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Sisa Waktu</div>
                <div class="text-sm font-medium ${
                    data.daysInfo.type === 'overdue' ? 'text-danger'
                  : data.daysInfo.type === 'today'   ? 'text-amber'
                  : 'text-emerald'
                }">${data.daysInfo.label}</div>
            </div>` : '';

        const returnHtml = data.isReturned
            ? `<div><div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Tgl Dikembalikan</div><div class="text-sm font-medium text-ink">${data.actualReturn}</div></div>`
            : `<div><div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Rencana Kembali</div><div class="text-sm font-medium text-ink">${data.plannedReturn}</div></div>`;

        result.className = `status-result show rounded-xl p-5 border ${st.bg} animate-fadeup`;
        result.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-laptop text-accent text-base"></i>
                    <span class="font-display font-semibold text-base text-ink">${data.asset.name}</span>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${st.badge}">${data.statusInfo.label}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Kode Pinjam</div>
                    <div class="text-sm font-mono font-medium text-ink">${data.borrowCode}</div>
                </div>
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Peminjam</div>
                    <div class="text-sm font-medium text-ink">${data.borrowerName}</div>
                </div>
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">Tgl Pinjam</div>
                    <div class="text-sm font-medium text-ink">${data.borrowDate}</div>
                </div>
                ${returnHtml}
                ${daysHtml}
            </div>
        `;
    } catch (e) {
        setResult(result, 'error', '<i class="fas fa-exclamation-circle mr-1.5"></i>Terjadi kesalahan. Coba lagi.');
    }
}

function setResult(el, type, html) {
    const cls = {
        loading: 'status-result show rounded-xl p-5 bg-s2 border border-border text-ink-soft',
        error:   'status-result show rounded-xl p-5 bg-danger-light border border-danger/20 text-danger',
    };
    el.className = cls[type] || cls.loading;
    el.innerHTML = html;
}
</script>
@endpush
