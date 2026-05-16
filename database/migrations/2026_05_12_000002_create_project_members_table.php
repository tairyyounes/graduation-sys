<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id('project_member_id');

            $table->foreignId('proposal_id')
                ->constrained('proposals', 'proposal_id')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students', 'student_id')
                ->cascadeOnDelete();

            $table->enum('member_role', [
                'owner',
                'member',
            ])->default('member');

            $table->enum('invitation_status', [
                'pending',
                'accepted',
                'rejected',
            ])->default('accepted');

            $table->timestamp('joined_at')->nullable();

            $table->timestamps();

            $table->unique(['proposal_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
