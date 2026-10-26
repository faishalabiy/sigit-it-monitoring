<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_schedule_id')->nullable()->constrained('pm_schedules')->nullOnDelete();
            $table->string('schedule_code',50);
            $table->string('schedule_name',200);
            $table->string('trigger',16);
            $table->string('status',24)->default('running');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->unique(['pm_schedule_id','trigger','scheduled_for'],'pm_runs_schedule_slot_unique');
            $table->index(['status','started_at']);
        });

        Schema::create('pm_run_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pm_run_id')->constrained('pm_runs')->cascadeOnDelete();
            $table->foreignId('pm_system_id')->nullable()->constrained('pm_systems')->nullOnDelete();
            $table->foreignId('pm_parameter_template_id')->nullable()->constrained('pm_parameter_templates')->nullOnDelete();
            $table->string('system_code',50);
            $table->string('system_name',200);
            $table->string('parameter_code',50);
            $table->string('parameter_name',200);
            $table->string('method',40);
            $table->string('status',24);
            $table->string('value',255)->nullable();
            $table->string('unit',30)->nullable();
            $table->string('message',512)->nullable();
            $table->json('thresholds')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['pm_run_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_run_checks');
        Schema::dropIfExists('pm_runs');
    }
};