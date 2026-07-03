<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f8; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 0 auto; background: #fff; }
  .header { background: #14532d; padding: 24px 40px; }
  .header h1 { color: #fff; font-size: 16px; margin: 0; }
  .body { padding: 28px 40px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
  td:first-child { color: #64748b; font-weight: 600; width: 40%; }
  .btn { display: inline-block; background: #16a34a; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; margin-top: 16px; }
  .footer { padding: 16px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header"><h1>✅ IAMS — Aset Dikembalikan</h1></div>
  <div class="body">
    <table>
      <tr><td>Kode</td><td style="font-family:'Courier New',monospace;font-weight:700;color:#16a34a">{{ $borrowing->borrow_code }}</td></tr>
      <tr><td>Aset</td><td>{{ $borrowing->asset->category_icon }} {{ $borrowing->asset->name }}</td></tr>
      <tr><td>Dikembalikan Oleh</td><td>{{ $borrowing->borrower_name }}</td></tr>
      <tr><td>Tanggal Kembali</td><td>{{ $borrowing->actual_return_date->format('d F Y') }}</td></tr>
      <tr><td>Kondisi</td><td>
        @php $cond = ['good'=>'✅ Baik','fair'=>'🟡 Cukup','poor'=>'🟠 Rusak Ringan','damaged'=>'❌ Rusak']; @endphp
        {{ $cond[$borrowing->return_condition] ?? '-' }}
      </td></tr>
      @if($borrowing->return_notes)
      <tr><td>Catatan</td><td>{{ $borrowing->return_notes }}</td></tr>
      @endif
    </table>
    <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="btn">Lihat Detail</a>
  </div>
  <div class="footer">IAMS Admin Notification System</div>
</div>
</body>
</html>
