@extends('layouts.admin')
@section('title','Hasil Pemeriksaan PM')
@section('page-title','Hasil Preventive Maintenance')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ route('admin.pm.runs.index') }}" class="text-sm font-semibold text-blue-600">← Riwayat PM</a>
        <h2 class="mt-2 text-2xl font-bold">Laporan Monitoring PM #{{ $run->id }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $run->schedule_name }} · {{ $run->schedule_code }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.pm.correctives.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Tindak Lanjut</a>
        @if($run->review_status==='finalized')
        <a class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.pm.runs.pdf',$run) }}">Download PDF Resmi</a>
        <a class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.pm.runs.excel',$run) }}">Download Excel</a>
        @endif
        @if($run->review_status!=='finalized')
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800"><i class="fa-solid fa-print mr-1"></i> Cetak Pratinjau (Belum Sah)</button>
        @endif
        @if($run->schedule && auth()->user()->hasPermission('pm.manage') && $run->schedule->status==='ready')
            <form method="POST" action="{{ route('admin.pm.schedules.run',$run->schedule) }}" onsubmit="return confirm('Jalankan pemeriksaan baru pada jadwal ini?')">@csrf
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Jalankan Ulang</button>
            </form>
        @endif
    </div>
</div>
@if($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h3 class="font-bold">Review dan Persetujuan Laporan</h3>
        <p class="text-sm text-slate-500">Status dokumen terpisah dari kondisi parameter dan progres corrective.</p></div>
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $run->review_status==='finalized'?'bg-emerald-50 text-emerald-800':($run->review_status==='approved'?'bg-blue-50 text-blue-800':'bg-amber-50 text-amber-800') }}">
            {{ ['draft'=>'Draft','under_review'=>'Menunggu Review','approved'=>'Disetujui','finalized'=>'Final / Terkunci'][$run->review_status] ?? 'Draft' }}
        </span>
    </div>
    <div class="mt-3 grid gap-2 text-xs text-slate-600 md:grid-cols-3">
      <div>Pengaju: {{ $run->reviewRequester->name ?? '-' }}<br/>{{ $run->review_requested_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
      <div>Penyetuju: {{ $run->approver->name ?? '-' }}<br/>{{ $run->approved_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
      <div>Finalisasi: {{ $run->finalizer->name ?? '-' }}<br/>{{ $run->finalized_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
    </div>
    @if($run->review_notes)<p class="mt-3 text-xs text-slate-600"><strong>Catatan Review/Pengecualian:</strong> {{ $run->review_notes }}</p>@endif
    @if($run->approval_notes)<p class="mt-2 text-xs text-slate-600"><strong>Catatan Approval:</strong> {{ $run->approval_notes }}</p>@endif
    @if($run->review_status==='finalized')
      <p class="mt-3 text-xs text-emerald-700">Dokumen final telah dibekukan. Hash SHA-256: <code class="break-all">{{ $run->report_sha256 }}</code></p>
      <p class="mt-1 text-xs text-slate-500">Corrective tetap dapat berjalan, tetapi PDF/Excel final memakai snapshot saat persetujuan.</p>
    @else
      <div class="mt-4 grid gap-4 md:grid-cols-2">
      @if($run->review_status==='draft' && auth()->user()->hasPermission('pm.manage') && $run->review_status==='draft')
      <form method="POST" action="{{ route('admin.pm.runs.approval',[$run,'submit']) }}" class="rounded-lg bg-slate-50 p-4">
        @csrf <label class="block text-sm font-semibold">Ajukan Review</label>
        <p class="my-1 text-xs text-slate-500">Selesaikan seluruh checklist manual. Jika ada item Belum Tersedia, jelaskan pengecualiannya di sini.</p>
        <textarea name="notes" rows="3" maxlength="3000" class="mt-2 w-full rounded-lg border-slate-300 text-sm" placeholder="Catatan pelaksanaan / alasan item belum tersedia">{{ old('notes') }}</textarea>
        <button class="mt-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Ajukan Review</button>
      </form>
      @elseif($run->review_status==='under_review' && auth()->user()->hasPermission('pm.approve'))
      <div class="rounded-lg bg-slate-50 p-4">
        <p class="mb-2 text-sm font-semibold">Keputusan Reviewer</p>
        <form method="POST" action="{{ route('admin.pm.runs.approval',[$run,'approve']) }}" class="mb-3">
          @csrf <textarea name="notes" rows="2" maxlength="3000" placeholder="Catatan persetujuan (opsional)" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
          <button class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Setujui Laporan</button>
        </form>
        <form method="POST" action="{{ route('admin.pm.runs.approval',[$run,'reject']) }}">
          @csrf <textarea name="notes" rows="2" maxlength="3000" required minlength="10" placeholder="Alasan dikembalikan (wajib)" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
          <button class="mt-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Kembalikan untuk Revisi</button>
        </form>
      </div>
      @elseif($run->review_status==='approved' && auth()->user()->hasPermission('pm.approve'))
      <form method="POST" action="{{ route('admin.pm.runs.approval',[$run,'finalize']) }}" onsubmit="return confirm('Finalisasi mengunci hasil pemeriksaan. Lanjutkan?')" class="rounded-lg bg-slate-50 p-4">
        @csrf <p class="mb-2 text-sm font-semibold">Finalisasi Dokumen</p>
        <p class="text-xs text-slate-500">Hanya pemberi approval dapat memfinalisasi. Dokumen PDF/Excel akan memakai snapshot tetap.</p>
        <button class="mt-3 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Finalisasi dan Kunci</button>
      </form>
      @endif
      </div>
    @endif
    @if($run->approvalEvents->isNotEmpty())
    <div class="mt-4 border-t border-slate-100 pt-3"><p class="text-xs font-semibold">Histori Persetujuan</p>
      @foreach($run->approvalEvents as $event)<p class="mt-1 text-xs text-slate-500">{{ $event->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB · {{ $event->actor->name ?? 'Sistem' }} · {{ strtoupper($event->event) }} @if($event->notes)— {{ $event->notes }}@endif</p>@endforeach
    </div>@endif
</div>
@if($run->review_status!=='finalized')
<div class="mb-4 border-2 border-dashed border-amber-400 bg-amber-50 px-4 py-3 text-center text-sm font-bold text-amber-900">
    PRATINJAU — LAPORAN BELUM FINAL / BELUM DAPAT DIJADIKAN DOKUMEN RESMI
</div>
@endif
<div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
    <i class="fa-solid fa-circle-info mr-1"></i> Pemeriksaan HTTP, waktu respons, dan SSL diambil dari target HTTPS perusahaan.
    Database dan service aplikasi yang telah terintegrasi dibaca dari health endpoint terlindungi.
    Monitoring server menggunakan identitas host terdaftar. Pemeriksaan tanpa sumber valid tetap BELUM TERSEDIA dan checklist manual menunggu verifikasi petugas.
</div>
<div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-6">
    @foreach([
        ['label'=>'Total','count'=>$run->summary['total'] ?? 0,'style'=>'slate'],
        ['label'=>'PASS','count'=>$run->summary['pass'] ?? 0,'style'=>'emerald'],
        ['label'=>'WARNING','count'=>$run->summary['warning'] ?? 0,'style'=>'amber'],
        ['label'=>'FAIL','count'=>$run->summary['fail'] ?? 0,'style'=>'red'],
        ['label'=>'Perlu Manual','count'=>$run->summary['pending'] ?? 0,'style'=>'slate'],
        ['label'=>'Belum Tersedia','count'=>$run->summary['unavailable'] ?? 0,'style'=>'slate']
    ] as $stat)
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs text-slate-500">{{ $stat['label'] }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stat['count'] }}</p>
    </div>
    @endforeach
</div>
<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Waktu Pelaksanaan (WIB)</div><div class="mt-1 text-sm font-semibold">{{ $run->started_at?->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}</div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="text-xs text-slate-500">Pemicu</div><div class="mt-1 text-sm font-semibold">{{ $run->trigger==='manual'?'Manual · '.($run->initiator->name ?? 'Admin'):'Jadwal Otomatis' }}</div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs text-slate-500">Hasil Pemeriksaan Saat Pelaksanaan</div>
        <div class="mt-1 text-sm font-semibold">{{ $run->result_label }}</div>
        <p class="mt-1 text-xs text-slate-500">Berbeda dari approval dokumen. Nilai historis tidak berubah setelah corrective selesai.</p>
    </div>
</div>
@if($run->review_status==='finalized' && $run->status==='needs_review')
<div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
    <strong>Laporan sudah FINAL dan disetujui.</strong>
    Label <strong>Ada Temuan</strong> menggambarkan hasil saat pemeriksaan dilakukan, bukan status persetujuan.
    {{ $run->correctives->where('status','resolved')->count() }} dari {{ $run->correctives->count() }} corrective telah diselesaikan.
    @if(($run->summary['unavailable']??0)>0)
        {{ $run->summary['unavailable'] }} parameter tetap tercatat Belum Tersedia pada pengukuran historis.
    @endif
    Untuk memastikan kondisi terbaru, jalankan PM baru.
</div>
@endif
<div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
        <div><h3 class="font-bold">Temuan dan Rekomendasi Tindak Lanjut</h3>
            <p class="text-xs text-slate-500">Status corrective terkini. Tanggal, nilai aktual, dan evaluasi hasil pemeriksaan tetap historis.</p>
        </div>
        <span class="rounded-lg bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $run->correctives->where('status','!=','resolved')->count() }} tindak lanjut belum selesai</span>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Temuan</th><th class="p-3">Rekomendasi</th><th class="p-3">PIC & Tenggat</th><th class="p-3">Status Corrective</th></tr></thead>
        <tbody>@forelse($run->correctives as $action)
        <tr class="border-t border-slate-100">
            <td class="p-3"><span class="block font-semibold">{{ $action->check->system_name ?? 'Sistem' }} / {{ $action->check->parameter_name ?? 'Parameter' }}</span>
            <span class="text-xs text-slate-500">{{ strtoupper($action->check->status ?? '') }} · CA-{{ str_pad($action->id,5,'0',STR_PAD_LEFT) }}</span></td>
            <td class="max-w-md p-3 text-slate-600">{{ $action->recommendation ?? 'Perlu analisis lanjutan' }}</td>
            <td class="p-3">{{ $action->assignee->name ?? 'Belum ditugaskan' }}
                <span class="block text-xs text-slate-500">{{ $action->due_on?->format('d M Y') ?? 'Tanpa tenggat' }}</span></td>
            <td class="p-3"><span class="block font-semibold">{{ ['open'=>'Open','in_progress'=>'Dalam Proses','resolved'=>'Selesai'][$action->status] ?? $action->status }}</span>
                <a class="text-xs font-semibold text-blue-600 underline" href="{{ route('admin.pm.correctives.show',$action) }}">Detail tindak lanjut</a></td>
        </tr>
        @empty<tr><td colspan="4" class="p-4 text-slate-500">Tidak ada temuan corrective yang dicatat dari pelaksanaan ini. Hasil Perlu Manual atau Belum Tersedia tetap harus ditinjau bila relevan.</td></tr>@endforelse
        </tbody></table></div>
</div>
@php
    $systemSummaries=$run->checks->groupBy('system_code')->map(function($checks) {
        return [
            'name'=>$checks->first()->system_name,
            'pass'=>$checks->where('status','pass')->count(),
            'warning'=>$checks->where('status','warning')->count(),
            'fail'=>$checks->where('status','fail')->count(),
            'pending'=>$checks->where('status','pending')->count(),
            'unavailable'=>$checks->where('status','unavailable')->count(),
        ];
    });
@endphp
<div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 p-4"><h3 class="font-bold">Ringkasan Kondisi per Sistem</h3>
    <p class="text-xs text-slate-500">Hasil pada saat PM dijalankan. Status temuan historis tidak berubah ketika corrective ditutup.</p></div>
  <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr>
    <th class="p-3">Sistem</th><th class="p-3">PASS</th><th class="p-3">WARNING</th><th class="p-3">FAIL</th><th class="p-3">Perlu Manual</th><th class="p-3">Belum Tersedia</th></tr></thead>
    <tbody>@forelse($systemSummaries as $summary)
    <tr class="border-t border-slate-100"><td class="p-3 font-semibold">{{ $summary['name'] }}</td>
    <td class="p-3">{{ $summary['pass'] }}</td><td class="p-3">{{ $summary['warning'] }}</td>
    <td class="p-3">{{ $summary['fail'] }}</td><td class="p-3">{{ $summary['pending'] }}</td>
    <td class="p-3">{{ $summary['unavailable'] }}</td></tr>
    @empty<tr><td colspan="6" class="p-4 text-slate-500">Tidak ada hasil.</td></tr>@endforelse</tbody>
  </table></div>
</div>
@forelse($run->checks->groupBy('system_code') as $code=>$checks)
<div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
        <h3 class="font-bold">{{ $checks->first()->system_name }}</h3><p class="text-xs text-slate-500">{{ $code }}</p>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-white text-xs uppercase tracking-wide text-slate-500"><tr><th class="p-4">Parameter</th><th class="p-4">Hasil Aktual</th><th class="p-4">Evaluasi</th><th class="p-4">Keterangan</th></tr></thead><tbody>
        @foreach($checks as $check)
        <tr class="border-t border-slate-100">
            <td class="p-4"><span class="font-medium">{{ $check->parameter_name }}</span><span class="block text-xs text-slate-500">{{ $check->parameter_code }}</span></td>
            <td class="p-4">{{ $check->value ?? '—' }} {{ $check->value !== null ? $check->unit : '' }}</td>
            <td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ match($check->status){'pass'=>'bg-emerald-50 text-emerald-700','warning'=>'bg-amber-50 text-amber-800','fail'=>'bg-red-50 text-red-700',default=>'bg-slate-100 text-slate-700'} }}">{{ match($check->status){'pass'=>'PASS','warning'=>'WARNING','fail'=>'FAIL','pending'=>'PERLU MANUAL',default=>'BELUM TERSEDIA'} }}</span></td>
            <td class="p-4 text-slate-600">
                <p>{{ $check->message }}</p>
                @if($check->correctiveAction)
                    <a class="mt-2 inline-flex text-xs font-bold text-blue-700 underline" href="{{ route('admin.pm.correctives.show',$check->correctiveAction) }}">
                        Corrective CA-{{ str_pad($check->correctiveAction->id,5,'0',STR_PAD_LEFT) }} · {{ ['open'=>'Open','in_progress'=>'Proses','resolved'=>'Selesai'][$check->correctiveAction->status] ?? $check->correctiveAction->status }}
                    </a>
                @elseif(in_array($check->status,['warning','fail','unavailable']) && auth()->user()->hasPermission('pm.manage') && $run->review_status==='draft')
                    <form class="mt-2" action="{{ route('admin.pm.correctives.from-check',[$run,$check]) }}" method="POST">@csrf
                        <button class="text-xs font-semibold text-blue-700 underline">Buat Tindak Lanjut</button>
                    </form>
                @endif
                @if($check->method==='manual_check' && $check->status==='pending' && auth()->user()->hasPermission('pm.manage') && $run->review_status==='draft')
                <form method="POST" action="{{ route('admin.pm.runs.checks.review',[$run,$check]) }}" class="mt-3 space-y-2">
                    @csrf @method('PATCH')
                    <label class="block text-xs font-medium">Hasil checklist manual</label>
                    <select name="status" required class="w-full rounded-lg border-slate-300 text-xs">
                        <option value="pass">PASS — Normal</option>
                        <option value="warning">WARNING — Temuan ringan</option>
                        <option value="fail">FAIL — Temuan kritis</option>
                    </select>
                    <textarea name="notes" maxlength="500" rows="2" required placeholder="Tuliskan hasil verifikasi dan bukti singkat (tanpa data sensitif)" class="w-full rounded-lg border-slate-300 text-xs"></textarea>
                    <button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white">Simpan Hasil Manual</button>
                </form>
                @endif
                @if($check->reviewed_at)
                <p class="mt-1 text-xs text-slate-400">Diverifikasi: {{ $check->reviewed_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
                @endif
            </td>
        </tr>@endforeach
        </tbody></table>
    </div>
</div>
@empty
<div class="rounded-xl bg-white p-7 text-center text-slate-500">Belum ada hasil pemeriksaan tersimpan.</div>
@endforelse
<style media="print">@media print{aside,header,form,button,a[href]{display:none!important} main{margin-left:0!important} body{background:white!important}section{padding:0!important}}</style>
@endsection