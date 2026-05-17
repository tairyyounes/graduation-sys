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

    public function getActivity(Request $request): JsonResponse
    {
        $user = $request->user();

        $activities = Activity::query()
            ->where('causer_id', $user->id)
            ->orWhere(function ($query) use ($user) {
                $student = $user->student;
                if ($student) {
                    $query->where('subject_type', \App\Models\Proposal::class)
                          ->whereIn('subject_id', $student->proposals->pluck('proposal_id'));
                }
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->description,
                    'description' => $activity->description, // Or detailed desc if available
                    'time' => $activity->created_at->diffForHumans(),
                    'type' => $this->getActivityType($activity),
                    'status' => $this->getActivityStatus($activity),
                    'dateGroup' => $activity->created_at->isToday() ? 'Today' : ($activity->created_at->isYesterday() ? 'Yesterday' : 'Older'),
                ];
            });

        return response()->json(['activities' => $activities]);
    }

    private function getActivityType($activity): string
    {
        $desc = strtolower($activity->description);
        if (str_contains($desc, 'submitted')) return 'submission';
        if (str_contains($desc, 'created') && str_contains($desc, 'draft')) return 'proposal';
        if (str_contains($desc, 'edited')) return 'proposal';
        if (str_contains($desc, 'version')) return 'version';
        if (str_contains($desc, 'archived')) return 'archive';
        if (str_contains($desc, 'member')) return 'team';
        if (str_contains($desc, 'decision') || str_contains($desc, 'feedback')) return 'feedback';
        return 'status';
    }

    private function getActivityStatus($activity): string
    {
        $desc = strtolower($activity->description);
        if (str_contains($desc, 'submitted')) return 'blue';
        if (str_contains($desc, 'accepted')) return 'green';
        if (str_contains($desc, 'revision')) return 'yellow';
        if (str_contains($desc, 'rejected')) return 'red';
        if (str_contains($desc, 'archived')) return 'slate';
        return 'blue';
    }
}
