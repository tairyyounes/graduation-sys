<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class StudentDashboardController extends Controller
{
    public function getData(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $student->load('department');

        return response()->json([
            'student' => [
                'name' => $student->full_name,
                'email' => $student->official_email,
                'department' => $student->department->department_name ?? 'N/A',
                'status' => $student->is_active ? 'Active' : 'Inactive',
                'id' => $student->student_id,
            ]
        ]);
    }
}
