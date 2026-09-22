<?php

namespace App\Services;

use App\Models\ProposalVersion;
use Illuminate\Http\Client\ConnectionException;
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
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8000')), '/');

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
            $response = Http::timeout(60)
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
     * Call the FastAPI recommendations engine for alternative suggestions.
     */
    public function getRecommendations(
        ProposalVersion $version,
        string $departmentName,
        ?string $excludeId = null
    ): array {
        $baseUrl = rtrim(config('services.dense_api.url', env('DENSE_API_URL', 'http://127.0.0.1:8000')), '/');

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
            $response = Http::timeout(30)
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
