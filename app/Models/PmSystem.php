<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PmSystem extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'environment', 'criticality',
        'status', 'target_url', 'host', 'owner_id', 'description',
    ];

    public const TYPES = [
        'application' => 'Aplikasi Web', 'server' => 'Server',
        'database' => 'Database', 'network' => 'Jaringan',
        'storage' => 'Storage / NAS', 'service' => 'Layanan',
        'other' => 'Lainnya',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parameters(): BelongsToMany
    {
        return $this->belongsToMany(PmParameterTemplate::class, 'pm_system_parameter')
            ->withPivot('is_required')->withTimestamps();
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(PmSchedule::class, 'pm_schedule_system')->withTimestamps();
    }
}