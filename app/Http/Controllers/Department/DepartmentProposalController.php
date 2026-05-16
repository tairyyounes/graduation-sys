<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Decision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentProposalController extends Controller
{
    /**
     * Get proposals for the authenticated department member's department.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $departmentId = $user->department_id;

        if (!$departmentId) {
            return response()->json(['message' => 'User not assigned to any department.'], 403);
        }

        $status = $request->query('status', 'submitted');

        $proposals = Proposal::where('department_id', $departmentId)
            ->where('submission_status', $status)
            ->with(['latestVersion', 'students'])
            ->latest()
            ->get();

        return response()->json([
            'proposals' => $proposals->map(fn($p) => $this->transformProposal($p)),
        ]);
    }

    /**
     * Get statistics for the department dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $stats = [
            'total' => Proposal::where('department_id', $departmentId)->count(),
            'pending' => Proposal::where('department_id', $departmentId)->where('review_status', 'pending')->count(),
            'accepted' => Proposal::where('department_id', $departmentId)->where('review_status', 'accepted')->count(),
            'rejected' => Proposal::where('department_id', $departmentId)->where('review_status', 'rejected')->count(),
            'revision' => Proposal::where('department_id', $departmentId)->where('review_status', 'revision_requested')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }

    /**
     * Get details of a specific proposal.
     */
    public function show(Request $request, Proposal $proposal): JsonResponse
    {
        // Security: Check if proposal belongs to user's department
        if ($proposal->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized access to this department\'s data.'], 403);
        }

        $proposal->load(['latestVersion', 'students', 'versions', 'decisions.reviewer']);

        return response()->json([
            'proposal' => $this->transformProposal($proposal),
            'history' => $proposal->versions,
            'decisions' => $proposal->decisions,
        ]);
    }

    /**
     * Store a review decision for a proposal.
     */
    public function review(Request $request, Proposal $proposal): JsonResponse
    {
        if ($proposal->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'decision' => 'required|in:accepted,rejected,revision_requested',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $proposal) {
            $decision = Decision::create([
                'proposal_id' => $proposal->proposal_id,
                'reviewer_id' => $request->user()->id,
                'decision_type' => $request->decision,
                'decision_note' => $request->note,
                'decision_date' => now(),
            ]);

            $proposal->update([
                'review_status' => $request->decision,
                'is_locked' => ($request->decision === 'accepted'),
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log("proposal marked as {$request->decision}");

            return response()->json([
                'message' => 'Review submitted successfully.',
                'decision' => $decision,
            ]);
        });
    }

    /**
     * Transform proposal model into a frontend-friendly array.
     */
    private function transformProposal(Proposal $proposal): array
    {
        $v = $proposal->latestVersion;
        return [
            'id' => $proposal->proposal_id,
            'title' => $v->title ?? 'No Title',
            'author' => $proposal->students->first()->full_name ?? 'Unknown',
            'author_email' => $proposal->students->first()->official_email ?? '',
            'department' => $proposal->department->department_name ?? 'N/A',
            'problem' => $v->problem ?? '',
            'solution' => $v->solution ?? '',
            'functions' => $v->functions ?? '',
            'objectives' => $v->objectives ?? '',
            'tags' => $v->tags ?? '',
            'tech' => $v->technologies_used ?? '',
            'status' => $proposal->review_status,
            'submission_status' => $proposal->submission_status,
            'is_locked' => $proposal->is_locked,
            'date' => $proposal->updated_at->format('Y-m-d'),
            'similarity' => '12%', // Mock for now, logic to be added
        ];
    }
}
