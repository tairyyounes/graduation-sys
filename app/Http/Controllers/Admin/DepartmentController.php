<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Retrieve all departments along with statistics (members count).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $departments = DB::table('departments')
            ->select('departments.department_id', 'departments.department_name')
            ->addSelect([
                'members_count' => DB::table('users')
                    ->selectRaw('count(*)')
                    ->whereColumn('department_id', 'departments.department_id')
                    ->where('role', 'department_member'),
                'students_count' => DB::table('students')
                    ->selectRaw('count(*)')
                    ->whereColumn('department_id', 'departments.department_id')
                    ->whereNull('deleted_at')
            ])
            ->orderBy('departments.department_name')
            ->get();

        $mappedDepartments = $departments->map(function ($dept) {
            return [
                'id' => $dept->department_id,
                'name' => $dept->department_name,
                'members' => $dept->members_count,
                'students' => $dept->students_count,
                'proposals' => 0, // Placeholder
            ];
        });

        return response()->json([
            'departments' => $mappedDepartments,
        ]);
    }

    /**
     * Store a newly created department in the database.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255', 'unique:departments,department_name'],
        ]);

        DB::table('departments')->insert([
            'department_name' => $validated['department_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        activity()
            ->causedBy($request->user())
            ->log("Created department: {$validated['department_name']}");

        return response()->json(['message' => 'Department created successfully.'], 201);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'department_name')->ignore($id, 'department_id')],
        ]);

        $updated = DB::table('departments')
            ->where('department_id', $id)
            ->update([
                'department_name' => $validated['department_name'],
                'updated_at' => now(),
            ]);

        if ($updated) {
            activity()
                ->causedBy($request->user())
                ->log("Updated department ID {$id} to: {$validated['department_name']}");
        }

        return response()->json(['message' => 'Department updated successfully.']);
    }

    /**
     * Remove the specified department from the database.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $department = DB::table('departments')->where('department_id', $id)->first();
        if (!$department) {
            return response()->json(['message' => 'Department not found.'], 404);
        }

        DB::table('departments')->where('department_id', $id)->delete();

        activity()
            ->causedBy($request->user())
            ->log("Deleted department: {$department->department_name}");

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    /**
     * Display a specific department and its members/proposals.
     */
    public function show($id): JsonResponse
    {
        $department = DB::table('departments')->where('department_id', $id)->first();
        if (!$department) {
            return response()->json(['message' => 'Department not found.'], 404);
        }

        $members = DB::table('users')
            ->where('department_id', $id)
            ->where('role', 'department_member')
            ->select('id', 'full_name as name', 'email', 'is_active')
            ->orderBy('full_name')
            ->get()
            ->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'status' => $member->is_active ? 'Active' : 'Disabled',
                ];
            });
            
        $students = DB::table('students')
            ->where('department_id', $id)
            ->whereNull('deleted_at')
            ->select('student_id as id', 'student_number', 'full_name as name', 'official_email as email', 'semester', 'is_active')
            ->orderBy('full_name')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->name,
                    'email' => $student->email,
                    'semester' => $student->semester,
                    'status' => $student->is_active ? 'Active' : 'Disabled',
                ];
            });

        return response()->json([
            'department' => [
                'id' => $department->department_id,
                'name' => $department->department_name,
                'members_count' => count($members),
                'proposals_count' => 0, // Placeholder
            ],
            'members' => $members,
            'students' => $students,
            'proposals' => [], // Placeholder
        ]);
    }
}
