<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('borrow_code')->unique(); // e.g. BRW-20240101-001
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');

            // Borrower info (public, no login required)
            $table->string('borrower_name');
            $table->string('borrower_email');
            $table->string('borrower_phone');
            $table->string('borrower_department')->nullable();
            $table->string('borrower_id_number')->nullable(); // NIP/NIM/NIK

            // Borrow details
            $table->date('borrow_date');
            $table->date('planned_return_date');
            $table->date('actual_return_date')->nullable();
            $table->text('purpose'); // keperluan peminjaman
            $table->string('destination')->nullable(); // lokasi penggunaan

            // Status: auto approved
            $table->enum('status', ['approved', 'borrowed', 'returned', 'overdue', 'cancelled'])->default('approved');

            // Return info
            $table->text('return_notes')->nullable();
            $table->enum('return_condition', ['good', 'fair', 'poor', 'damaged'])->nullable();

            // Admin notes
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
