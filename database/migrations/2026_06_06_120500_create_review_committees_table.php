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
        Schema::create('review_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments', 'department_id')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('committee_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_committee_id')->constrained('review_committees')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['review_committee_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_user');
        Schema::dropIfExists('review_committees');
    }
};
