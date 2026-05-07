<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Retrieve a paginated list of system activity logs.
     * This method fetches logs tracked by the Spatie Activitylog package.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Fetch activity logs, eager loading the causer (the User who performed the action)
        // to prevent N+1 query performance issues. We order by the newest first.
        $activities = Activity::with('causer')
            ->latest()
            ->paginate(50); // Using Laravel's built-in pagination, 50 items per page

        // Map the data into a simplified array structure for the Vue frontend
        $mappedLogs = $activities->map(function ($activity) {
            // Determine the human-readable action based on the event or description
            $action = $activity->description;
            if (in_array($activity->event, ['created', 'updated', 'deleted'])) {
                // If it's a standard Eloquent event, format it nicely
                $modelName = class_basename($activity->subject_type);
                $action = ucfirst($activity->event) . " {$modelName}";
            }

            // Determine the target (e.g., the specific user or entity that was affected)
            // If the subject exists and has a name or email, use it. Otherwise use the ID.
            $target = '—';
            if ($activity->subject) {
                $target = $activity->subject->full_name ?? $activity->subject->email ?? "ID: {$activity->subject->id}";
            }

            return [
                'time' => $activity->created_at->format('Y-m-d H:i'), // e.g., "2 hours ago"
                'actor' => $activity->causer ? $activity->causer->full_name : 'System',
                'action' => $action,
                'target' => $target,
            ];
        });

        return response()->json([
            'logs' => $mappedLogs,
            // Include pagination metadata for the frontend
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
            ]
        ]);
    }
}
