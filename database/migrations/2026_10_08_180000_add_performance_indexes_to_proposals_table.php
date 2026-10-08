<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->index(['submission_status', 'created_at'], 'idx_proposals_submission_created');
            $table->index(['review_status', 'created_at'], 'idx_proposals_review_created');
            $table->index('created_at', 'idx_proposals_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex('idx_proposals_submission_created');
            $table->dropIndex('idx_proposals_review_created');
            $table->dropIndex('idx_proposals_created_at');
        });
    }
};
