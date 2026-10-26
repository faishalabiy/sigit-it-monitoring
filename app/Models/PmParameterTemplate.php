<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PmParameterTemplate extends Model
{
    protected $fillable = [
        'code', 'name', 'category', 'method', 'unit', 'expected_value',
        'warning_min', 'warning_max', 'failure_min', 'failure_max',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const CATEGORIES = [
        'availability'=>'Ketersediaan', 'performance'=>'Performa',
        'capacity'=>'Kapasitas', 'security'=>'Keamanan',
        'backup'=>'Backup', 'operational'=>'Operasional',
    ];

    public const METHODS = [
        'http_status'=>'HTTP Status', 'response_time'=>'Response Time',
        'ssl_expiry'=>'Sisa Masa Berlaku SSL',
        'cpu_usage'=>'Penggunaan CPU', 'memory_usage'=>'Penggunaan RAM',
        'disk_usage'=>'Penggunaan Disk',
        'database_connection'=>'Koneksi Database',
        'service_status'=>'Status Service',
        'backup_age'=>'Umur Backup', 'backup_present'=>'Bukti Backup NAS Proxmox', 'systemd_unit'=>'Status Unit Server',
        'scheduler_heartbeat'=>'Heartbeat Scheduler', 'queue_worker'=>'Queue Worker',
        'manual_check'=>'Checklist Manual',
    ];

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(PmSystem::class, 'pm_system_parameter')
            ->withPivot('is_required')->withTimestamps();
    }
}