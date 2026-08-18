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

        // Security: Proposal max 3 students
        if ($proposal->students()->count() >= 3) {
            return response()->json(['message' => 'Maximum team size reached.'], 422);
        }

        $newStudent = Student::where('student_number', $request->reg_number)->first();
        $currentStudent = $request->user()->student;

        // Check duplicate check (same student added twice or logged-in student duplicated)
        if ($proposal->students()->where('students.student_id', $newStudent->student_id)->exists() || ($currentStudent && $newStudent->student_id === $currentStudent->student_id)) {
            return response()->json([
                'message' => 'This student is already added to this team.',
                'errors' => ['reg_number' => ['This student is already added to this team.']]
            ], 422);
        }

        // Check if student belongs to another active proposal
        $hasActive = $newStudent->proposals()
            ->where('submission_status', 'submitted')
            ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
            ->exists();
        if ($hasActive) {
            return response()->json([
                'message' => 'This student already belongs to another active proposal.',
                'errors' => ['reg_number' => ['This student already belongs to another active proposal.']]
            ], 422);
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
