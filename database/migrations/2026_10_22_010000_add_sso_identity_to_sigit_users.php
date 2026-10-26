<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasSsoId = Schema::hasColumn('users', 'sso_id');
        $hasEmployeeId = Schema::hasColumn('users', 'employee_id');
        if ($hasSsoId && $hasEmployeeId) return;

        Schema::table('users', function (Blueprint $table) use ($hasSsoId, $hasEmployeeId) {
            if (! $hasSsoId) $table->string('sso_id')->nullable()->unique();
            if (! $hasEmployeeId) $table->string('employee_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        $columns = [];
        if (Schema::hasColumn('users', 'sso_id')) $columns[] = 'sso_id';
        if (Schema::hasColumn('users', 'employee_id')) $columns[] = 'employee_id';
        if ($columns !== []) Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
    }
};
