<!doctype html>
<html lang="id">
<head><meta charset="utf-8"/>
<style>
@page{margin:24px 26px 30px 26px}
body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#162234}
.header{border-bottom:3px solid #1d4ed8;padding-bottom:10px;margin-bottom:12px}
h1{font-size:17px;color:#0f172a;margin:0 0 4px}
h2{font-size:11px;margin:18px 0 8px;padding:6px;background:#eaf0fc;color:#172c55}
.muted{color:#64748b}.tiny{font-size:7px}.meta{width:100%;border-collapse:collapse}
.meta td{padding:4px 8px 4px 0;vertical-align:top;width:50%}
table.data{width:100%;border-collapse:collapse;margin-bottom:10px;table-layout:fixed}
.data th{background:#1e3a8a;color:#fff;text-align:left;padding:6px;font-size:8px}
.data td{border-bottom:1px solid #d5ddec;padding:5px;vertical-align:top;overflow-wrap:break-word;font-size:8px}
.kpi{width:100%;margin-top:12px}.kpi td{background:#eff5ff;padding:9px;text-align:center}
.stamp{margin-top:18px;border:1px solid #bdc9df;padding:8px}
.pagebreak{page-break-before:always}
.footer{position:fixed;bottom:-20px;font-size:7px;color:#64748b;border-top:1px solid #d4dbe8;padding-top:4px;width:100%}
</style></head><body>
<div class="header">
 <div class="muted">PT REKAINDO GLOBAL JASA · SIGIT / SISTEM INFORMASI</div>
 <h1>LAPORAN PREVENTIVE MAINTENANCE</h1>
 <div>Nomor: <strong>{{ $report['document_number'] }}</strong> · Dokumen Resmi / FINAL</div>
</div>
<table class="meta">
 <tr><td><b>Jadwal</b>: {{ $report['schedule_name'] }} ({{ $report['schedule_code'] }})</td>
     <td><b>Pemicu</b>: {{ $report['trigger']==='manual'?'Manual':'Scheduler Otomatis' }}</td></tr>
 <tr><td><b>Pelaksanaan</b>: {{ $report['started_at'] }} WIB</td>
     <td><b>Selesai</b>: {{ $report['completed_at'] }} WIB</td></tr>
 <tr><td><b>Petugas / Sumber</b>: {{ $report['performed_by'] }}</td>
     <td><b>Hasil pemeriksaan saat pelaksanaan</b>: {{ match($report['execution_status']){'completed'=>'Semua Normal','needs_review'=>'Ada Temuan','failed'=>'Pemeriksaan Gagal',default=>'Sedang Berjalan'} }}</td></tr>
</table>
<h2>1. Ringkasan Pemeriksaan</h2>
<table class="kpi"><tr>
@foreach(['total'=>'TOTAL','systems'=>'SISTEM','pass'=>'PASS','warning'=>'WARNING','fail'=>'FAIL','unavailable'=>'BELUM TERSEDIA'] as $key=>$label)
<td><b>{{ $report['summary'][$key] ?? 0 }}</b><br/><span class="tiny">{{ $label }}</span></td>
@endforeach
</tr></table>
<h2>2. Hasil Pemeriksaan Seluruh Target</h2>
<table class="data">
<thead><tr><th style="width:19%">Sistem / Kode</th><th style="width:23%">Parameter</th><th style="width:11%">Nilai</th><th style="width:10%">Status</th><th style="width:37%">Keterangan</th></tr></thead>
<tbody>@foreach($report['checks'] as $check)
<tr><td><b>{{ $check['system_name'] }}</b><br/><span class="muted">{{ $check['system_code'] }}</span></td>
<td>{{ $check['parameter_name'] }}<br/><span class="muted tiny">{{ $check['parameter_code'] }}</span><br/>
<span class="muted tiny">Acuan: {{ $check['thresholds']['expected'] ?? '-' }}</span><br/>
<span class="muted tiny">Warning min/max: {{ $check['thresholds']['warning_min'] ?? '-' }} / {{ $check['thresholds']['warning_max'] ?? '-' }}</span><br/>
<span class="muted tiny">Fail min/max: {{ $check['thresholds']['failure_min'] ?? '-' }} / {{ $check['thresholds']['failure_max'] ?? '-' }}</span></td>
<td>{{ $check['value'] ?? '-' }} {{ $check['unit'] ?? '' }}</td>
<td><b>{{ strtoupper($check['status']) }}</b></td>
<td>{{ $check['message'] }}</td></tr>
@endforeach</tbody></table>
<h2>3. Temuan dan Rekomendasi (Snapshot Saat Finalisasi)</h2>
<table class="data"><thead><tr><th>Nomor / Sistem</th><th>Temuan & Rekomendasi</th><th>PIC</th><th>Status</th></tr></thead>
<tbody>@forelse($report['correctives'] as $item)
<tr><td><b>{{ $item['code'] }}</b><br/>{{ $item['system_name'] }}</td>
<td>{{ $item['finding'] }}<br/><b>Rekomendasi:</b> {{ $item['recommendation'] }}</td>
<td>{{ $item['assigned_to'] ?? 'Belum ditetapkan' }}<br/>{{ $item['due_on'] ?? '-' }}</td>
<td>{{ strtoupper($item['status']) }}</td></tr>
@empty<tr><td colspan="4">Tidak ada corrective yang tercatat saat laporan difinalisasi.</td></tr>@endforelse</tbody></table>
<h2>4. Review, Persetujuan & Finalisasi</h2>
<table class="meta">
<tr><td><b>Diajukan oleh:</b> {{ $report['prepared_by'] }}<br/><b>Catatan:</b> {{ $report['review_notes'] ?: '-' }}</td>
<td><b>Disetujui oleh:</b> {{ $report['approved_by'] }}<br/>{{ $report['approved_at'] }} WIB<br/><b>Catatan:</b> {{ $report['approval_notes'] ?: '-' }}</td></tr>
<tr><td colspan="2"><b>Finalisasi:</b> {{ $report['finalized_by'] }} · {{ $report['finalized_at'] }} WIB</td></tr>
</table>
<div class="stamp"><b>Informasi Dokumen:</b> Snapshot hasil PM dikunci saat finalisasi. Perkembangan corrective berikutnya dilacak terpisah; tidak mengubah isi laporan ini.</div>
<div class="footer">SIGIT · {{ $report['document_number'] }} · SHA-256: {{ $checksum }}</div>
</body></html>