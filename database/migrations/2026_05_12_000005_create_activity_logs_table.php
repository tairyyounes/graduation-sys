<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {

            $table->id('log_id');

            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('action');

            /*
            Examples:
            proposal
            proposal_version
            team_member
            similarity_result
            decision
            feedback
            */

            $table->string('target_type');

            /*
            Stores the related record id
            Example:
            proposal_id
            version_id
            feedback_id
            */

            $table->unsignedBigInteger('target_id')->nullable();

            $table->json('metadata')->nullable();

            /*
            Optional extra data:
            old_status
            new_status
            similarity_score
            version_number
            etc...
            */

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
