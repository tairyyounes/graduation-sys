<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\SimilarityResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposalRepositoryController extends Controller
{
    /**
     * Get repository proposals for the authenticated user's department.
     * Supports search (title, tags, technologies) and year filter.
     */
    public function index(Request $request): JsonResponse
    {
        // Resolve the authenticated user's department
        $user = $request->user();
        $departmentId = $user->department_id;

        // Determine the start of the current semester to exclude it
        $currentMonth = now()->month;
        $currentYear = now()->year;

        if ($currentMonth <= 6) {
            $semesterStart = now()->setDate($currentYear, 1, 1)->startOfDay();
        } else {
            $semesterStart = now()->setDate($currentYear, 7, 1)->startOfDay();
        }

        $query = Proposal::whereIn('submission_status', ['submitted', 'archived'])
            ->where('created_at', '<', $semesterStart)
            ->with(['latestVersion', 'department', 'students']);

        // Always scope to the user's own department
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        // Search filter: title, tags, technologies
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('latestVersion', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%")
                  ->orWhere('technologies_used', 'like', "%{$search}%")
                  ->orWhere('problem', 'like', "%{$search}%");
            });
        }

        // Year filter
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->query('year'));
        }

        $proposals = $query->latest()->get();

        // Available years for this department only (exclude current semester)
        $years = Proposal::whereIn('submission_status', ['submitted', 'archived'])
            ->where('created_at', '<', $semesterStart)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->pluck('created_at')
            ->map(fn($date) => $date ? $date->format('Y') : null)
            ->filter()
            ->unique()
            ->values()
            ->sortDesc()
            ->toArray();

        return response()->json([
            'proposals' => $proposals->map(fn($p) => $this->transformProposal($p)),
            'years'     => $years,
        ]);
    }

    /**
     * Get full details of a specific repository proposal.
     */
    public function show(Request $request, Proposal $proposal): JsonResponse
    {
        if ($proposal->submission_status === 'draft') {
            return response()->json(['message' => 'Unauthorized access to drafts.'], 403);
        }

        $proposal->load(['latestVersion', 'department', 'students']);
        return response()->json([
            'proposal' => $this->transformProposal($proposal),
        ]);
    }

    /**
     * Compare a current proposal side-by-side with a repository proposal.
     */
    public function compare(Request $request, Proposal $proposal): JsonResponse
    {
        $currentProposal = null;

        if ($request->has('current_id')) {
            $currentProposal = Proposal::find($request->query('current_id'));
        } else {
            $student = $request->user()->student;
            if ($student) {
                $currentProposal = $student->proposals()
                    ->where('submission_status', 'submitted')
                    ->first() ?? $student->proposals()
                    ->where('submission_status', 'draft')
                    ->first();
            }
        }

        if (!$currentProposal) {
            return response()->json(['message' => 'No active proposal found to compare. Please create a draft first.'], 422);
        }

        $currentProposal->load(['latestVersion', 'students']);
        $proposal->load(['latestVersion', 'students']);

        $currentVersion  = $currentProposal->latestVersion;
        $comparedVersion = $proposal->latestVersion;

        $similarityResult = null;
        if ($currentVersion && $comparedVersion) {
            $similarityResult = SimilarityResult::where('proposal_version_id', $currentVersion->version_id)
                ->where('compared_version_id', $comparedVersion->version_id)
                ->first();
        }

        $authorName = $currentProposal->students->first()->full_name ?? 'Unknown';

        return response()->json([
            'current' => [
                'id'         => $currentProposal->proposal_id,
                'title'      => $currentVersion->title ?? 'No Title',
                'author'     => $authorName,
                'problem'    => $currentVersion->problem ?? '',
                'solution'   => $currentVersion->solution ?? '',
                'functions'  => $currentVersion->functions ?? '',
                'objectives' => $currentVersion->objectives ?? '',
                'tags'       => $currentVersion->tags ?? '',
                'tech'       => $currentVersion->technologies_used ?? '',
            ],
            'compared'   => $this->transformProposal($proposal),
            'similarity' => $similarityResult ? [
                'score'      => $similarityResult->final_score !== null ? round($similarityResult->final_score * 100, 1) . '%' : ($similarityResult->similarity_score ?? 0) . '%',
                'semantic'   => $similarityResult->semantic_similarity   !== null ? round($similarityResult->semantic_similarity   * 100, 1) . '%' : null,
                'functions'  => $similarityResult->functions_similarity  !== null ? round($similarityResult->functions_similarity  * 100, 1) . '%' : null,
                'objectives' => $similarityResult->objectives_similarity !== null ? round($similarityResult->objectives_similarity * 100, 1) . '%' : null,
                'tags'       => $similarityResult->tags_similarity       !== null ? round($similarityResult->tags_similarity       * 100, 1) . '%' : null,
                'tech'       => $similarityResult->technologies_similarity !== null ? round($similarityResult->technologies_similarity * 100, 1) . '%' : null,
                'verdict'    => $similarityResult->verdict,
                'explanation'=> $similarityResult->explanation,
            ] : null,
        ]);
    }

    /**
     * Transform a proposal model into a clean repository card structure.
     */
    private function transformProposal(Proposal $proposal): array
    {
        $v = $proposal->latestVersion;
        return [
            'id'          => $proposal->proposal_id,
            'title'       => $v->title ?? 'No Title',
            'department'  => $proposal->department->department_name ?? 'N/A',
            'problem'     => $v->problem ?? '',
            'solution'    => $v->solution ?? '',
            'functions'   => $v->functions ?? '',
            'objectives'  => $v->objectives ?? '',
            'tags'        => $v->tags ?? '',
            'tech'        => $v->technologies_used ?? '',
            'date'        => $proposal->created_at ? $proposal->created_at->format('Y-m-d') : null,
            'year'        => $proposal->created_at ? $proposal->created_at->format('Y') : null,
            'author'      => $proposal->students->first()->full_name ?? 'Unknown',
        ];
    }
}
