<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id('decision_id');

            $table->foreignId('proposal_id')
                ->constrained('proposals', 'proposal_id')
                ->cascadeOnDelete();

            $table->foreignId('version_id')
                ->constrained('proposal_versions', 'version_id')
                ->cascadeOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->enum('decision_type', [
                'accepted',
                'rejected',
                'revision_requested',
            ]);

            $table->text('decision_note')->nullable();

            $table->timestamp('decision_date')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
