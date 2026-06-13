<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\ReviewCommittee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewCommitteeController extends Controller
{
    /**
     * Display a listing of the committees for the authenticated user's department.
     */
    public function index(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $committees = ReviewCommittee::with('users:id,full_name,email')
            ->where('department_id', $departmentId)
            ->latest()
            ->get();

        $availableMembers = User::where('department_id', $departmentId)
            ->whereIn('role', ['department_member', 'department_head'])
            ->where('is_active', true)
            ->select('id', 'full_name', 'email')
            ->get();

        return response()->json([
            'committees' => $committees,
            'available_members' => $availableMembers,
        ]);
    }

    /**
     * Store a newly created committee.
     */
    public function store(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'array',
            'members.*' => 'exists:users,id',
        ]);

        $committee = ReviewCommittee::create([
            'name' => $request->name,
            'department_id' => $departmentId,
        ]);

        if ($request->has('members') && is_array($request->members)) {
            // Ensure members belong to the same department
            $validMembers = User::where('department_id', $departmentId)
                ->whereIn('id', $request->members)
                ->pluck('id');
            $committee->users()->attach($validMembers);
        }

        return response()->json([
            'message' => 'Committee created successfully.',
            'committee' => $committee->load('users:id,full_name,email'),
        ], 201);
    }

    /**
     * Update the specified committee.
     */
    public function update(Request $request, ReviewCommittee $committee): JsonResponse
    {
        if ($committee->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'array',
            'members.*' => 'exists:users,id',
        ]);

        $committee->update([
            'name' => $request->name,
        ]);

        if ($request->has('members') && is_array($request->members)) {
            $validMembers = User::where('department_id', $committee->department_id)
                ->whereIn('id', $request->members)
                ->pluck('id');
            $committee->users()->sync($validMembers);
        } else {
            $committee->users()->detach();
        }

        return response()->json([
            'message' => 'Committee updated successfully.',
            'committee' => $committee->load('users:id,full_name,email'),
        ]);
    }

    /**
     * Remove the specified committee.
     */
    public function destroy(Request $request, ReviewCommittee $committee): JsonResponse
    {
        if ($committee->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $committee->delete();

        return response()->json([
            'message' => 'Committee deleted successfully.',
        ]);
    }
}
