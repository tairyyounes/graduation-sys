<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\TeamLeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Requests from students to leave a team whose proposal is active. The
 * department reads the reason and decides: rejecting keeps the team and the
 * proposal as they are; approving cancels the proposal for the whole team so
 * every member has to submit a new one.
 */
class TeamLeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = TeamLeaveRequest::query()
            ->with([
                'student:student_id,full_name,student_number',
                'decider:id,full_name',
                'proposal.latestVersion',
                'proposal.students:students.student_id,full_name,student_number',
            ])
            ->when($user->role !== 'admin', fn ($q) => $q->whereHas(
                'proposal',
                fn ($p) => $p->where('department_id', $user->department_id)
            ))
            // Pending first, newest first inside each group.
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->get()
            ->map(fn (TeamLeaveRequest $leave) => [
                'id' => $leave->id,
                'status' => $leave->status,
                'reason' => $leave->reason,
                'date' => $leave->created_at,
                'decided_at' => $leave->decided_at,
                'decided_by' => $leave->decider->full_name ?? null,
                'student' => [
                    'name' => $leave->student->full_name ?? null,
                    'regNumber' => $leave->student->student_number ?? null,
                ],
                'proposal' => [
                    'id' => $leave->proposal_id,
                    'title' => $leave->proposal->latestVersion->title ?? null,
                    'review_status' => $leave->proposal->review_status ?? null,
                    'submission_status' => $leave->proposal->submission_status ?? null,
                ],
                'team' => $leave->proposal
                    ? $leave->proposal->students->map(fn ($s) => [
                        'name' => $s->full_name,
                        'regNumber' => $s->student_number,
                    ])->values()
                    : [],
            ])
            ->values();

        return response()->json(['requests' => $requests]);
    }

    public function decide(Request $request, TeamLeaveRequest $leaveRequest): JsonResponse
    {
        $user = $request->user();
        $proposal = $leaveRequest->proposal;

        if (!$proposal || ($user->role !== 'admin' && $proposal->department_id !== $user->department_id)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => __('messages.leave.already_decided')], 422);
        }

        $approve = $request->decision === 'approve';

        DB::transaction(function () use ($leaveRequest, $proposal, $user, $approve) {
            $leaveRequest->update([
                'status' => $approve ? 'approved' : 'rejected',
                'decided_by' => $user->id,
                'decided_at' => now(),
            ]);

            if (!$approve) {
                return;
            }

            $this->cancelProposal($proposal);

            // The team no longer exists, so other members' pending requests are settled too.
            TeamLeaveRequest::where('proposal_id', $proposal->proposal_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'decided_by' => $user->id,
                    'decided_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        activity()
            ->performedOn($proposal)
            ->causedBy($user)
            ->log($approve ? 'team leave request approved, proposal cancelled' : 'team leave request rejected');

        return response()->json([
            'message' => __($approve ? 'messages.leave.approved' : 'messages.leave.rejected'),
        ]);
    }

    /**
     * Cancelled = archived + rejected + locked. Archived takes it off every
     * member's active slot (teamProposal() and the "active proposal" guards on
     * create/submit/restore all skip it), rejected clears an "accepted" status
     * that would otherwise still block new proposals, and the lock stops the
     * team from restoring it.
     */
    private function cancelProposal(Proposal $proposal): void
    {
        $proposal->update([
            'submission_status' => 'archived',
            'review_status' => 'rejected',
            'is_locked' => true,
        ]);

        // Unanswered team requests on the cancelled proposal are dropped.
        DB::table('project_members')
            ->where('proposal_id', $proposal->proposal_id)
            ->where('invitation_status', 'pending')
            ->delete();
    }
}
