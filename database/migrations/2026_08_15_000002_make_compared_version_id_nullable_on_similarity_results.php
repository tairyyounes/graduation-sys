<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * compared_version_id was a required FK to proposal_versions, which
     * silently assumed every AI match corresponds to a real proposal in
     * this system. In practice the AI engine's comparison corpus is an
     * external research dataset (see AI similarity investigation) — almost
     * no match has a real proposal_versions row to point to. The previous
     * code coped by falling back to self-referencing the current version,
     * which made the frontend display the student's OWN title/domain for
     * every match (comparedVersion->title always resolved successfully,
     * so it always beat the `?? raw['title']` fallback).
     *
     * Making this column nullable lets a match against an external/
     * synthetic corpus entry correctly store NULL here — no real compared
     * version exists, which is the truthful state — so the frontend falls
     * through to the AI engine's own raw title/domain instead.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('similarity_results', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedBigInteger('compared_version_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE similarity_results ALTER COLUMN compared_version_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('similarity_results')) {
            DB::statement(
                'UPDATE similarity_results SET compared_version_id = proposal_version_id WHERE compared_version_id IS NULL'
            );
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('similarity_results', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedBigInteger('compared_version_id')->nullable(false)->change();
                });
            } else {
                DB::statement('ALTER TABLE similarity_results ALTER COLUMN compared_version_id SET NOT NULL');
            }
        }
    }
};
