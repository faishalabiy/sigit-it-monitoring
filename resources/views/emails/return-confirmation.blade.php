<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f8; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 0 auto; background: #fff; }
  .header { background: #14532d; padding: 32px 40px; text-align: center; }
  .header h1 { color: #fff; font-size: 22px; margin: 0; }
  .header p  { color: #86efac; margin: 4px 0 0; font-size: 14px; }
  .body   { padding: 32px 40px; }
  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
  td { padding: 10px 14px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
  td:first-child { color: #64748b; font-weight: 600; width: 40%; }
  .footer { padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
  .big-check { font-size: 48px; text-align: center; margin-bottom: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>💻 IAMS</h1>
    <p>IT Asset Management System</p>
  </div>
  <div class="body">
    <div class="big-check">🎉</div>
    <p style="text-align:center;font-size:18px;font-weight:700;color:#0f1117">Pengembalian Berhasil!</p>
    <p style="color:#374151;font-size:14px;line-height:1.7;text-align:center;margin-bottom:20px">
      Terima kasih, <strong>{{ $borrowing->borrower_name }}</strong>. Aset telah berhasil dikembalikan.
    </p>

    <table>
      <tr><td>Kode Peminjaman</td><td style="font-family:'Courier New',monospace;font-weight:700;color:#2563eb">{{ $borrowing->borrow_code }}</td></tr>
      <tr><td>Aset</td><td>{{ $borrowing->asset->category_icon }} {{ $borrowing->asset->name }}</td></tr>
      <tr><td>Tanggal Dikembalikan</td><td>{{ $borrowing->actual_return_date->format('d F Y') }}</td></tr>
      <tr><td>Kondisi Saat Kembali</td><td>
        @php $cond = ['good'=>'✅ Baik','fair'=>'🟡 Cukup','poor'=>'🟠 Rusak Ringan','damaged'=>'❌ Rusak']; @endphp
        {{ $cond[$borrowing->return_condition] ?? '-' }}
      </td></tr>
      @if($borrowing->return_notes)
      <tr><td>Catatan</td><td>{{ $borrowing->return_notes }}</td></tr>
      @endif
    </table>
  </div>
  <div class="footer">
    Email ini dikirim otomatis oleh sistem IAMS. Jangan balas email ini.
  </div>
</div>
</body>
</html>
