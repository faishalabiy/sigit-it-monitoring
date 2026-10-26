<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('pm_runs', function (Blueprint $table) {
            $table->string('review_status',24)->default('draft')->index();
            $table->foreignId('review_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('review_requested_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();
            $table->longText('report_snapshot')->nullable();
            $table->char('report_sha256',64)->nullable();
        });
        Schema::create('pm_report_approval_events',function(Blueprint $table){
            $table->id();
            $table->foreignId('pm_run_id')->constrained('pm_runs')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event',32);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pm_run_id','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('pm_report_approval_events');
        Schema::table('pm_runs',function(Blueprint $table){
            $table->dropConstrainedForeignId('review_requested_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn(['review_status','review_requested_at','review_notes',
                'approved_at','approval_notes','finalized_at','report_snapshot','report_sha256']);
        });
    }
};