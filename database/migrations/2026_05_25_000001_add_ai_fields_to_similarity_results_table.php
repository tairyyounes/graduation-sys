<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add AI similarity breakdown fields to the similarity_results table.
     *
     * New columns:
     *  - ai_status              : tracks whether the AI call succeeded, failed, or is pending
     *  - semantic_similarity    : deep text-level similarity from sentence-transformer embeddings
     *  - functions_similarity   : Jaccard overlap over system functions
     *  - objectives_similarity  : Jaccard overlap over project objectives
     *  - tags_similarity        : Jaccard overlap over keyword tags
     *  - technologies_similarity: Jaccard overlap over technologies used
     *  - final_score            : weighted reranked score (primary display score)
     *  - verdict                : human-readable classification label from the AI
     *  - explanation            : AI-generated reason why the projects matched
     *  - ai_raw_response        : full JSON response stored for debugging/auditing
     */
    public function up(): void
    {
        Schema::table('similarity_results', function (Blueprint $table) {
            // Track whether the AI call was made and what happened
            $table->string('ai_status')->default('pending')->after('id')
                  ->comment('pending | success | failed');

            // Per-dimension breakdown scores (0.00 – 1.00)
            $table->decimal('semantic_similarity', 5, 4)->nullable()->after('similarity_score');
            $table->decimal('functions_similarity', 5, 4)->nullable()->after('semantic_similarity');
            $table->decimal('objectives_similarity', 5, 4)->nullable()->after('functions_similarity');
            $table->decimal('tags_similarity', 5, 4)->nullable()->after('objectives_similarity');
            $table->decimal('technologies_similarity', 5, 4)->nullable()->after('tags_similarity');

            // Final weighted score from the AI reranker (same range as similarity_score but from AI)
            $table->decimal('final_score', 5, 4)->nullable()->after('technologies_similarity');

            // AI verdict and explanation
            $table->string('verdict')->nullable()->after('final_score')
                  ->comment('e.g. Very High Similarity, Moderate Similarity');
            $table->text('explanation')->nullable()->after('verdict');

            // Full raw JSON response for auditing/debugging
            $table->json('ai_raw_response')->nullable()->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('similarity_results', function (Blueprint $table) {
            $table->dropColumn([
                'ai_status',
                'semantic_similarity',
                'functions_similarity',
                'objectives_similarity',
                'tags_similarity',
                'technologies_similarity',
                'final_score',
                'verdict',
                'explanation',
                'ai_raw_response',
            ]);
        });
    }
};
