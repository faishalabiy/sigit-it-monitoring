@extends('layouts.admin')
@section('title','Riwayat Pelaksanaan PM')
@section('page-title','Riwayat PM')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div><a href="{{ route('admin.pm.index') }}" class="text-sm font-semibold text-blue-600">← Dashboard PM</a>
        <h2 class="mt-2 text-2xl font-bold">Riwayat Monitoring Preventive Maintenance</h2>
        <p class="text-sm text-slate-500">Status hasil menunjukkan temuan saat pemeriksaan. Status dokumen menunjukkan persetujuan; corrective dipantau secara terpisah.</p>
    </div>
    <a href="{{ route('admin.pm.schedules.index') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Jadwal & Jalankan PM</a>
</div>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm">
<thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">Pelaksanaan</th><th class="p-4">Sumber</th><th class="p-4">Waktu Mulai</th><th class="p-4">Ringkasan</th><th class="p-4">Hasil Pemeriksaan</th><th class="p-4">Status Dokumen</th><th class="p-4">Tindak Lanjut</th><th class="p-4">Detail</th></tr></thead>
<tbody>
@forelse($runs as $run)
<tr class="border-t border-slate-100">
    <td class="p-4"><span class="block font-semibold">{{ $run->schedule_name }}</span><span class="text-xs text-slate-500">#{{ $run->id }} · {{ $run->schedule_code }}</span></td>
    <td class="p-4">{{ $run->trigger==='manual'?'Manual':'Jadwal Otomatis' }}</td>
    <td class="p-4">{{ $run->started_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
    <td class="p-4 text-slate-600">{{ $run->summary['total'] ?? 0 }} pemeriksaan</td>
    <td class="p-4">
        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $run->status==='completed'?'bg-emerald-50 text-emerald-700':($run->status==='failed'?'bg-red-50 text-red-700':'bg-amber-50 text-amber-800') }}">{{ $run->result_label }}</span>
        @if($run->status==='needs_review')
        <span class="mt-1 block text-xs text-slate-500">
            {{ $run->summary['fail'] ?? 0 }} FAIL · {{ $run->summary['warning'] ?? 0 }} WARNING · {{ $run->summary['unavailable'] ?? 0 }} belum tersedia
        </span>
        @endif
    </td>
    <td class="p-4"><span class="text-sm font-semibold {{ $run->review_status==='finalized'?'text-emerald-700':'text-slate-700' }}">{{ ['draft'=>'Draft','under_review'=>'Menunggu Approval','approved'=>'Disetujui','finalized'=>'FINAL'][$run->review_status] ?? 'Draft' }}</span></td>
    <td class="p-4">
        @if($run->corrective_total > 0)
            @php $closed=$run->corrective_total-$run->corrective_open; @endphp
            <span class="font-semibold {{ $run->corrective_open===0?'text-emerald-700':'text-amber-700' }}">{{ $closed }}/{{ $run->corrective_total }} selesai</span>
            @if(($run->summary['unavailable']??0)>0)<span class="block text-xs text-slate-500">Ada parameter belum tersedia</span>@endif
        @else
            <span class="text-xs text-slate-500">Tidak ada corrective</span>
        @endif
    </td>
    <td class="p-4"><a class="font-semibold text-blue-600" href="{{ route('admin.pm.runs.show',$run) }}">Lihat Hasil <i class="fa-solid fa-arrow-right text-xs"></i></a></td>
</tr>
@empty<tr><td colspan="8" class="p-8 text-center text-slate-500">Belum ada PM yang dijalankan. Gunakan tombol Jalankan PM Sekarang pada Jadwal PM.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $runs->links() }}</div>
@endsection