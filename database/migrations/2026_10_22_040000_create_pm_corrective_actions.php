<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pm_corrective_actions', function(Blueprint $table) {
            $table->id();
            $table->foreignId('pm_run_id')->constrained('pm_runs')->cascadeOnDelete();
            $table->foreignId('pm_run_check_id')->unique()->constrained('pm_run_checks')->cascadeOnDelete();
            $table->string('status',20)->default('open');
            $table->string('priority',12)->default('medium');
            $table->string('finding',512);
            $table->text('recommendation')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status','due_on']);
        });
        Schema::create('pm_corrective_events',function(Blueprint $table) {
            $table->id();
            $table->foreignId('pm_corrective_action_id')->constrained('pm_corrective_actions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event',32);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pm_corrective_events');
        Schema::dropIfExists('pm_corrective_actions');
    }
};