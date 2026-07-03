<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Borrowing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'borrow_code', 'asset_id', 'tipe_peminjaman',
        'borrower_name', 'borrower_email', 'borrower_phone',
        'borrower_department', 'borrower_id_number',
        'borrow_date', 'planned_return_date', 'actual_return_date',
        'purpose', 'destination', 'status',
        'return_notes', 'return_condition',
        'admin_notes', 'processed_by',
    ];

    protected $casts = [
        'borrow_date'          => 'date',
        'planned_return_date'  => 'date',
        'actual_return_date'   => 'date',
    ];

    // Generate unique borrow code
    public static function generateCode(): string
    {
        $date  = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'BRW-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Status info
    public static function statusLabels(): array
    {
        return [
            'approved'  => ['label' => 'Disetujui',   'color' => 'blue'],
            'borrowed'  => ['label' => 'Dipinjam',    'color' => 'yellow'],
            'returned'  => ['label' => 'Dikembalikan','color' => 'green'],
            'overdue'   => ['label' => 'Terlambat',   'color' => 'red'],
            'cancelled' => ['label' => 'Dibatalkan',  'color' => 'gray'],
        ];
    }

    public function getStatusInfoAttribute(): array
    {
        return self::statusLabels()[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    // Is overdue?
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['approved', 'borrowed'])
            && $this->planned_return_date < today();
    }

    // Days remaining or overdue
    public function getDaysInfoAttribute(): array
    {
        if ($this->status === 'returned') {
            return ['type' => 'returned', 'days' => 0, 'label' => 'Sudah dikembalikan'];
        }
        $diff = today()->diffInDays($this->planned_return_date, false);
        if ($diff < 0) {
            return ['type' => 'overdue', 'days' => abs($diff), 'label' => abs($diff) . ' hari terlambat'];
        }
        if ($diff === 0) {
            return ['type' => 'today',   'days' => 0,    'label' => 'Harus kembali hari ini'];
        }
        return ['type' => 'ok', 'days' => $diff, 'label' => $diff . ' hari lagi'];
    }

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Tipe peminjaman info
    public static function tipePeminjamanLabels(): array
    {
        return [
            'pribadi' => 'Pribadi',
            'kantor' => 'Kantor',
        ];
    }

    public function getTipePeminjamanLabelAttribute(): string
    {
        return self::tipePeminjamanLabels()[$this->tipe_peminjaman] ?? '-';
    }
}
