# SIGIT Preventive Maintenance — Sprint 2

## Operational status

- Pusat Notifikasi: /admin/pm/notifications
- Monitoring Backup: /admin/pm/backups
- Cron SIGIT menjalankan Laravel Scheduler tiap menit; pemindaian notifikasi tiap 15 menit.
- Sinyal: pengingat PM H-1, pemeriksaan FAIL, corrective lewat tenggat, serta backup belum tersedia/terlambat.
- Anti-duplikat: unik berdasarkan (user_id, event_key).
- Penerima: PIC atau pemilik sistem dan super-admin. Inbox setiap pengguna terpisah.
- Email masih dinonaktifkan karena MAIL_MAILER=log; log mailer tidak mengirim ke inbox.

## Aktivasi email resmi

Setelah relai SMTP perusahaan diuji, konfigurasi MAIL_MAILER=smtp beserta server, port, autentikasi, TLS, dan alamat pengirim resmi. Kemudian set PM_EMAIL_ENABLED=true pada .env SIGIT dan jalankan php artisan optimize:clear.

Jangan menyalin password SMTP ke notulen, chat publik, atau laporan PM. Pesan yang dibuat saat email dinonaktifkan tetap bisa dibuka pada pusat notifikasi, dan tidak otomatis dikirim ulang secara massal. Percobaan pengiriman yang gagal dibatasi maksimal tiga dan diberi jeda 20 menit.

## Integrasi bukti backup

Belum ditemukan artefak backup khusus aplikasi dari folder storage SIGIT, SIPO, PADUKA yang dapat diverifikasi. Fitur backup/restore dokumen di SIPO bukan bukti backup database.

Administrator hanya boleh mendaftarkan direktori backup yang sudah diverifikasi (di luar document root):
- PM_SIPO_BACKUP_DIR=/verified/private/sipo-backups
- PM_PADUKA_BACKUP_DIR=/verified/private/paduka-backups
- PM_SIGIT_BACKUP_DIR=/verified/private/sigit-backups

Pilih BACKUP_AGE_DAILY atau BACKUP_AGE_WEEKLY pada Master Sistem sesuai kebijakan nyata. Status PASS hanya berarti waktu modifikasi artefak masih dalam batas yang ditentukan; tidak membuktikan integritas atau kemampuan restore. Uji restore tetap dilakukan terpisah.

Tanpa sumber dan jadwal backup yang sah, SIGIT menunjukkan UNAVAILABLE dan mengirim pengingat mingguan; tidak menandai PASS.

## Verifikasi

1. Buka notifikasi melalui akun PIC dan super-admin.
2. Jalankan php artisan pm:notifications:scan dua kali untuk memastikan deduplikasi.
3. Periksa notifikasi dari FAIL, corrective terlambat, PM mendatang, dan backup tidak tersedia.
4. Buka Monitoring Backup; aplikasi tanpa sumber tidak boleh PASS.
5. Pertahankan satu cron Laravel SIGIT yang sudah ada.
6. Jika SMTP siap, lakukan test email terkendali sebelum mengaktifkan PM_EMAIL_ENABLED.

Perubahan pada repository lokal dan migrasi MySQL bersifat aditif. Riwayat PM dan corrective yang ada dipertahankan.