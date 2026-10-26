<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmRunCheck extends Model
{
    protected $fillable = [
        'pm_run_id','pm_system_id','pm_parameter_template_id','system_code','system_name',
        'parameter_code','parameter_name','method','status','value','unit','message',
        'thresholds','checked_at','reviewed_by','reviewed_at',
    ];

    protected function casts(): array
    {
        return ['thresholds'=>'array','checked_at'=>'datetime','reviewed_at'=>'datetime'];
    }

    public function run(): BelongsTo { return $this->belongsTo(PmRun::class,'pm_run_id'); }
    public function correctiveAction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PmCorrectiveAction::class,'pm_run_check_id');
    }
}