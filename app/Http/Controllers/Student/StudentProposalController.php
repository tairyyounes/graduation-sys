<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Decision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        
        $proposals = $student->proposals()->with(['latestVersion', 'department'])->get();

        $drafts = $proposals->where('submission_status', 'draft')->map(fn($p) => $this->transformProposal($p))->values();
        $active = $proposals->where('submission_status', 'submitted')->map(fn($p) => $this->transformProposal($p))->first();
        $archived = $proposals->where('submission_status', 'archived')->map(fn($p) => $this->transformProposal($p))->values();

        return response()->json([
            'drafts' => $drafts,
            'active' => $active,
            'archived' => $archived,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'functions' => 'nullable|string',
            'objectives' => 'nullable|string',
            'tags' => 'nullable|string',
            'tech' => 'nullable|string',
        ]);

        $student = $request->user()->student;

        return DB::transaction(function () use ($request, $student) {
            $proposal = Proposal::create([
                'department_id' => $student->department_id,
                'submission_status' => 'draft',
                'review_status' => 'pending',
            ]);

            ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => 1,
                'title' => $request->title,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'functions' => $request->functions,
                'objectives' => $request->objectives,
                'tags' => $request->tags,
                'technologies_used' => $request->tech,
            ]);

            $proposal->students()->attach($student->student_id, [
                'member_role' => 'owner',
                'invitation_status' => 'accepted',
                'joined_at' => now(),
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log('draft created');

            return response()->json([
                'message' => 'Draft created successfully.',
                'proposal' => $this->transformProposal($proposal->load('latestVersion')),
            ], 201);
        });
    }

    public function update(Request $request, Proposal $proposal): JsonResponse
    {
        // Security: Student can only access their own proposals
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Cannot edit accepted or locked proposals
        if ($proposal->is_locked || $proposal->review_status === 'accepted') {
            return response()->json(['message' => 'Proposal is locked or already accepted.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'functions' => 'nullable|string',
            'objectives' => 'nullable|string',
            'tags' => 'nullable|string',
            'tech' => 'nullable|string',
            'note' => 'nullable|string|max:255', // Version note
        ]);

        return DB::transaction(function () use ($request, $proposal) {
            $latestVersion = $proposal->latestVersion;
            
            // Create new version
            $newVersion = ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => $latestVersion->version_number + 1,
                'title' => $request->title,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'functions' => $request->functions,
                'objectives' => $request->objectives,
                'tags' => $request->tags,
                'technologies_used' => $request->tech,
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log('proposal edited');

            activity()
                ->performedOn($newVersion)
                ->causedBy($request->user())
                ->log('version created');

            return response()->json([
                'message' => 'Proposal updated (new version created).',
                'proposal' => $this->transformProposal($proposal->load('latestVersion')),
            ]);
        });
    }

    public function submit(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Cannot submit more than one active proposal
        $hasActive = $student->proposals()->where('submission_status', 'submitted')->exists();
        if ($hasActive && $proposal->submission_status !== 'submitted') {
            return response()->json(['message' => 'You already have an active submitted proposal.'], 422);
        }

        $proposal->update([
            'submission_status' => 'submitted',
            'review_status' => 'pending',
        ]);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('proposal submitted');

        return response()->json(['message' => 'Proposal submitted for review.']);
    }

    public function archive(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $proposal->update(['submission_status' => 'archived']);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('draft archived');

        return response()->json(['message' => 'Proposal archived.']);
    }

    public function destroy(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Student cannot delete submitted proposals
        if ($proposal->submission_status === 'submitted') {
            return response()->json(['message' => 'Cannot delete a submitted proposal.'], 403);
        }

        $proposal->delete(); // This will cascade delete versions and members if foreign keys are set to cascade

        return response()->json(['message' => 'Proposal deleted successfully.']);
    }

    public function versions(Proposal $proposal): JsonResponse
    {
        $versions = $proposal->versions()->orderBy('version_number', 'desc')->get();
        return response()->json(['versions' => $versions]);
    }

    public function decision(Proposal $proposal): JsonResponse
    {
        $decision = $proposal->decisions()->with('reviewer')->latest()->first();

        if (!$decision) {
            return response()->json(['message' => 'Waiting for department review.'], 200);
        }

        return response()->json([
            'decision' => [
                'type' => $decision->decision_type,
                'note' => $decision->decision_note,
                'reviewer' => $decision->reviewer->full_name,
                'date' => $decision->decision_date,
            ]
        ]);
    }

    public function similarity(Proposal $proposal): JsonResponse
    {
        // Security: only compare within the same department
        $departmentId = $proposal->department_id;

        $matches = Proposal::where('department_id', $departmentId)
            ->where('proposal_id', '!=', $proposal->proposal_id)
            ->where('submission_status', 'submitted')
            ->with('latestVersion')
            ->get()
            ->map(function($other) {
                // Mock similarity score calculation
                return [
                    'id' => $other->proposal_id,
                    'title' => $other->latestVersion->title,
                    'author' => $other->students->first()->full_name ?? 'Anonymous',
                    'score' => rand(5, 45) . '%', // Mock score
                    'year' => $other->created_at->format('Y'),
                ];
            });

        return response()->json([
            'results' => $matches,
            'message' => $matches->isEmpty() ? 'No other proposals found in this department to compare.' : 'Similarity analysis generated.'
        ]);
    }

    private function transformProposal(Proposal $proposal): array
    {
        $v = $proposal->latestVersion;
        return [
            'id' => $proposal->proposal_id,
            'title' => $v->title ?? 'No Title',
            'domain' => $proposal->department->department_name ?? 'N/A',
            'problem' => $v->problem ?? '',
            'solution' => $v->solution ?? '',
            'functions' => $v->functions ?? '',
            'objectives' => $v->objectives ?? '',
            'tags' => $v->tags ?? '',
            'tech' => $v->technologies_used ?? '',
            'status' => $proposal->review_status,
            'submission_status' => $proposal->submission_status,
            'date' => $proposal->updated_at->format('Y-m-d'),
            'version' => $v->version_number ?? 1,
            'similarity' => null, // Placeholder as requested
        ];
    }
}
