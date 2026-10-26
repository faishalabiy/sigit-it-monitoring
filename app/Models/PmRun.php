<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmRun extends Model
{
    protected $fillable = ['pm_schedule_id','schedule_code','schedule_name','trigger',
        'status','initiated_by','scheduled_for','started_at','completed_at','summary',
        'review_status','review_requested_by','review_requested_at','review_notes',
        'approved_by','approved_at','approval_notes','finalized_by','finalized_at',
        'report_snapshot','report_sha256'];

    protected function casts(): array
    {
        return ['scheduled_for'=>'datetime','started_at'=>'datetime',
            'completed_at'=>'datetime','summary'=>'array',
            'review_requested_at'=>'datetime','approved_at'=>'datetime','finalized_at'=>'datetime'];
    }

    /**
     * Label for technical results, distinct from document approval.
     * Historical measurements are never changed by approval/closure.
     */
    public function getResultLabelAttribute(): string
    {
        return match ($this->status) {
            'completed'=>'Semua Normal',
            'needs_review'=>'Ada Temuan',
            'failed'=>'Pemeriksaan Gagal',
            default=>'Sedang Berjalan',
        };
    }

    public function schedule(): BelongsTo { return $this->belongsTo(PmSchedule::class,'pm_schedule_id'); }
    public function initiator(): BelongsTo { return $this->belongsTo(User::class,'initiated_by'); }
    public function checks(): HasMany { return $this->hasMany(PmRunCheck::class,'pm_run_id'); }
    public function correctives(): HasMany {return $this->hasMany(PmCorrectiveAction::class,'pm_run_id');}
    public function approvalEvents():HasMany {return $this->hasMany(PmReportApprovalEvent::class,'pm_run_id');}
    public function reviewRequester():BelongsTo {return $this->belongsTo(User::class,'review_requested_by');}
    public function approver():BelongsTo {return $this->belongsTo(User::class,'approved_by');}
    public function finalizer():BelongsTo {return $this->belongsTo(User::class,'finalized_by');}

}