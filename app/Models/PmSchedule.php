<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PmSchedule extends Model
{
    protected $fillable = [
        'code','name','frequency','run_time','day_of_week','day_of_month',
        'timezone','starts_on','status','assigned_to','description',
    ];

    protected function casts(): array
    {
        return ['starts_on'=>'date'];
    }

    public const FREQUENCIES = ['daily'=>'Harian','weekly'=>'Mingguan','monthly'=>'Bulanan'];

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(PmSystem::class,'pm_schedule_system')->withTimestamps();
    }

    public function runs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PmRun::class,'pm_schedule_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}