<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmNotification extends Model {
    protected $fillable=[
        'user_id','kind','level','subject','message','url','event_key',
        'read_at','email_status','email_attempts','email_attempted_at','email_sent_at',
    ];
    protected function casts():array {
        return ['read_at'=>'datetime','email_attempted_at'=>'datetime','email_sent_at'=>'datetime'];
    }
    public function user():BelongsTo {return $this->belongsTo(User::class);}
}