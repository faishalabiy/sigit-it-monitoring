@extends('layouts.public')

@section('title', 'Form Pengembalian Aset')

@section('nav')
    <a href="{{ route('public.landing') }}"
       class="inline-flex items-center gap-2 text-sm text-ink-soft hover:text-ink px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
        <i class="fas fa-arrow-left text-xs"></i>Kembali ke Beranda
    </a>
@endsection

@section('content')

<div class="max-w-3xl mx-auto px-6 py-10 pb-20">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-xs text-ink-muted mb-7">
        <a href="{{ route('public.landing') }}" class="hover:text-emerald transition-colors duration-150">Beranda</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <span class="text-ink-soft font-medium">Form Pengembalian Aset</span>
    </nav>

    {{-- Page Header --}}
    <div class="flex items-center gap-4 mb-8 animate-fadeup">
        <div class="w-14 h-14 rounded-2xl bg-emerald-light flex items-center justify-center shrink-0">
            <i class="fas fa-download text-emerald text-2xl"></i>
        </div>
        <div>
            <h1 class="font-display font-bold text-2xl text-ink leading-tight">Form Pengembalian Aset</h1>
            <p class="text-sm text-ink-soft mt-1">Masukkan kode peminjaman untuk memproses pengembalian aset IT</p>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="flex items-start gap-3 bg-danger-light border border-danger/20 text-danger rounded-xl px-4 py-3.5 text-sm mb-6 animate-fadeup">
            <i class="fas fa-exclamation-circle mt-0.5 shrink-0"></i>
            <div>
                <div class="font-semibold mb-1">Mohon periksa kembali isian Anda:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Form Card --}}
    <div class="bg-surface border border-border rounded-2xl shadow-card overflow-hidden animate-fadeup-2">
        <form action="{{ route('public.return.submit') }}" method="POST" id="returnForm">
            @csrf

            {{-- ── SECTION: Verifikasi Peminjaman ── --}}
            <div class="px-8 py-7 border-b border-border">
                <div class="flex items-center gap-2.5 text-xs font-semibold text-ink-muted uppercase tracking-widest mb-5">
                    <i class="fas fa-qrcode text-ink-muted/60"></i>
                    <span>Verifikasi Peminjaman</span>
                    <span class="flex-1 h-px bg-border ml-1"></span>
                </div>

                {{-- Lookup row --}}
                <div class="flex flex-col sm:flex-row gap-3 items-end mb-1">
                    <div class="flex-1 min-w-0">
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">
                            Kode Peminjaman <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="borrow_code" id="borrowCodeInput"
                               placeholder="BRW-20240101-001"
                               value="{{ old('borrow_code', request('borrow_code')) }}" required
                               class="form-control w-full px-4 py-2.5 border rounded-xl text-sm font-mono tracking-wide uppercase bg-surface transition-all duration-150 placeholder:text-ink-muted/60 placeholder:normal-case placeholder:tracking-normal {{ $errors->has('borrow_code') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        @error('borrow_code')
                            <p class="flex items-center gap-1.5 text-xs text-danger mt-1">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                        <p class="text-xs text-ink-muted mt-1.5">
                            <i class="fas fa-info-circle mr-1"></i>Lihat di email konfirmasi peminjaman Anda
                        </p>
                    </div>
                    <button type="button" id="lookupBtn" onclick="lookupBorrowing()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 border border-border bg-surface text-ink-soft text-sm font-medium rounded-xl hover:border-emerald hover:text-emerald hover:bg-emerald-light transition-all duration-150 whitespace-nowrap shrink-0">
                        <i class="fas fa-search text-xs"></i>Cek Detail
                    </button>
                </div>

                {{-- Preview Result --}}
                <div id="borrowPreview" class="hidden mt-4 border rounded-xl overflow-hidden text-sm">
                    <div id="previewHeader" class="flex items-center gap-2 px-4 py-3 font-medium"></div>
                    <div id="previewBody" class="px-4 py-4 bg-surface grid grid-cols-2 sm:grid-cols-4 gap-4"></div>
                </div>
            </div>

            {{-- ── SECTION: Kondisi Pengembalian ── --}}
            <div class="px-8 py-7 border-b border-border">
                <div class="flex items-center gap-2.5 text-xs font-semibold text-ink-muted uppercase tracking-widest mb-5">
                    <i class="fas fa-clipboard-check text-ink-muted/60"></i>
                    <span>Kondisi Pengembalian</span>
                    <span class="flex-1 h-px bg-border ml-1"></span>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-ink-soft mb-3">
                        Kondisi Aset Saat Dikembalikan <span class="text-danger">*</span>
                    </label>
                    <input type="hidden" name="return_condition" id="returnConditionInput" value="{{ old('return_condition') }}">

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $conditions = [
                                ['value' => 'good',    'label' => 'Baik',        'sub' => 'Tidak ada kerusakan',   'icon' => 'fa-circle-check',    'color' => 'emerald'],
                                ['value' => 'fair',    'label' => 'Cukup',       'sub' => 'Sedikit aus / lecet',   'icon' => 'fa-circle-minus',    'color' => 'amber'],
                                ['value' => 'poor',    'label' => 'Rusak Ringan','sub' => 'Masih bisa digunakan',  'icon' => 'fa-triangle-exclamation', 'color' => 'orange'],
                                ['value' => 'damaged', 'label' => 'Rusak',       'sub' => 'Perlu perbaikan',       'icon' => 'fa-circle-xmark',    'color' => 'danger'],
                            ];
                        @endphp

                        @foreach ($conditions as $c)
                            <button type="button"
                                    data-condition="{{ $c['value'] }}"
                                    onclick="setCondition('{{ $c['value'] }}', this)"
                                    class="condition-btn flex flex-col items-center gap-1.5 py-4 px-3 border border-border rounded-xl bg-surface text-ink-soft cursor-pointer transition-all duration-150 hover:border-ink-soft/40 hover:bg-s2
                                           {{ old('return_condition') === $c['value'] ? 'active-'.$c['value'] : '' }}">
                                <i class="fas {{ $c['icon'] }} text-2xl"></i>
                                <span class="text-sm font-semibold">{{ $c['label'] }}</span>
                                <span class="text-[11px] text-ink-muted leading-tight text-center">{{ $c['sub'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    @error('return_condition')
                        <p class="flex items-center gap-1.5 text-xs text-danger mt-2">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-medium text-ink-soft mb-1.5">Catatan Pengembalian</label>
                    <textarea name="return_notes" rows="3"
                              placeholder="Tuliskan jika ada kerusakan, masalah, atau hal penting lainnya yang perlu diketahui tim IT..."
                              class="form-control w-full px-4 py-2.5 border border-border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 resize-y">{{ old('return_notes') }}</textarea>
                </div>
            </div>

            {{-- ── SUBMIT AREA ── --}}
            <div class="px-8 py-5 bg-s2 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-ink-muted order-2 sm:order-1">
                    <i class="fas fa-circle-info mr-1 text-accent"></i>
                    Pastikan kode peminjaman sesuai sebelum mengkonfirmasi pengembalian.
                </p>
                <button type="submit" id="returnSubmitBtn"
                        class="order-1 sm:order-2 w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-emerald hover:bg-emerald/90 text-white font-semibold text-sm px-7 py-3 rounded-xl transition-all duration-200 hover:shadow-gbtng hover:-translate-y-px active:translate-y-0">
                    <span class="btn-text flex items-center gap-2">
                        <i class="fas fa-circle-check"></i>Konfirmasi Pengembalian
                    </span>
                    <span class="spinner hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                </button>
            </div>

        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Kondisi warna mapping ─────────────────────────────
const conditionStyles = {
    good:    { border: 'border-emerald',  bg: 'bg-emerald-light',  text: 'text-emerald',  icon: 'text-emerald'  },
    fair:    { border: 'border-amber',    bg: 'bg-amber/10',       text: 'text-amber',    icon: 'text-amber'    },
    poor:    { border: 'border-orange-500', bg: 'bg-orange-50',    text: 'text-orange-500', icon: 'text-orange-500' },
    damaged: { border: 'border-danger',   bg: 'bg-danger-light',   text: 'text-danger',   icon: 'text-danger'   },
};

// ── Condition selector ────────────────────────────────
function setCondition(value, btn) {
    // Reset semua tombol
    document.querySelectorAll('.condition-btn').forEach(b => {
        b.className = 'condition-btn flex flex-col items-center gap-1.5 py-4 px-3 border border-border rounded-xl bg-surface text-ink-soft cursor-pointer transition-all duration-150 hover:border-ink-soft/40 hover:bg-s2';
        b.querySelector('span.text-sm').className    = 'text-sm font-semibold';
        b.querySelector('span.text-\\[11px\\]').className = 'text-[11px] text-ink-muted leading-tight text-center';
    });

    // Aktifkan tombol yang dipilih
    const s = conditionStyles[value];
    btn.classList.add(s.border, s.bg, s.text);
    btn.querySelector('span.text-sm').classList.add(s.text);

    document.getElementById('returnConditionInput').value = value;
}

// ── Restore old condition on load ─────────────────────
(function () {
    const old = document.getElementById('returnConditionInput').value;
    if (old) {
        const btn = document.querySelector(`[data-condition="${old}"]`);
        if (btn) setCondition(old, btn);
    }
})();

// ── Uppercase borrow code ─────────────────────────────
const codeInput = document.getElementById('borrowCodeInput');
codeInput.addEventListener('input', () => codeInput.value = codeInput.value.toUpperCase());
codeInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookupBorrowing(); } });

