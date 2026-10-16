<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proposal_id')
                ->constrained('proposals', 'proposal_id')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students', 'student_id')
                ->cascadeOnDelete();

            $table->text('reason');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('decided_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['proposal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_leave_requests');
    }
};
