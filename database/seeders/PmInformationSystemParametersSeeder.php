<?php

namespace Database\Seeders;

use App\Models\PmParameterTemplate;
use Illuminate\Database\Seeder;

class PmInformationSystemParametersSeeder extends Seeder
{
    /**
     * Initial PM parameter catalog for information systems.
     *
     * Idempotent by code: existing parameter configuration, including custom
     * thresholds edited by an administrator, will never be overwritten.
     * These are CONFIGURATION templates, not active monitoring probes.
     */
    public static function templates(): array
    {
        return [
            [
                'code'=>'WEB_HTTP_STATUS', 'name'=>'Ketersediaan Aplikasi Web (HTTP)',
                'category'=>'availability', 'method'=>'http_status',
                'expected_value'=>'200',
                'description'=>'Uji URL health/status aplikasi dengan respons HTTP 200. URL login yang mengembalikan redirect 302 tidak cocok sebagai endpoint uji tanpa penyesuaian. Pemeriksaan otomatis membutuhkan engine PM tahap berikutnya.',
            ],
            [
                'code'=>'WEB_RESPONSE_TIME', 'name'=>'Waktu Respons Aplikasi',
                'category'=>'performance', 'method'=>'response_time',
                'unit'=>'ms', 'warning_max'=>1500, 'failure_max'=>3000,
                'description'=>'Ukur waktu respons endpoint health/aplikasi. Normal di bawah 1.500 ms; WARNING mulai 1.500 ms; FAIL mulai 3.000 ms. Nilai awal ini harus disesuaikan setelah tersedia baseline performa.',
            ],
            [
                'code'=>'WEB_SSL_EXPIRY', 'name'=>'Sisa Masa Berlaku Sertifikat SSL',
                'category'=>'security', 'method'=>'ssl_expiry',
                'unit'=>'hari', 'warning_min'=>30, 'failure_min'=>7,
                'description'=>'Sisa hari menuju akhir masa berlaku sertifikat HTTPS. Normal di atas 30 hari; WARNING jika 8–30 hari; FAIL jika 7 hari atau kurang. Gunakan hanya pada sistem HTTPS.',
            ],
            [
                'code'=>'DB_CONNECTION', 'name'=>'Konektivitas Database Aplikasi',
                'category'=>'availability', 'method'=>'database_connection',
                'expected_value'=>'connected',
                'description'=>'Pastikan aplikasi dapat terkoneksi ke database tanpa mengeksekusi query yang mengubah data. Membutuhkan integrasi health endpoint/agent internal, bukan akses database publik.',
            ],
            [
                'code'=>'SERVER_CPU_USAGE', 'name'=>'Penggunaan CPU Server',
                'category'=>'capacity', 'method'=>'cpu_usage',
                'unit'=>'%', 'warning_max'=>80, 'failure_max'=>90,
                'description'=>'Persentase penggunaan CPU server. WARNING jika mencapai 80%, FAIL jika mencapai 90%. Gunakan rata-rata observasi (misalnya 5 menit) agar lonjakan sesaat tidak dinilai sebagai gangguan.',
            ],
            [
                'code'=>'SERVER_RAM_USAGE', 'name'=>'Penggunaan RAM Server',
                'category'=>'capacity', 'method'=>'memory_usage',
                'unit'=>'%', 'warning_max'=>85, 'failure_max'=>95,
                'description'=>'Persentase penggunaan RAM efektif. WARNING jika mencapai 85%, FAIL jika mencapai 95%. Pengukuran Linux harus memperhitungkan available memory/cache.',
            ],
            [
                'code'=>'SERVER_DISK_USAGE', 'name'=>'Kapasitas Disk Server',
                'category'=>'capacity', 'method'=>'disk_usage',
                'unit'=>'%', 'warning_max'=>80, 'failure_max'=>90,
                'description'=>'Persentase partisi/volume disk yang terpakai. WARNING jika mencapai 80%, FAIL jika mencapai 90%. Nilai ditentukan per filesystem/volume target.',
            ],
            [
                'code'=>'SERVICE_RUNNING', 'name'=>'Status Layanan Utama Aplikasi',
                'category'=>'availability', 'method'=>'service_status',
                'expected_value'=>'running',
                'description'=>'Periksa apakah proses service aplikasi/web/database/queue yang terdaftar berstatus running. Nama service harus dipetakan pada sistem target di engine/agent PM.',
            ],
            [
                'code'=>'BACKUP_AGE_DAILY', 'name'=>'Usia Backup Terakhir (Jadwal Harian)',
                'category'=>'backup', 'method'=>'backup_age',
                'unit'=>'hari', 'warning_max'=>2, 'failure_max'=>3,
                'description'=>'Untuk sistem dengan target backup harian. WARNING jika backup terakhir sudah berusia 2 hari, FAIL jika 3 hari atau lebih. Tidak cocok untuk backup yang terjadwal mingguan.',
            ],
            [
                'code'=>'BACKUP_AGE_WEEKLY', 'name'=>'Usia Backup Terakhir (Jadwal Mingguan)',
                'category'=>'backup', 'method'=>'backup_age',
                'unit'=>'hari', 'warning_max'=>8, 'failure_max'=>15,
                'description'=>'Untuk sistem dengan target backup mingguan. WARNING jika backup terakhir sudah berusia 8 hari, FAIL jika 15 hari atau lebih. Sesuaikan dengan kebijakan backup yang disepakati.',
            ],
            [
                'code'=>'MANUAL_LOGIN_CHECK', 'name'=>'Uji Login dan Logout Pengguna',
                'category'=>'operational', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Petugas login menggunakan akun uji yang sah, memverifikasi dashboard dapat dibuka dan logout mengakhiri session. Jangan menyimpan password atau token di laporan PM.',
            ],
            [
                'code'=>'MANUAL_CORE_FEATURE', 'name'=>'Uji Fungsi Utama Sistem Informasi',
                'category'=>'operational', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Jalankan smoke test pada modul inti aplikasi menggunakan data uji yang aman, tanpa mengubah data produksi nyata. Catat menu yang diuji, hasil, dan bukti screenshot yang disanitasi.',
            ],
            [
                'code'=>'MANUAL_APP_ERROR_LOG', 'name'=>'Pemeriksaan Error Log Aplikasi',
                'category'=>'operational', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Tinjau error log aplikasi dan server untuk error kritis atau error berulang selama periode PM. PASS bila tidak ada temuan kritis; sertakan ringkasan, bukan data sensitif/log mentah.',
            ],
            [
                'code'=>'MANUAL_BACKUP_RESTORE', 'name'=>'Verifikasi Backup dan Uji Restore',
                'category'=>'backup', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Konfirmasi backup tersedia serta lakukan uji pemulihan terkontrol pada lingkungan nonproduksi sesuai prosedur. PASS jika bukti backup dan hasil uji restore valid.',
            ],
            [
                'code'=>'MANUAL_ACCOUNT_REVIEW', 'name'=>'Review Akun dan Hak Akses',
                'category'=>'security', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Tinjau akun aktif, akun tidak digunakan, role admin, serta kesesuaian akses. Catat temuan dan rekomendasi tanpa mengekspos daftar akun sensitif pada laporan publik.',
            ],
            [
                'code'=>'MANUAL_PATCH_REVIEW', 'name'=>'Pemeriksaan Versi dan Patch Keamanan',
                'category'=>'security', 'method'=>'manual_check',
                'expected_value'=>'PASS',
                'description'=>'Cek versi aplikasi, framework, OS dan dependensi terhadap patch keamanan yang disetujui. Tidak melakukan upgrade otomatis; temuan menjadi tindak lanjut corrective.',
            ],
        ];
    }

    public function run(): void
    {
        $created = 0;
        foreach (self::templates() as $template) {
            $parameter = PmParameterTemplate::firstOrCreate(
                ['code'=>$template['code']],
                ['is_active'=>true, ...$template]
            );
            if ($parameter->wasRecentlyCreated) $created++;
        }

        $this->command?->info("Template PM Sistem Informasi: {$created} baru dibuat, ".(count(self::templates())-$created)." sudah ada.");
    }
}
