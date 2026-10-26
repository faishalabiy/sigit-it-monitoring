<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up():void {
        Schema::create('pm_notifications', function(Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind',32);
            $table->string('level',16)->default('info');
            $table->string('subject',200);
            $table->text('message');
            $table->string('url',512)->nullable();
            $table->char('event_key',64);
            $table->dateTime('read_at')->nullable();
            $table->string('email_status',16)->default('disabled');
            $table->unsignedTinyInteger('email_attempts')->default(0);
            $table->dateTime('email_attempted_at')->nullable();
            $table->dateTime('email_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id','event_key']);
            $table->index(['user_id','read_at','created_at']);
            $table->index(['email_status','email_attempted_at']);
        });
    }
    public function down():void {Schema::dropIfExists('pm_notifications');}
};