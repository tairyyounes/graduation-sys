<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First try to drop constraint if it exists (for PostgreSQL)
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT users_role_check");
        } catch (\Exception $e) {}

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->change();
        });
    }

    public function down(): void
    {
        // Reverse is not strictly necessary or easily reversible to exact check constraint
        // So we leave it as string.
    }
};
