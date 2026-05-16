<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentTeamController extends Controller
{
    public function getTeam(Proposal $proposal): JsonResponse
    {
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

        return response()->json(['members' => $members]);
    }

    public function invite(Request $request, Proposal $proposal): JsonResponse
    {
        $request->validate([
            'reg_number' => 'required|string|exists:students,student_number',
        ]);

        // Security: Proposal max 3 students
        if ($proposal->students()->count() >= 3) {
            return response()->json(['message' => 'Maximum team size reached.'], 422);
        }

        $newStudent = Student::where('student_number', $request->reg_number)->first();

        // Check if student is already in a team or has an active proposal
        if ($newStudent->proposals()->where('submission_status', 'submitted')->exists()) {
            return response()->json(['message' => 'This student is already part of another active proposal.'], 422);
        }

        if ($proposal->students()->where('students.student_id', $newStudent->student_id)->exists()) {
            return response()->json(['message' => 'This student is already in your team.'], 422);
        }

        $proposal->students()->attach($newStudent->student_id, [
            'member_role' => 'member',
            'invitation_status' => 'accepted', // Auto-accept for simplicity as requested "add team member"
            'joined_at' => now(),
        ]);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('team member added');

        return response()->json(['message' => 'Team member added successfully.']);
    }
}