// ── Preview helpers ───────────────────────────────────
function showPreview(type, headerHtml, bodyHtml = '') {
    const preview = document.getElementById('borrowPreview');
    const header  = document.getElementById('previewHeader');
    const body    = document.getElementById('previewBody');

    const styles = {
        found:    { wrap: 'border-emerald/40',  header: 'bg-emerald-light text-emerald'   },
        error:    { wrap: 'border-danger/30',   header: 'bg-danger-light text-danger'     },
        returned: { wrap: 'border-accent/30',   header: 'bg-accent-light text-accent'     },
        loading:  { wrap: 'border-border',      header: 'bg-s2 text-ink-soft'             },
    };
    const st = styles[type] || styles.loading;

    preview.className = `mt-4 border rounded-xl overflow-hidden text-sm ${st.wrap}`;
    header.className  = `flex items-center gap-2 px-4 py-3 font-medium ${st.header}`;
    header.innerHTML  = headerHtml;
    body.innerHTML    = bodyHtml;

    if (bodyHtml) {
        body.className = 'px-4 py-4 bg-surface grid grid-cols-2 sm:grid-cols-4 gap-4';
    } else {
        body.className = '';
    }

    preview.classList.remove('hidden');
}

function previewItem(label, value, mono = false) {
    return `<div>
        <div class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted mb-0.5">${label}</div>
        <div class="text-sm font-medium text-ink ${mono ? 'font-mono' : ''}">${value}</div>
    </div>`;
}

