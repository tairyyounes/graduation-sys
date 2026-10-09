<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalVersion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSimilarityService
{
    /**
     * Call the FastAPI similarity engine for a given proposal version.
     *
     * @param  ProposalVersion  $version         The version whose content is sent to the API.
     * @param  string           $departmentName  Used as the "department" field in the API payload.
     * @param  string|null      $excludeId       Optional project ID to exclude from results.
     * @param  int              $topK            Number of top matches to return (default 5).
     * @return array            The decoded JSON response from the AI API.
     *
     * @throws \RuntimeException  If the API call fails or returns a non-2xx status.
     */
    public function checkSimilarity(
        ProposalVersion $version,
        string $departmentName,
        ?string $excludeId = null,
        int $topK = 5
    ): array {
        @set_time_limit(120);
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8002')), '/');

        $this->ensureServerRunning($baseUrl);

        $payload = [
            'title'            => $version->title ?? '',
            'problem'          => $version->problem ?? '',
            'solution'         => $version->solution ?? '',
            'functions'        => $version->functions ?? '',
            'objectives'       => $version->objectives ?? '',
            'tags'             => $version->tags ?? '',
            'technologies_used' => $version->technologies_used ?? '',
            'department'       => $departmentName,
            'top_k'            => $topK,
        ];

        if ($excludeId !== null) {
            $payload['exclude_project_id'] = $excludeId;
        }

        try {
            $response = Http::timeout(240)
                ->post("{$baseUrl}/search_proposals", $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException(
                "AI API is unreachable at {$baseUrl}: " . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            $status = $response->status();
            $body   = $response->body();
            Log::error("AiSimilarityService: HTTP {$status} from AI API", ['body' => $body]);
            throw new \RuntimeException(
                "AI API returned HTTP {$status}: {$body}"
            );
        }

        return $response->json();
    }

    /**
     * Proposals the AI engine compares new submissions against: historical
     * imports (archived) and accepted proposals stored in this system.
     */
    public static function corpusQuery()
    {
        return Proposal::query()
            ->with(['latestVersion', 'department'])
            ->where('review_status', '!=', 'rejected')
            ->where(function ($q) {
                $q->where('submission_status', 'archived')
                  ->orWhere('review_status', 'accepted');
            });
    }

    /**
     * Push the system's proposals to the AI engine so it compares against
     * them (and returns their real proposal_id). The engine encodes in the
     * background and only re-encodes new or changed proposals.
     */
    public function syncCorpus(): array
    {
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8000')), '/');

        $proposals = [];
        self::corpusQuery()->chunkById(500, function ($chunk) use (&$proposals) {
            foreach ($chunk as $p) {
                $v = $p->latestVersion;
                if (!$v) {
                    continue;
                }
                $proposals[] = [
                    'project_id'        => $p->proposal_id,
                    'title'             => $v->title ?? '',
                    'problem'           => $v->problem ?? '',
                    'solution'          => $v->solution ?? '',
                    'objectives'        => $v->objectives ?? '',
                    'functions'         => $v->functions ?? '',
                    'tags'              => $v->tags ?? '',
                    'technologies_used' => $v->technologies_used ?? '',
                    'domain'            => optional($p->department)->department_name ?? '',
                ];
            }
        }, 'proposal_id');

        $response = Http::timeout(60)->post("{$baseUrl}/corpus/sync", ['proposals' => $proposals]);
        if ($response->failed()) {
            throw new \RuntimeException("AI corpus sync failed: HTTP {$response->status()} {$response->body()}");
        }

        Log::info('AiSimilarityService: corpus sync started', ['proposals' => count($proposals)]);
        return $response->json();
    }

    /**
     * Returns the AI engine's corpus status, or null if unreachable.
     */
    public function corpusStatus(): ?array
    {
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8000')), '/');
        try {
            $response = Http::timeout(5)->get("{$baseUrl}/corpus/status");
            return $response->successful() ? $response->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * If the local AI server is down, launch it and wait until it is ready,
     * so a submission never fails just because nobody started the server.
     * No-op when AI_SERVER_DIR is not configured or the server is healthy.
     */
    private function ensureServerRunning(string $baseUrl): void
    {
        $dir = config('services.dense_api.server_dir');
        if (!$dir || $this->isHealthy($baseUrl)) {
            return;
        }

        // One launcher at a time; concurrent requests just wait for health.
        $lock = Cache::lock('ai-server-start', 180);
        if ($lock->get()) {
            $port   = parse_url($baseUrl, PHP_URL_PORT) ?: 8000;
            $python = config('services.dense_api.python', 'py');
            $log    = storage_path('logs/ai-server.log');
            Log::info("AiSimilarityService: AI server down, starting it from {$dir}");

            // Start-Process launches a fully detached process (no inherited
            // handles), so it outlives this request and never blocks it.
            // PowerShell itself returns immediately.
            $ps = sprintf(
                "Start-Process -FilePath '%s' -ArgumentList '-m','uvicorn','server_dense:app','--port','%d' "
                . "-WorkingDirectory '%s' -WindowStyle Hidden -RedirectStandardOutput '%s' -RedirectStandardError '%s'",
                $python, $port, $dir, $log, $log . '.err'
            );
            exec('powershell -NoProfile -NonInteractive -Command "' . $ps . '"');
        }

        // Startup (models + warm-up) takes ~15-20s.
        $deadline = time() + 150;
        while (time() < $deadline) {
            if ($this->isHealthy($baseUrl)) {
                return;
            }
            sleep(2);
        }
        Log::error('AiSimilarityService: AI server did not become ready in time.');
    }

    private function isHealthy(string $baseUrl): bool
    {
        try {
            return Http::timeout(3)->get("{$baseUrl}/health")->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Call the FastAPI recommendations engine for alternative suggestions.
     */
    public function getRecommendations(
        ProposalVersion $version,
        string $departmentName,
        ?string $excludeId = null
    ): array {
        @set_time_limit(120);
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8002')), '/');

        $payload = [
            'title'            => $version->title ?? '',
            'problem'          => $version->problem ?? '',
            'solution'         => $version->solution ?? '',
            'functions'        => $version->functions ?? '',
            'objectives'       => $version->objectives ?? '',
            'tags'             => $version->tags ?? '',
            'technologies_used' => $version->technologies_used ?? '',
            'department'       => $departmentName,
            'top_k'            => 3,
        ];

        if ($excludeId !== null) {
            $payload['exclude_project_id'] = $excludeId;
        }

        try {
            $response = Http::timeout(120)
                ->post("{$baseUrl}/recommend", $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("AiSimilarityService recommendations failed: " . $e->getMessage());
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return $response->json()['results'] ?? [];
    }
}
