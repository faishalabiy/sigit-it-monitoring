<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pm_systems', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->string('type', 32);
            $table->string('environment', 32)->default('production');
            $table->string('criticality', 16)->default('medium');
            $table->string('status', 16)->default('active');
            $table->string('target_url', 512)->nullable();
            $table->string('host', 255)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['status','type']);
        });

        Schema::create('pm_parameter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->string('category', 32);
            $table->string('method', 40);
            $table->string('unit', 30)->nullable();
            $table->string('expected_value', 255)->nullable();
            $table->decimal('warning_min', 15, 3)->nullable();
            $table->decimal('warning_max', 15, 3)->nullable();
            $table->decimal('failure_min', 15, 3)->nullable();
            $table->decimal('failure_max', 15, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('pm_system_parameter', function (Blueprint $table) {
            $table->foreignId('pm_system_id')->constrained('pm_systems')->cascadeOnDelete();
            $table->foreignId('pm_parameter_template_id')->constrained('pm_parameter_templates')->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->primary(['pm_system_id','pm_parameter_template_id'], 'pm_sys_parameter_primary');
        });

        Schema::create('pm_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->string('frequency', 16);
            $table->time('run_time');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->date('starts_on');
            $table->string('status', 16)->default('draft');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['status','frequency']);
        });

        Schema::create('pm_schedule_system', function (Blueprint $table) {
            $table->foreignId('pm_schedule_id')->constrained('pm_schedules')->cascadeOnDelete();
            $table->foreignId('pm_system_id')->constrained('pm_systems')->restrictOnDelete();
            $table->timestamps();
            $table->primary(['pm_schedule_id','pm_system_id'], 'pm_schedule_system_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_schedule_system');
        Schema::dropIfExists('pm_schedules');
        Schema::dropIfExists('pm_system_parameter');
        Schema::dropIfExists('pm_parameter_templates');
        Schema::dropIfExists('pm_systems');
    }
};