// ── Lookup borrowing ──────────────────────────────────
async function lookupBorrowing() {
    const code = codeInput.value.trim();
    const btn  = document.getElementById('lookupBtn');

    if (!code) {
        showPreview('error', '<i class="fas fa-exclamation-circle"></i> Masukkan kode peminjaman terlebih dahulu.');
        return;
    }

    // Loading state
    btn.disabled     = true;
    btn.innerHTML    = '<i class="fas fa-spinner fa-spin text-xs"></i>Mencari...';
    showPreview('loading', '<i class="fas fa-spinner fa-spin"></i> Mencari data peminjaman...');

    try {
        const res  = await fetch('{{ route('public.check.status') }}?' + new URLSearchParams({ borrow_code: code }));
        const data = await res.json();

        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-search text-xs"></i>Cek Detail';

        if (!data.found) {
            showPreview('error', '<i class="fas fa-times-circle"></i> Kode peminjaman tidak ditemukan.');
            return;
        }

        if (data.isReturned) {
            showPreview('returned', `<i class="fas fa-info-circle"></i> Peminjaman ini sudah dikembalikan pada <strong class="ml-1">${data.actualReturn}</strong>.`);
            return;
        }

        const daysItem = data.daysInfo ? previewItem('Sisa Waktu',
            `<span class="${data.daysInfo.type === 'overdue' ? 'text-danger' : data.daysInfo.type === 'today' ? 'text-amber' : 'text-emerald'} font-semibold">
                ${data.daysInfo.label}
            </span>`) : '';

        showPreview(
            'found',
            `<i class="fas fa-circle-check"></i> Data ditemukan &mdash; <strong class="ml-1">${data.asset.name}</strong>`,
            previewItem('Kode Pinjam', data.borrowCode, true) +
            previewItem('Peminjam',    data.borrowerName) +
            previewItem('Tgl Pinjam',  data.borrowDate) +
            previewItem('Rencana Kembali', data.plannedReturn) +
            daysItem
        );

    } catch (e) {
        document.getElementById('lookupBtn').disabled  = false;
        document.getElementById('lookupBtn').innerHTML = '<i class="fas fa-search text-xs"></i>Cek Detail';
        showPreview('error', '<i class="fas fa-exclamation-circle"></i> Terjadi kesalahan. Coba lagi.');
    }
}

// ── Submit: show spinner ──────────────────────────────
document.getElementById('returnForm').addEventListener('submit', function () {
    const btn     = document.getElementById('returnSubmitBtn');
    btn.disabled  = true;
    btn.querySelector('.btn-text').classList.add('hidden');
    btn.querySelector('.spinner').classList.remove('hidden');
});

// ── Auto-lookup jika ada old value ────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (codeInput.value.trim()) lookupBorrowing();
});
</script>
@endpush
