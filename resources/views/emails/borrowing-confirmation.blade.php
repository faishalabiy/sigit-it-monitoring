<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f8; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 0 auto; background: #fff; }
  .header { background: #1e2340; padding: 32px 40px; text-align: center; }
  .header h1 { color: #fff; font-size: 22px; margin: 0; }
  .header p  { color: #94a3b8; margin: 4px 0 0; font-size: 14px; }
  .body   { padding: 32px 40px; }
  .greeting { font-size: 16px; color: #0f1117; margin-bottom: 16px; }
  .code-box { background: #eff4ff; border: 1.5px dashed #3b82f6; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
  .code-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
  .code-value { font-size: 28px; font-weight: 700; color: #2563eb; letter-spacing: 4px; font-family: 'Courier New', monospace; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
  td { padding: 10px 14px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
  td:first-child { color: #64748b; font-weight: 600; width: 40%; }
  td:last-child  { color: #0f1117; }
  .note { background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-top: 16px; }
  .footer { padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
  .badge { display: inline-block; background: #f0fdf4; color: #16a34a; padding: 3px 10px; border-radius: 100px; font-size: 13px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>💻 IAMS</h1>
    <p>IT Asset Management System</p>
  </div>
  <div class="body">
    <p class="greeting">Halo, <strong>{{ $borrowing->borrower_name }}</strong>!</p>
    <p style="color:#374151;font-size:14px;line-height:1.7">
      Peminjaman aset IT Anda telah berhasil diajukan dan <span class="badge">✅ Disetujui Otomatis</span>.
      Berikut adalah detail peminjaman Anda:
    </p>

    <div class="code-box">
      <div class="code-label">Kode Peminjaman — Simpan kode ini!</div>
      <div class="code-value">{{ $borrowing->borrow_code }}</div>
    </div>

    <table>
      <tr><td>Aset</td><td>{{ $borrowing->asset->category_icon }} {{ $borrowing->asset->name }} ({{ $borrowing->asset->asset_code }})</td></tr>
      <tr><td>Kategori</td><td>{{ $borrowing->asset->category_label }}</td></tr>
      <tr><td>Tanggal Pinjam</td><td>{{ $borrowing->borrow_date->format('d F Y') }}</td></tr>
      <tr><td>Rencana Kembali</td><td>{{ $borrowing->planned_return_date->format('d F Y') }}</td></tr>
      <tr><td>Keperluan</td><td>{{ $borrowing->purpose }}</td></tr>
      @if($borrowing->destination)
      <tr><td>Lokasi Penggunaan</td><td>{{ $borrowing->destination }}</td></tr>
      @endif
    </table>

    <div class="note">
      ⚠️ <strong>Penting:</strong> Simpan kode peminjaman <strong>{{ $borrowing->borrow_code }}</strong> untuk digunakan saat pengembalian aset. Pastikan aset dikembalikan sebelum atau pada tanggal <strong>{{ $borrowing->planned_return_date->format('d F Y') }}</strong>.
    </div>
  </div>
  <div class="footer">
    Email ini dikirim otomatis oleh sistem IAMS. Jangan balas email ini.
  </div>
</div>
</body>
</html>
