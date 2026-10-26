# Preventive Maintenance - Infrastruktur dan Backup SIGIT

Modul PM menjalankan pemeriksaan server secara read-only pada `192.168.13.191`. Jadwal mingguan yang ada kini juga mencakup target **Server Aplikasi Bersama**.

## Pemeriksaan server

- Status unit systemd: Apache, MySQL, PHP-FPM 8.3, PHP-FPM 8.4, cron
- Heartbeat Laravel Scheduler SIGIT (dari perintah `pm:run-due`)
- Worker `artisan queue:work` yang berasal dari direktori SIPO

Hasil status unit bukan bukti semua fungsi aplikasi sehat. Uji DB dari health endpoint serta HTTP/SSL tetap digunakan secara terpisah. Pemantauan queue worker membaca informasi proses OS; bila identitas proses tidak dapat dibaca oleh user PHP-FPM, status dicatat **Belum Tersedia** dan tidak dianggap PASS.

## Pemeriksaan backup

Metode `backup_age` menggunakan **artefak nyata** pada direktori yang telah ditetapkan administrator lewat `.env`:

```dotenv
PM_SIPO_BACKUP_DIR=/lokasi/backup-sipo
PM_PADUKA_BACKUP_DIR=/lokasi/backup-paduka
PM_SIGIT_BACKUP_DIR=/lokasi/backup-sigit
```

Direktori harus berada di luar document root publik, berisi file backup aplikasi tersebut, dan dapat dibaca tanpa izin menulis oleh proses SIGIT. File yang didukung: `.sql`, `.sql.gz`, `.dump`, `.backup`, `.tar.gz`, `.zip`. Pemeriksaan hanya membaca ukuran dan waktu modifikasi file; tidak membaca isi, tidak mengunggah data, tidak menjalankan backup, dan tidak melakukan restore. Hasil PASS **hanya menunjukkan umur artefak berada dalam threshold**, bukan bahwa backup lengkap/aman/dapat direstore.

Saat ini bukti backup PADUKA dan SIPO **belum ditemukan/dikonfigurasi**, sehingga metode ini harus berstatus `unavailable`. Setelah lokasi valid tersedia:

1. Atur direktori pada `.env` SIGIT dan jalankan `php artisan optimize:clear`.
2. Pilih template `BACKUP_AGE_DAILY` atau `BACKUP_AGE_WEEKLY` sesuai kebijakan backup masing-masing sistem.
3. Jalankan PM manual dari Jadwal PM, periksa tanggal dan ukuran artefak.
4. Gunakan `MANUAL_BACKUP_RESTORE` untuk uji pemulihan yang dilakukan petugas.

## Keamanan

Unit service dan direktori queue worker dibatasi melalui allowlist kode, bukan perintah yang dapat diketik pengguna. Pemeriksaan tidak menjalankan restart/service management. Pengaturan backup hanya di `.env` administrator server, tidak dari input form web.
