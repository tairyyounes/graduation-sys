<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Retrieve a filtered and paginated list of system activity logs along with KPI metrics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        // 1. Search Query (Actor name/email, description, subject name)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhere('properties', 'like', "%{$search}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], function ($uq) use ($search) {
                      $uq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Category / Action Filter
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $category = $request->input('category');
            switch ($category) {
                case 'create':
                    $query->where(function ($q) {
                        $q->where('event', 'created')
                          ->orWhere('description', 'like', '%create%')
                          ->orWhere('description', 'like', '%added%')
                          ->orWhere('description', 'like', '%import%');
                    });
                    break;
                case 'update':
                    $query->where(function ($q) {
                        $q->where('event', 'updated')
                          ->orWhere('description', 'like', '%update%')
                          ->orWhere('description', 'like', '%edit%')
                          ->orWhere('description', 'like', '%restored%')
                          ->orWhere('description', 'like', '%revision%');
                    });
                    break;
                case 'delete':
                    $query->where(function ($q) {
                        $q->where('event', 'deleted')
                          ->orWhere('description', 'like', '%delete%')
                          ->orWhere('description', 'like', '%archived%');
                    });
                    break;
                case 'review':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%decision%')
                          ->orWhere('description', 'like', '%marked as%')
                          ->orWhere('description', 'like', '%review%');
                    });
                    break;
                case 'proposal':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%proposal%')
                          ->orWhere('subject_type', 'like', '%Proposal%');
                    });
                    break;
            }
        }

        // 3. Subject / Entity Filter
        if ($request->filled('entity') && $request->input('entity') !== 'all') {
            $entity = $request->input('entity');
            $query->where('subject_type', 'like', "%{$entity}%");
        }

        // 4. Date Range Filter
        if ($request->filled('date_range') && $request->input('date_range') !== 'all') {
            $range = $request->input('date_range');
            if ($range === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($range === '7days') {
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
            } elseif ($range === '30days') {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            }
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }
        }

        // 5. Calculate KPI Summary Statistics
        $totalLogsCount = Activity::count();
        $todayLogsCount = Activity::whereDate('created_at', Carbon::today())->count();
        $activeActorsTodayCount = Activity::whereDate('created_at', Carbon::today())
            ->whereNotNull('causer_id')
            ->distinct('causer_id')
            ->count('causer_id');
        $adminActionsCount = Activity::whereHasMorph('causer', [\App\Models\User::class], function ($q) {
            $q->where('role', 'admin');
        })->count();

        // 6. Pagination
        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $activities = $query->paginate($perPage);

        // 7. Map Data with Enriched Metadata
        $mappedLogs = $activities->map(function ($activity) {
            $rawDesc = (string) $activity->description;
            $event = $activity->event ?? 'custom';

            // Determine categorization & badge visual style
            $category = $this->resolveCategory($event, $rawDesc);
            $actionLabel = $this->resolveActionLabel($event, $rawDesc, $activity->subject_type);

            // Determine Causer Info
            $causer = $activity->causer;
            $actorData = [
                'id' => $causer?->id ?? null,
                'name' => $causer?->full_name ?? ($causer ? $causer->name : 'System'),
                'email' => $causer?->email ?? null,
                'role' => $causer?->role ?? 'system',
                'initials' => $this->extractInitials($causer?->full_name ?? 'System'),
            ];

            // Determine Subject / Target Entity
            $subject = $activity->subject;
            $subjectType = $activity->subject_type ? class_basename($activity->subject_type) : null;
            $subjectName = '—';

            if ($subject) {
                $subjectName = $subject->department_name 
                    ?? $subject->title 
                    ?? $subject->full_name 
                    ?? $subject->name 
                    ?? $subject->official_email 
                    ?? $subject->email 
                    ?? "ID: #{$subject->id}";
            } elseif ($activity->subject_id) {
                $subjectName = "ID: #{$activity->subject_id}";
            }

            return [
                'id' => $activity->id,
                'category' => $category,
                'action' => $actionLabel,
                'description' => $rawDesc,
                'actor' => $actorData,
                'target' => [
                    'type' => $subjectType ?? 'General',
                    'name' => $subjectName,
                    'id' => $activity->subject_id,
                ],
                'properties' => $activity->properties ? $activity->properties->toArray() : [],
                'created_at' => $activity->created_at->toIso8601String(),
                'formatted_time' => $activity->created_at->format('Y-m-d H:i:s'),
                'relative_time' => $activity->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'logs' => $mappedLogs,
            'stats' => [
                'total' => $totalLogsCount,
                'today' => $todayLogsCount,
                'active_actors_today' => $activeActorsTodayCount,
                'admin_actions' => $adminActionsCount,
            ],
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ],
        ]);
    }

    /**
     * Resolve activity category for filtering and UI color accents.
     */
    private function resolveCategory(?string $event, string $description): string
    {
        $desc = strtolower($description);

        if ($event === 'deleted' || str_contains($desc, 'deleted') || str_contains($desc, 'archived')) {
            return 'delete';
        }
        if ($event === 'created' || str_contains($desc, 'created') || str_contains($desc, 'added') || str_contains($desc, 'import')) {
            return 'create';
        }
        if (str_contains($desc, 'marked as') || str_contains($desc, 'decision') || str_contains($desc, 'rejected') || str_contains($desc, 'approved') || str_contains($desc, 'accepted')) {
            return 'review';
        }
        if ($event === 'updated' || str_contains($desc, 'updated') || str_contains($desc, 'edited') || str_contains($desc, 'revision') || str_contains($desc, 'restored')) {
            return 'update';
        }
        if (str_contains($desc, 'proposal') || str_contains($desc, 'version')) {
            return 'proposal';
        }

        return 'system';
    }

    /**
     * Resolve humanized action label.
     */
    private function resolveActionLabel(?string $event, string $description, ?string $subjectType): string
    {
        if (in_array($event, ['created', 'updated', 'deleted'])) {
            $modelName = $subjectType ? class_basename($subjectType) : 'Item';
            return ucfirst($event) . " {$modelName}";
        }

        return ucfirst($description);
    }

    /**
     * Helper to compute avatar initials.
     */
    private function extractInitials(?string $name): string
    {
        if (!$name) {
            return 'SY';
        }

        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, min(2, mb_strlen($name))));
    }
}
