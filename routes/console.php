<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:check', function () {
    $url = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8000')), '/');
    $this->info("Checking AI FastAPI server connection at: {$url}");

    $samplePayload = [
        'title'             => 'Test Smart System',
        'problem'           => 'Manual operations lead to inefficiencies and errors in processing.',
        'solution'          => 'Automated web-based system using AI algorithms.',
        'functions'         => 'Authentication, Queue Management, Analytics',
        'objectives'        => 'Improve performance and save time',
        'tags'              => 'automation, AI, web',
        'technologies_used' => 'Python, PHP, Vue',
        'department'        => 'General',
        'top_k'             => 3,
        // A connectivity test must not write this sample into the AI index.
        'add_to_index'      => false,
    ];

    try {
        $this->line("1. Testing endpoint [POST] {$url}/search_proposals ...");
        $response = \Illuminate\Support\Facades\Http::timeout(5)->post("{$url}/search_proposals", $samplePayload);

        if ($response->successful()) {
            $this->info("   [SUCCESS] Status {$response->status()}");
            $data = $response->json();
            $matchCount = count($data['results'] ?? []);
            $this->line("   Received {$matchCount} match result(s).");
        } else {
            $this->error("   [FAILED] Server responded with HTTP {$response->status()}: " . $response->body());
        }
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $this->error("   [CONNECTION ERROR] Could not connect to {$url}. Is the FastAPI server running?");
        $this->comment("   Start it with: uvicorn <file_name>:app --host 127.0.0.1 --port 8000 --reload");
        return;
    } catch (\Throwable $e) {
        $this->error("   [ERROR] " . $e->getMessage());
        return;
    }

    $this->info("AI connection check completed.");
})->purpose('Test connection to the external FastAPI AI server');


Artisan::command('ai:sync-corpus', function (\App\Services\AiSimilarityService $service) {
    $count = \App\Services\AiSimilarityService::corpusQuery()->count();
    $this->info("Sending {$count} archived/accepted proposals to the AI engine...");
    $result = $service->syncCorpus();
    $this->info('Started: ' . json_encode($result));
    $this->comment('Encoding runs in the background on the AI server; check progress with GET /corpus/status.');
})->purpose('Sync the system proposals into the AI comparison corpus');
