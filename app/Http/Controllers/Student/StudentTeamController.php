<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teams are pairs: the proposal owner plus at most one teammate. A teammate
 * joins only after accepting a team request; until then the request stays
 * pending and the sender cannot send another one unless it is declined.
 */
class StudentTeamController extends Controller
{
    private const MAX_TEAM_SIZE = 2;

    public function getTeam(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$student || !$this->isMember($proposal, $student)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $members = $proposal->students()->select([
            'students.student_id',
            'students.full_name as name',
            'students.student_number as regNumber',
        ])->get()->map(function($member) {
            return [
                'id' => $member->student_id,
                'name' => $member->name,
                'regNumber' => $member->regNumber,
                'role' => $member->pivot->member_role === 'owner' ? 'Owner' : 'Member',
            ];
        });

        // The latest request sent from this team that has not been accepted.
        $teamRequest = DB::table('project_members')
            ->join('students', 'students.student_id', '=', 'project_members.student_id')
            ->where('project_members.proposal_id', $proposal->proposal_id)
            ->whereIn('project_members.invitation_status', ['pending', 'rejected'])
            ->orderByDesc('project_members.updated_at')
            ->first(['students.full_name', 'students.student_number', 'project_members.invitation_status', 'project_members.updated_at']);

        return response()->json([
            'members' => $members,
            'max_size' => self::MAX_TEAM_SIZE,
            'request' => $teamRequest && $members->count() < self::MAX_TEAM_SIZE ? [
                'name' => $teamRequest->full_name,
                'regNumber' => $teamRequest->student_number,
                'status' => $teamRequest->invitation_status,
                'date' => $teamRequest->updated_at,
            ] : null,
        ]);
    }

    public function invite(Request $request, Proposal $proposal): JsonResponse
    {
        // Only a member of this team may send a team request for it (draft or submitted).
        $inviter = $request->user()->student;
        if (!$inviter || !$this->isMember($proposal, $inviter)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($this->isLocked($proposal)) {
            return response()->json(['message' => __('messages.team.locked')], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'reg_number' => [
                'required',
                'numeric',
                'digits_between:1,6',
                'exists:students,student_number',
            ],
        ], [
            'reg_number.required' => 'Student number is required.',
            'reg_number.numeric' => 'Student number must contain numbers only.',
            'reg_number.digits_between' => 'Student number must not be more than 6 digits.',
            'reg_number.exists' => 'This student does not exist in the system.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first('reg_number'),
                'errors' => $validator->errors()
            ], 422);
        }

        $newStudent = Student::where('student_number', $request->reg_number)->first();

        if ($newStudent->student_id === $inviter->student_id) {
            return $this->fieldError(__('messages.team.self_invite'));
        }
        if ($proposal->students()->count() >= self::MAX_TEAM_SIZE) {
            return $this->fieldError(__('messages.team.team_full'));
        }
        if ($inviter->pairedProposal()) {
            return $this->fieldError(__('messages.team.already_paired'));
        }
        if ($inviter->proposalWithPendingRequest()) {
            return $this->fieldError(__('messages.team.request_pending'));
        }
        if ($newStudent->pairedProposal()) {
            return $this->fieldError(__('messages.team.invitee_paired'));
        }

        $hasActive = $newStudent->proposals()
            ->where('submission_status', 'submitted')
            ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
            ->exists();
        if ($hasActive) {
            return $this->fieldError(__('messages.team.invitee_busy'));
        }

        DB::transaction(function () use ($proposal, $newStudent) {
            // Declined requests are only kept to show the sender the answer;
            // a new request replaces them (and frees the unique key).
            DB::table('project_members')
                ->where('proposal_id', $proposal->proposal_id)
                ->where('invitation_status', 'rejected')
                ->delete();

            $proposal->students()->attach($newStudent->student_id, [
                'member_role' => 'member',
                'invitation_status' => 'pending',
                'joined_at' => null,
            ]);
        });

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('team request sent');

        return response()->json(['message' => __('messages.team.request_sent')]);
    }

