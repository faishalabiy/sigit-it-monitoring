@extends('layouts.public')

@section('title', 'Form Peminjaman Aset')

{{-- Navbar: ganti tombol kanan jadi "Kembali" --}}
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
        <a href="{{ route('public.landing') }}" class="hover:text-accent transition-colors duration-150">Beranda</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <span class="text-ink-soft font-medium">Form Peminjaman Aset</span>
    </nav>

    {{-- Page Header --}}
    <div class="flex items-center gap-4 mb-8 animate-fadeup">
        <div class="w-14 h-14 rounded-2xl bg-accent-light flex items-center justify-center shrink-0">
            <i class="fas fa-upload text-accent text-2xl"></i>
        </div>
        <div>
            <h1 class="font-display font-bold text-2xl text-ink leading-tight">Form Peminjaman Aset</h1>
            <p class="text-sm text-ink-soft mt-1">Isi data di bawah ini untuk mengajukan peminjaman peralatan IT</p>
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
        <form action="{{ route('public.borrow.submit') }}" method="POST" id="borrowForm">
            @csrf

            {{-- ── SECTION: Pilih Aset ── --}}
            <div class="px-8 py-7 border-b border-border">
                <div class="flex items-center gap-2.5 text-xs font-semibold text-ink-muted uppercase tracking-widest mb-5">
                    <i class="fas fa-box-archive text-ink-muted/60"></i>
                    <span>Aset yang Dipinjam</span>
                    <span class="flex-1 h-px bg-border ml-1"></span>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-ink-soft">
                        Pilih Aset <span class="text-danger">*</span>
                    </label>
                    <div class="asset-selector relative">
                        <button type="button"
                                id="assetSelectBtn"
                                onclick="toggleAssetDropdown()"
                                class="asset-select-btn w-full flex items-center justify-between gap-3 px-4 py-2.5 border border-border rounded-xl text-sm bg-surface text-ink-muted transition-all duration-150 hover:border-accent/50 focus:outline-none {{ $errors->has('asset_id') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : '' }}">
                            <span id="assetSelectText">-- Pilih aset yang ingin dipinjam --</span>
                            <i class="fas fa-chevron-down text-[11px] text-ink-muted shrink-0 transition-transform duration-200" id="assetChevron"></i>
                        </button>

                        {{-- Dropdown --}}
                        <div id="assetDropdown"
                             class="absolute top-[calc(100%+6px)] left-0 right-0 bg-surface border border-border rounded-xl shadow-lift z-50 hidden overflow-hidden">

                            {{-- Search --}}
                            <div class="px-3 pt-3 pb-2 border-b border-border">
                                <div class="flex items-center gap-2 border border-border rounded-lg px-3 py-2 bg-s2 focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/10 transition-all">
                                    <i class="fas fa-magnifying-glass text-xs text-ink-muted"></i>
                                    <input type="text"
                                           id="assetSearch"
                                           placeholder="Cari nama atau kode aset..."
                                           oninput="filterAssets(this.value)"
                                           class="flex-1 bg-transparent text-sm text-ink placeholder:text-ink-muted/60 outline-none">
                                </div>
                            </div>

                            <div id="assetList" class="max-h-64 overflow-y-auto"></div>
                        </div>

                        <input type="hidden" name="asset_id" id="assetIdInput" value="{{ old('asset_id') }}">
                    </div>
                    @error('asset_id')
                        <p class="flex items-center gap-1.5 text-xs text-danger mt-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            {{-- ── SECTION: Data Peminjam ── --}}
            <div class="px-8 py-7 border-b border-border">
                <div class="flex items-center gap-2.5 text-xs font-semibold text-ink-muted uppercase tracking-widest mb-5">
                    <i class="fas fa-user text-ink-muted/60"></i>
                    <span>Data Peminjam</span>
                    <span class="flex-1 h-px bg-border ml-1"></span>
                </div>

                {{-- Nama Lengkap --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-ink-soft mb-1.5">
                        Nama Lengkap <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="borrower_name"
                           placeholder="Nama lengkap sesuai identitas"
                           value="{{ old('borrower_name') }}" required
                           class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 {{ $errors->has('borrower_name') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                    @error('borrower_name')
                        <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email + Telepon --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="borrower_email"
                               placeholder="email@domain.com"
                               value="{{ old('borrower_email') }}" required
                               class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 {{ $errors->has('borrower_email') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        @error('borrower_email')
                            <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-ink-muted mt-1.5">
                            <i class="fas fa-info-circle mr-1"></i>Kode peminjaman akan dikirim ke email ini
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">
                            No. Telepon <span class="text-danger">*</span>
                        </label>
                        <input type="tel" name="borrower_phone"
                               placeholder="08xxxxxxxxxx"
                               value="{{ old('borrower_phone') }}" required
                               class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 {{ $errors->has('borrower_phone') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        @error('borrower_phone')
                            <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Departemen + NIP --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">Departemen / Unit</label>
                        <input type="text" name="borrower_department"
                               placeholder="IT, HR, Keuangan, Akademik..."
                               value="{{ old('borrower_department') }}"
                               class="form-control w-full px-4 py-2.5 border border-border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">NIP / NIM / NIK</label>
                        <input type="text" name="borrower_id_number"
                               placeholder="Nomor identitas (opsional)"
                               value="{{ old('borrower_id_number') }}"
                               class="form-control w-full px-4 py-2.5 border border-border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60">
                    </div>
                </div>
            </div>

            {{-- ── SECTION: Rencana Peminjaman ── --}}
            <div class="px-8 py-7 border-b border-border">
                <div class="flex items-center gap-2.5 text-xs font-semibold text-ink-muted uppercase tracking-widest mb-5">
                    <i class="fas fa-calendar-days text-ink-muted/60"></i>
                    <span>Rencana Peminjaman</span>
                    <span class="flex-1 h-px bg-border ml-1"></span>
                </div>

                {{-- Tipe Peminjaman --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-ink-soft mb-1.5">
                        Tipe Peminjaman <span class="text-danger">*</span>
                    </label>

                    <select name="tipe_peminjaman"
                            required
                            class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 {{ $errors->has('tipe_peminjaman') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        <option value="">-- Pilih tipe peminjaman --</option>
                        <option value="pribadi" @selected(old('tipe_peminjaman') === 'pribadi')>
                            Pribadi
                        </option>
                        <option value="kantor" @selected(old('tipe_peminjaman') === 'kantor')>
                            Kantor
                        </option>
                    </select>

                    @error('tipe_peminjaman')
                        <p class="flex items-center gap-1.5 text-xs text-danger mt-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Tanggal Pinjam + Rencana Kembali --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">
                            Tanggal Pinjam <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="borrow_date" id="borrowDate"
                            value="{{ old('borrow_date', date('Y-m-d')) }}"
                            min="{{ date('Y-m-d') }}" required
                            class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 {{ $errors->has('borrow_date') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        @error('borrow_date')
                            <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-soft mb-1.5">
                            Rencana Tanggal Kembali <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="planned_return_date" id="plannedReturnDate"
                            value="{{ old('planned_return_date', old('borrow_date', date('Y-m-d'))) }}"
                            min="{{ old('borrow_date', date('Y-m-d')) }}" required
                            class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 {{ $errors->has('planned_return_date') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">
                        @error('planned_return_date')
                            <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Keperluan --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-ink-soft mb-1.5">
                        Keperluan / Tujuan Peminjaman <span class="text-danger">*</span>
                    </label>
                    <textarea name="purpose" rows="3" required
                            placeholder="Jelaskan secara singkat untuk apa aset ini akan digunakan..."
                            class="form-control w-full px-4 py-2.5 border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60 resize-y {{ $errors->has('purpose') ? 'border-danger shadow-[0_0_0_3px_rgba(220,38,38,.08)]' : 'border-border' }}">{{ old('purpose') }}</textarea>
                    @error('purpose')
                        <p class="flex items-center gap-1.5 text-xs text-danger mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lokasi --}}
                <div>
                    <label class="block text-xs font-medium text-ink-soft mb-1.5">Lokasi Penggunaan</label>
                    <input type="text" name="destination"
                        placeholder="Contoh: Gedung A Lantai 2, Ruang Rapat 301..."
                        value="{{ old('destination') }}"
                        class="form-control w-full px-4 py-2.5 border border-border rounded-xl text-sm text-ink bg-surface transition-all duration-150 placeholder:text-ink-muted/60">
                </div>
            </div>

            {{-- ── SUBMIT AREA ── --}}
            <div class="px-8 py-5 bg-s2 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-ink-muted order-2 sm:order-1">
                    <i class="fas fa-shield-halved mr-1 text-emerald"></i>
                    Data Anda aman dan hanya digunakan untuk keperluan peminjaman aset.
                </p>
                <button type="submit" id="borrowSubmitBtn"
                        class="order-1 sm:order-2 w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-accent hover:bg-accent-2 text-white font-semibold text-sm px-7 py-3 rounded-xl transition-all duration-200 hover:shadow-gbtn hover:-translate-y-px active:translate-y-0">
                    <span class="btn-text flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>Ajukan Peminjaman
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
// ── Asset dropdown state ─────────────────────────────
let allAssets      = [];
let selectedAssetId = '{{ old('asset_id') }}';

// ── Load available assets ────────────────────────────
async function loadAssets() {
    try {
        const res = await fetch('{{ route('public.assets.available') }}');
        allAssets = await res.json();
        renderAssets(allAssets);
        if (selectedAssetId) {
            const found = allAssets.find(a => a.id == selectedAssetId);
            if (found) selectAsset(found, false);
        }
    } catch (e) {
        document.getElementById('assetList').innerHTML = `
            <div class="flex items-center gap-2 text-danger text-sm px-4 py-6 justify-center">
                <i class="fas fa-exclamation-circle"></i>
                Gagal memuat data aset. Muat ulang halaman.
            </div>`;
    }
}

// ── Render asset list ────────────────────────────────
function renderAssets(assets) {
    const el = document.getElementById('assetList');
    if (!assets.length) {
        el.innerHTML = `
            <div class="text-center text-ink-muted text-sm px-4 py-6">
                <i class="fas fa-box-open text-2xl mb-2 block opacity-40"></i>
                Tidak ada aset tersedia saat ini.
            </div>`;
        return;
    }
    el.innerHTML = assets.map(a => `
        <div class="asset-option flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors duration-100
                    hover:bg-s2 ${a.id == selectedAssetId ? 'bg-accent-light' : ''}"
             onclick='selectAsset(${JSON.stringify(a)})'>
            <div class="w-9 h-9 rounded-xl bg-accent-light flex items-center justify-center shrink-0">
                <i class="fas fa-laptop text-accent text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-ink truncate">${a.name}</div>
                <div class="text-xs text-ink-muted mt-0.5">${a.code} &middot; ${a.category} &middot; ${a.brand || '-'}</div>
            </div>
            <span class="text-[11px] font-semibold text-emerald bg-emerald-light px-2.5 py-0.5 rounded-full shrink-0">Tersedia</span>
        </div>
    `).join('');
}

// ── Filter by search ─────────────────────────────────
function filterAssets(q) {
    const lq = q.toLowerCase();
    renderAssets(allAssets.filter(a =>
        a.name.toLowerCase().includes(lq) ||
        a.code.toLowerCase().includes(lq) ||
        (a.category && a.category.toLowerCase().includes(lq))
    ));
}

// ── Select an asset ──────────────────────────────────
function selectAsset(asset, close = true) {
    selectedAssetId = asset.id;
    document.getElementById('assetIdInput').value = asset.id;

    const btn  = document.getElementById('assetSelectBtn');
    const text = document.getElementById('assetSelectText');
    text.textContent = asset.name + ' (' + asset.code + ')';
    text.classList.remove('text-ink-muted');
    text.classList.add('text-ink');
    btn.classList.add('border-accent/40');

    if (close) toggleAssetDropdown(false);
    renderAssets(allAssets);
}

// ── Toggle dropdown ──────────────────────────────────
function toggleAssetDropdown(forceOpen) {
    const dd      = document.getElementById('assetDropdown');
    const chevron = document.getElementById('assetChevron');
    const isOpen  = !dd.classList.contains('hidden');
    const open    = forceOpen !== undefined ? forceOpen : !isOpen;

    dd.classList.toggle('hidden', !open);
    chevron.style.transform = open ? 'rotate(180deg)' : '';

    if (open) {
        setTimeout(() => document.getElementById('assetSearch').focus(), 50);
        if (!allAssets.length) loadAssets();
    }
}

document.getElementById('borrowDate').addEventListener('change', function () {
    const returnDate = document.getElementById('plannedReturnDate');
    returnDate.min = this.value;

    if (returnDate.value && returnDate.value < this.value) {
        returnDate.value = this.value;
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', e => {
    if (!document.querySelector('.asset-selector').contains(e.target)) {
        toggleAssetDropdown(false);
    }
});

// ── Submit: show spinner ─────────────────────────────
document.getElementById('borrowForm').addEventListener('submit', function () {
    const btn     = document.getElementById('borrowSubmitBtn');
    const btnText = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner');
    btn.disabled  = true;
    btnText.classList.add('hidden');
    spinner.classList.remove('hidden');
});

// ── Init ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadAssets);
</script>
@endpush
