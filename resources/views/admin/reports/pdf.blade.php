<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; color: #0f1117; }
  .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 16px; }
  .header h1 { font-size: 16px; margin: 0 0 4px; color: #1e2340; }
  .header p  { font-size: 11px; color: #64748b; margin: 0; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th { background: #1e2340; color: #fff; padding: 7px 8px; text-align: left; }
  td { padding: 6px 8px; border-bottom: 1px solid #e2e5ed; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; }
  .badge-approved  { background: #dbeafe; color: #1e40af; }
  .badge-borrowed  { background: #fef9c3; color: #854d0e; }
  .badge-returned  { background: #f0fdf4; color: #16a34a; }
  .badge-overdue   { background: #fef2f2; color: #dc2626; }
  .badge-cancelled { background: #f4f5f8; color: #64748b; }
  .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $title }}</h1>
    <p>{{ $dateRange }} &mdash; Dicetak: {{ now()->format('d F Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Kode</th>
            <th>Aset</th>
            <th>Peminjam</th>
            <th>Departemen</th>
            <th>Tgl Pinjam</th>
            <th>Rencana Kembali</th>
            <th>Aktual Kembali</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($borrowings as $i => $b)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-family:monospace;font-weight:700;color:#2563eb">{{ $b->borrow_code }}</td>
            <td>{{ $b->asset->name ?? '-' }}</td>
            <td>{{ $b->borrower_name }}</td>
            <td>{{ $b->borrower_department ?? '-' }}</td>
            <td>{{ $b->borrow_date->format('d/m/Y') }}</td>
            <td>{{ $b->planned_return_date->format('d/m/Y') }}</td>
            <td>{{ $b->actual_return_date?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="badge badge-{{ $b->status }}">{{ $b->status_info['label'] }}</span></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:16px;color:#94a3b8">Tidak ada data</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Total: {{ $borrowings->count() }} transaksi &mdash; IAMS IT Asset Management System
</div>
</body>
</html>
