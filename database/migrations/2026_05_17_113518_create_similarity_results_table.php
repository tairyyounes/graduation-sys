<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('similarity_results', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('proposal_version_id')
                  ->constrained('proposal_versions', 'version_id')
                  ->cascadeOnDelete();
                  
            $table->foreignId('compared_version_id')
                  ->constrained('proposal_versions', 'version_id')
                  ->cascadeOnDelete();
                  
            $table->decimal('similarity_score', 5, 2); // percentage
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('similarity_results');
    }
};
