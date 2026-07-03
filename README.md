# IAMS — IT Asset Management System
> Sistem Peminjaman Aset IT dengan Laravel

---

## 📋 Fitur Utama

### Halaman Publik (Tanpa Login)
- **Form Peminjaman** — Ajukan peminjaman aset IT secara mandiri
- **Form Pengembalian** — Kembalikan aset dengan kode peminjaman
- **Cek Status** — Cek status peminjaman via kode + email
- **Auto-approve** — Peminjaman langsung disetujui otomatis

### Panel Admin (Login Required)
- **Dashboard** — Statistik real-time, aset overdue, grafik tren
- **Manajemen Aset** — CRUD lengkap, QR code generator, upload foto
- **Peminjaman** — Monitor semua transaksi, update status manual
- **Laporan** — Filter periode, export PDF
- **Notifikasi Email** — Konfirmasi ke peminjam + notifikasi ke admin

---

## ⚡ Instalasi

### 1. Clone & Setup Laravel
```bash
composer create-project laravel/laravel iams
cd iams
```

### 2. Copy File Proyek
Salin semua file dari folder ini ke dalam proyek Laravel Anda (sesuaikan struktur direktori).

### 3. Install Dependencies
```bash
composer require simplesoftwareio/simple-qrcode
composer require barryvdh/laravel-dompdf
```

### 4. Konfigurasi `.env`
```env
APP_NAME=IAMS
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iams_db
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@iams.local
MAIL_FROM_NAME="IAMS System"

# IAMS Custom Config
ADMIN_EMAIL=admin@perusahaan.com
ADMIN_NAME="IT Admin"
ORG_NAME="Nama Perusahaan / Instansi"
IAMS_AUTO_APPROVE=true
IAMS_MAX_BORROW_DAYS=30
```

### 5. Setup Database
```bash
php artisan migrate
php artisan db:seed
```

### 6. Storage Link
```bash
php artisan storage:link
```

### 7. Jalankan Server
```bash
php artisan serve
```

---

## 🔑 Akun Default
| Role  | Email             | Password |
|-------|-------------------|----------|
| Admin | admin@iams.local  | password |

---

## 🗂️ Struktur File Penting

```
app/
├── Http/Controllers/
│   ├── Public/
│   │   └── LandingController.php     ← Halaman publik & form
│   └── Admin/
│       ├── DashboardController.php
│       ├── AssetController.php
│       ├── BorrowingController.php
│       └── ReportController.php
├── Models/
│   ├── Asset.php
│   └── Borrowing.php
└── Mail/
    ├── BorrowingConfirmation.php
    ├── ReturnConfirmation.php
    ├── AdminBorrowingNotification.php
    └── AdminReturnNotification.php

resources/views/
├── public/
│   ├── landing.blade.php             ← ★ Halaman utama publik
│   ├── borrow-success.blade.php
│   └── return-success.blade.php
├── admin/
│   ├── dashboard/index.blade.php
│   ├── assets/{index,create,edit,show}.blade.php
│   ├── borrowings/{index,show}.blade.php
│   └── reports/{index,pdf}.blade.php
├── layouts/admin.blade.php
└── emails/
    ├── borrowing-confirmation.blade.php
    ├── return-confirmation.blade.php
    ├── admin-borrowing-notification.blade.php
    └── admin-return-notification.blade.php

database/
├── migrations/
│   ├── ..._create_assets_table.php
│   └── ..._create_borrowings_table.php
└── seeders/DatabaseSeeder.php

routes/web.php
config/iams.php
```

---

## 📡 Routes

### Publik
| Method | URL                        | Deskripsi               |
|--------|----------------------------|-------------------------|
| GET    | `/`                        | Landing page            |
| POST   | `/pinjam`                  | Submit form peminjaman  |
| POST   | `/kembali`                 | Submit form pengembalian|
| GET    | `/pinjam/berhasil/{code}`  | Halaman sukses pinjam   |
| GET    | `/kembali/berhasil/{code}` | Halaman sukses kembali  |
| GET    | `/api/cek-status`          | AJAX cek status         |
| GET    | `/api/aset-tersedia`       | AJAX list aset tersedia |

### Admin (auth required)
| Method | URL                             | Deskripsi           |
|--------|---------------------------------|---------------------|
| GET    | `/admin/`                       | Dashboard           |
| GET    | `/admin/aset`                   | Daftar aset         |
| GET    | `/admin/aset/create`            | Form tambah aset    |
| POST   | `/admin/aset/{id}/qrcode`       | Generate QR code    |
| GET    | `/admin/peminjaman`             | Daftar peminjaman   |
| PATCH  | `/admin/peminjaman/{id}/status` | Update status       |
| GET    | `/admin/laporan`                | Laporan             |
| GET    | `/admin/laporan/export/pdf`     | Export PDF          |

---

## 📦 Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "simplesoftwareio/simple-qrcode": "^4.2",
        "barryvdh/laravel-dompdf": "^3.0"
    }
}
```

---

## 🎨 Aset yang Didukung
- 💻 **Laptop / Notebook**
- 📽️ **Proyektor**
- 📷 **Kamera / Peralatan Foto**
- 🔌 **Perangkat Jaringan** (Router, Switch, AP)
- 📦 **Lainnya**

---

## 🔄 Alur Sistem

```
Publik buka landing page
  └── Pilih aset dari dropdown
  └── Isi form peminjaman
  └── Submit → Auto-approved
  └── Terima email konfirmasi + Kode peminjaman
  └── Admin terima notifikasi email

Saat hendak mengembalikan:
  └── Buka landing page → Form Pengembalian
  └── Masukkan kode peminjaman + email
  └── Pilih kondisi aset
  └── Submit → Aset kembali "Tersedia"
  └── Terima email konfirmasi pengembalian
  └── Admin terima notifikasi email
```
