{{-- admin-borrowing-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f8; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 0 auto; background: #fff; }
  .header { background: #1e40af; padding: 24px 40px; }
  .header h1 { color: #fff; font-size: 16px; margin: 0; }
  .body { padding: 28px 40px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
  td:first-child { color: #64748b; font-weight: 600; width: 40%; }
  .btn { display: inline-block; background: #2563eb; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; margin-top: 16px; }
  .footer { padding: 16px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header"><h1>📋 IAMS — Peminjaman Baru Masuk</h1></div>
  <div class="body">
    <p style="color:#374151;font-size:14px;margin-bottom:16px">Ada permintaan peminjaman aset IT yang telah disetujui otomatis:</p>
    <table>
      <tr><td>Kode</td><td style="font-family:'Courier New',monospace;font-weight:700;color:#2563eb">{{ $borrowing->borrow_code }}</td></tr>
      <tr><td>Aset</td><td>{{ $borrowing->asset->category_icon }} {{ $borrowing->asset->name }} ({{ $borrowing->asset->asset_code }})</td></tr>
      <tr><td>Peminjam</td><td>{{ $borrowing->borrower_name }}</td></tr>
      <tr><td>Email</td><td>{{ $borrowing->borrower_email }}</td></tr>
      <tr><td>Telepon</td><td>{{ $borrowing->borrower_phone }}</td></tr>
      <tr><td>Departemen</td><td>{{ $borrowing->borrower_department ?? '-' }}</td></tr>
      <tr><td>Tanggal Pinjam</td><td>{{ $borrowing->borrow_date->format('d F Y') }}</td></tr>
      <tr><td>Rencana Kembali</td><td>{{ $borrowing->planned_return_date->format('d F Y') }}</td></tr>
      <tr><td>Keperluan</td><td>{{ $borrowing->purpose }}</td></tr>
    </table>
    <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="btn">Lihat di Admin Panel</a>
  </div>
  <div class="footer">IAMS Admin Notification System</div>
</div>
</body>
</html>
