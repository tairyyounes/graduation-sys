<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('supervisor_name', 150)->nullable()->after('review_status');
            // Path on the private "local" disk — never publicly reachable,
            // served only through authorized controller endpoints.
            $table->string('supervisor_approval_path')->nullable()->after('supervisor_name');
            $table->string('supervisor_approval_original_name')->nullable()->after('supervisor_approval_path');
            $table->string('supervisor_approval_mime', 100)->nullable()->after('supervisor_approval_original_name');
            $table->unsignedInteger('supervisor_approval_size')->nullable()->after('supervisor_approval_mime');
            $table->timestamp('supervisor_approval_uploaded_at')->nullable()->after('supervisor_approval_size');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor_name',
                'supervisor_approval_path',
                'supervisor_approval_original_name',
                'supervisor_approval_mime',
                'supervisor_approval_size',
                'supervisor_approval_uploaded_at',
            ]);
        });
    }
};
