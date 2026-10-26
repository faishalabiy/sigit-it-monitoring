@extends('layouts.admin')
@section('title','Preventive Maintenance')
@section('page-title','Preventive Maintenance')
@section('content')
<div class="mb-6">
    <h2 class="font-heading text-2xl font-bold text-slate-950">Preventive Maintenance</h2>
    <p class="mt-1 text-sm text-slate-500">Pusat konfigurasi pemeriksaan berkala dan kesiapan sistem TI.</p>
</div>
@include('admin.pm.overview-partial')
@include('admin.pm.monthly-matrix-partial')
<div class="mb-5 flex flex-wrap gap-3">
  <a href="{{ route('admin.pm.notifications.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm"><i class="fa-solid fa-bell mr-2"></i> Pusat Notifikasi PM</a>
  <a href="{{ route('admin.pm.backups.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm"><i class="fa-solid fa-box-archive mr-2"></i> Monitoring Backup</a>
</div>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
    <span><i class="fa-solid fa-circle-info mr-2"></i> Jalankan pemeriksaan langsung dari <b>Jadwal PM</b> atau tinjau hasil dari <b>Riwayat Monitoring</b>. Pemeriksaan tanpa konektor akan diberi status Belum Tersedia / Perlu Manual.</span>
    <a href="{{ route('admin.pm.runs.index') }}" class="rounded-lg bg-white px-3 py-2 font-semibold text-blue-700 shadow-sm">Riwayat Monitoring <i class="fa-solid fa-arrow-right ml-1"></i></a>
    <a href="{{ route('admin.pm.correctives.index') }}" class="rounded-lg bg-white px-3 py-2 font-semibold text-blue-700 shadow-sm">Tindak Lanjut <i class="fa-solid fa-arrow-right ml-1"></i></a>
</div>
<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach([
        ['label'=>'Corrective Terbuka','value'=>$openCorrectives,'icon'=>'fa-list-check'],
        ['label'=>'Corrective Terlambat','value'=>$overdueCorrectives,'icon'=>'fa-triangle-exclamation'],
        ['label'=>'Total Sistem','value'=>$systemCount,'icon'=>'fa-server'],
        ['label'=>'Sistem Aktif','value'=>$activeSystemCount,'icon'=>'fa-circle-check'],
        ['label'=>'Template Parameter Aktif','value'=>$parameterCount,'icon'=>'fa-list-check'],
        ['label'=>'Jadwal Siap','value'=>$readyCount,'icon'=>'fa-calendar-check']
    ] as $card)
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><i class="fa-solid {{ $card['icon'] }}"></i></div>
        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
    </div>
    @endforeach
</div>
<div class="mb-7 grid gap-4 md:grid-cols-3">
    @foreach([
        ['title'=>'Master Sistem','desc'=>'Daftarkan aplikasi, server, database dan perangkat jaringan sebagai target pemeriksaan.','icon'=>'fa-cubes-stacked','route'=>'admin.pm.systems.index'],
        ['title'=>'Template Parameter','desc'=>'Definisikan metode pemeriksaan, nilai acuan dan batas WARNING/FAIL.','icon'=>'fa-sliders','route'=>'admin.pm.parameters.index'],
        ['title'=>'Jadwal PM','desc'=>'Tetapkan frekuensi, jam, PIC dan target yang akan diperiksa.','icon'=>'fa-calendar-days','route'=>'admin.pm.schedules.index']
    ] as $item)
    <a href="{{ route($item['route']) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
        <i class="fa-solid {{ $item['icon'] }} mb-4 text-xl text-blue-600"></i>
        <h3 class="font-bold text-slate-900">{{ $item['title'] }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $item['desc'] }}</p>
        <p class="mt-3 text-sm font-semibold text-blue-600">Kelola <i class="fa-solid fa-arrow-right ml-1"></i></p>
    </a>
    @endforeach
</div>
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-bold text-slate-900"><i class="fa-solid fa-box-archive mr-2 text-blue-600"></i>Kesiapan Pemeriksaan Backup</h3>
        <span class="text-xs text-slate-500">Berdasarkan sumber bukti backup yang terdaftar</span>
    </div>
    <p class="mb-4 text-sm text-slate-500">Untuk PADUKA dan SIPO, sumber backup adalah satu VM Proxmox yang sama pada NAS Synology. PASS hanya jika berkas vzdump VM terkait ditemukan; keberadaan berkas tidak membuktikan restore berhasil.</p>
    <div class="flex flex-wrap gap-2">
    @forelse($backupReadiness as $source)
        <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
            <span class="font-semibold">{{ $source['name'] }}</span>
            <span class="ml-2 rounded-full px-2 py-1 text-xs {{ $source['configured']?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-800' }}">
                {{ $source['configured']?'Sumber Terdaftar':'Sumber Belum Terdaftar' }}
            </span>
        </div>
    @empty
        <span class="text-sm text-slate-500">Belum ada aplikasi terdaftar.</span>
    @endforelse
    </div>
    <p class="mt-4 text-xs text-slate-500">Parameter PADUKA/SIPO: PROXMOX_NAS_BACKUP. Membutuhkan akses read-only ke folder dump NAS dan konfigurasi VM ID bersama. Uji pemulihan tetap merupakan proses terpisah.</p>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 p-5">
        <h3 class="font-bold text-slate-900">Jadwal PM Terbaru</h3>
        <a class="text-sm font-semibold text-blue-600" href="{{ route('admin.pm.schedules.index') }}">Lihat semua</a>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Kode / Jadwal</th><th class="p-4">Frekuensi</th><th class="p-4">Target</th><th class="p-4">Status</th></tr></thead>
        <tbody>
        @forelse($schedules as $schedule)
        <tr class="border-t border-slate-100">
            <td class="p-4"><span class="block text-xs text-slate-500">{{ $schedule->code }}</span><span class="font-semibold">{{ $schedule->name }}</span></td>
            <td class="p-4">{{ \App\Models\PmSchedule::FREQUENCIES[$schedule->frequency] ?? $schedule->frequency }}</td>
            <td class="p-4">{{ $schedule->systems_count }} sistem</td>
            <td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $schedule->status === 'ready' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $schedule->status === 'ready' ? 'Siap' : 'Draft' }}</span></td>
        </tr>
        @empty<tr><td colspan="4" class="p-6 text-center text-slate-500">Belum ada jadwal. Mulai dengan menambahkan master sistem dan parameter.</td></tr>@endforelse
        </tbody>
    </table></div>
</div>
@endsection