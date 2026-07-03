<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique(); // e.g. LPT-001, PRJ-001
            $table->string('name');
            $table->enum('category', ['laptop', 'projector', 'camera', 'network', 'other']);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->text('description')->nullable();
            $table->enum('condition', ['good', 'fair', 'poor'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'maintenance', 'retired'])->default('available');
            $table->string('location')->nullable(); // storage location
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('qr_code')->nullable(); // path to QR code image
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