    /** Withdraws the pending request sent from this team. */
    public function cancelInvite(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$student || !$this->isMember($proposal, $student)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $deleted = DB::table('project_members')
            ->where('proposal_id', $proposal->proposal_id)
            ->where('invitation_status', 'pending')
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => __('messages.team.request_missing')], 404);
        }

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('team request cancelled');

        return response()->json(['message' => __('messages.team.cancelled')]);
    }

    /** Team requests waiting for the logged-in student's answer. */
    public function invitations(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        if (!$student) {
            return response()->json(['invitations' => []]);
        }

        $invitations = $student->pendingInvitations()
            ->with(['latestVersion', 'students'])
            ->get()
            ->map(function (Proposal $proposal) {
                $owner = $proposal->students->firstWhere('pivot.member_role', 'owner') ?? $proposal->students->first();

                return [
                    'proposal_id' => $proposal->proposal_id,
                    'title' => $proposal->latestVersion->title ?? null,
                    'from_name' => $owner->full_name ?? null,
                    'from_reg_number' => $owner->student_number ?? null,
                    'date' => $proposal->pivot->created_at,
                ];
            })
            ->values();

        return response()->json(['invitations' => $invitations]);
    }

    public function accept(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$student || !$this->pendingRow($proposal, $student)->exists()) {
            return response()->json(['message' => __('messages.team.request_missing')], 404);
        }
        if ($this->isLocked($proposal)) {
            return response()->json(['message' => __('messages.team.locked')], 422);
        }
        if ($proposal->students()->count() >= self::MAX_TEAM_SIZE) {
            return response()->json(['message' => __('messages.team.team_full')], 422);
        }
        if ($student->pairedProposal()) {
            return response()->json(['message' => __('messages.team.accept_paired')], 422);
        }
        if ($proposal->submission_status === 'submitted') {
            $hasOtherActive = $student->proposals()
                ->where('submission_status', 'submitted')
                ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
                ->exists();
            if ($hasOtherActive) {
                return response()->json(['message' => __('messages.team.accept_busy')], 422);
            }
        }

        DB::transaction(function () use ($proposal, $student) {
            $this->pendingRow($proposal, $student)->update([
                'invitation_status' => 'accepted',
                'joined_at' => now(),
                'updated_at' => now(),
            ]);

            // Now paired: decline the other requests sent to this student...
            DB::table('project_members')
                ->where('student_id', $student->student_id)
                ->where('invitation_status', 'pending')
                ->update(['invitation_status' => 'rejected', 'updated_at' => now()]);

            // ...and withdraw the request this student sent from their own proposals.
            DB::table('project_members')
                ->whereIn('proposal_id', $student->proposals()
                    ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
                    ->pluck('proposals.proposal_id'))
                ->where('invitation_status', 'pending')
                ->delete();
        });

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('team request accepted');

        return response()->json(['message' => __('messages.team.accepted')]);
    }

    public function reject(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$student) {
            return response()->json(['message' => __('messages.team.request_missing')], 404);
        }

        $updated = $this->pendingRow($proposal, $student)->update([
            'invitation_status' => 'rejected',
            'updated_at' => now(),
        ]);
        if (!$updated) {
            return response()->json(['message' => __('messages.team.request_missing')], 404);
        }

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('team request declined');

        return response()->json(['message' => __('messages.team.declined')]);
    }

    private function isMember(Proposal $proposal, Student $student): bool
    {
        return $proposal->students()->where('project_members.student_id', $student->student_id)->exists();
    }

    private function isLocked(Proposal $proposal): bool
    {
        return $proposal->is_locked || $proposal->review_status === 'accepted' || $proposal->submission_status === 'archived';
    }

    private function pendingRow(Proposal $proposal, Student $student)
    {
        return DB::table('project_members')
            ->where('proposal_id', $proposal->proposal_id)
            ->where('student_id', $student->student_id)
            ->where('invitation_status', 'pending');
    }

    private function fieldError(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => ['reg_number' => [$message]],
        ], 422);
    }
}
