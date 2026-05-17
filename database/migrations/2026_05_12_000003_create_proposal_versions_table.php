<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_versions', function (Blueprint $table) {
            $table->id('version_id');

            $table->foreignId('proposal_id')
                ->constrained('proposals', 'proposal_id')
                ->cascadeOnDelete();

            $table->integer('version_number');

            $table->string('title');

            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            $table->text('functions')->nullable();
            $table->text('objectives')->nullable();

            $table->text('tags')->nullable();
            $table->text('technologies_used')->nullable();

            $table->timestamps();

            $table->unique(['proposal_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_versions');
    }
};
