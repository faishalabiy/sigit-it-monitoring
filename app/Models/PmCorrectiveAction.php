<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmCorrectiveAction extends Model
{
    protected $fillable = [
        'pm_run_id','pm_run_check_id','status','priority','finding',
        'recommendation','assigned_to','due_on','resolution_notes','resolved_by',
        'resolved_at','created_by',
    ];
    protected function casts(): array {
        return ['due_on'=>'date','resolved_at'=>'datetime'];
    }
    public function run(): BelongsTo { return $this->belongsTo(PmRun::class,'pm_run_id'); }
    public function check(): BelongsTo { return $this->belongsTo(PmRunCheck::class,'pm_run_check_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class,'resolved_by'); }
    public function events(): HasMany { return $this->hasMany(PmCorrectiveEvent::class,'pm_corrective_action_id'); }
}