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

    try {
        $this->line("2. Testing endpoint [POST] {$url}/recommend ...");
        $recResponse = \Illuminate\Support\Facades\Http::timeout(5)->post("{$url}/recommend", $samplePayload);

        if ($recResponse->successful()) {
            $this->info("   [SUCCESS] Status {$recResponse->status()}");
        } else {
            $this->warn("   [WARNING] /recommend responded with HTTP {$recResponse->status()}");
        }
    } catch (\Throwable $e) {
        $this->warn("   [WARNING] /recommend check: " . $e->getMessage());
    }

    $this->info("AI connection check completed.");
})->purpose('Test connection to the external FastAPI AI server');
