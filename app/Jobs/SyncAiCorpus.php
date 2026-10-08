<?php

namespace App\Jobs;

use App\Services\AiSimilarityService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends the system's archived/accepted proposals to the AI engine so new
 * submissions are compared against them. Dispatched whenever that set
 * changes (CSV import, single historical add, final acceptance).
 */
class SyncAiCorpus implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $uniqueFor = 60;

    public function handle(AiSimilarityService $service): void
    {
        // Never overwrite the real AI engine's corpus with test-database rows
        if (app()->runningUnitTests()) {
            return;
        }
        $service->syncCorpus();
    }
}
