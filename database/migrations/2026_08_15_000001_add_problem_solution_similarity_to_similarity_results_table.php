<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the two breakdown dimensions the AI engine's per-aspect
     * explainer (similarity_breakdown.py) computes but the original schema
     * never had a column for: problem_similarity and solution_similarity.
     *
     * The pre-existing "semantic_similarity" column is NOT reused for
     * either of these — it was defined against an older, now-replaced
     * architecture (a single whole-proposal embedding, not a per-field
     * breakdown) and repurposing it would silently redefine what it has
     * historically meant. It is kept, untouched, for backward
     * compatibility with any old raw responses already stored.
     */
    public function up(): void
    {
        Schema::table('similarity_results', function (Blueprint $table) {
            $table->decimal('problem_similarity', 5, 4)->nullable()->after('similarity_score');
            $table->decimal('solution_similarity', 5, 4)->nullable()->after('problem_similarity');
        });
    }

    public function down(): void
    {
        Schema::table('similarity_results', function (Blueprint $table) {
            $table->dropColumn(['problem_similarity', 'solution_similarity']);
        });
    }
};
