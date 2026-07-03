<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code', 'name', 'category', 'brand', 'model',
        'serial_number', 'description', 'condition', 'status',
        'asset_pic',
        'location', 'purchase_date', 'purchase_price', 'qr_code', 'photo',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    // Category labels
    public static function categoryLabels(): array
    {
        return [
            'laptop'    => 'Laptop / Notebook',
            'monitor'   => 'Monitor',
            'pc'        => 'PC / Desktop',
            'printer'   => 'Printer / Scanner',
            'projector' => 'Proyektor',
            'camera'    => 'Kamera / Peralatan Foto',
            'network'   => 'Perangkat Jaringan',
            'other'     => 'Lainnya',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }

    // Status labels & colors
    public static function statusLabels(): array
    {
        return [
            'available'   => ['label' => 'Tersedia',     'color' => 'green'],
            'not_available' => ['label' => 'Tidak Tersedia', 'color' => 'red'],
            'borrowed'    => ['label' => 'Dipinjam',     'color' => 'yellow'],
            'maintenance' => ['label' => 'Maintenance',  'color' => 'orange'],
            'retired'     => ['label' => 'Tidak Aktif',  'color' => 'grey'],
            'fixed_asset'  => ['label' => 'Aset Tetap',   'color' => 'blue'],

        ];
    }

    public function getStatusInfoAttribute(): array
    {
        return self::statusLabels()[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    // Condition labels
    public static function conditionLabels(): array
    {
        return [
            'good' => 'Baik',
            'fair' => 'Cukup',
            'poor' => 'Rusak Ringan',
            'missing' => 'Hilang',
        ];
    }

    public function getConditionLabelAttribute(): string
    {
        return self::conditionLabels()[$this->condition] ?? $this->condition;
    }

    // Category icon
    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'laptop'    => '💻',
            'projector' => '📽️',
            'camera'    => '📷',
            'network'   => '🔌',
            default     => '📦',
        };
    }

    // Generate unique asset code
    public static function generateCode(string $category): string
    {
        $prefix = match($category) {
            'laptop'    => 'LPT',
            'projector' => 'PRJ',
            'camera'    => 'CAM',
            'network'   => 'NET',
            default     => 'AST',
        };
        $last = self::where('category', $category)->withTrashed()->count() + 1;
        return $prefix . '-' . str_pad($last, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowing()
    {
        return $this->hasOne(Borrowing::class)->whereIn('status', ['approved', 'borrowed']);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
