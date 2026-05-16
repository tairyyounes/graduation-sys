<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id('proposal_id');

            $table->foreignId('department_id')
                ->constrained('departments', 'department_id')
                ->cascadeOnDelete();

            $table->enum('submission_status', [
                'draft',
                'submitted',
                'archived',
            ])->default('draft');

            $table->enum('review_status', [
                'pending',
                'under_review',
                'revision_requested',
                'accepted',
                'rejected',
            ])->default('pending');

            $table->boolean('is_locked')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
