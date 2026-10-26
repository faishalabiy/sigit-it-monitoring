<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmCorrectiveEvent extends Model
{
    public $timestamps=false;
    protected $fillable=['pm_corrective_action_id','actor_id','event','changes'];
    protected function casts(): array {return ['changes'=>'array','created_at'=>'datetime'];}
    public function action(): BelongsTo { return $this->belongsTo(PmCorrectiveAction::class,'pm_corrective_action_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class,'actor_id'); }
}