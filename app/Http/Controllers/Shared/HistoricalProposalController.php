<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoricalProposalController extends Controller
{
    /**
     * Get a list of previous accepted and rejected proposals.
     * Excludes proposals from the current semester.
     */
    public function index(Request $request): JsonResponse
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        if ($currentMonth <= 6) {
            $semesterStart = now()->setDate($currentYear, 1, 1)->startOfDay();
        } else {
            $semesterStart = now()->setDate($currentYear, 7, 1)->startOfDay();
        }

        $proposalsQuery = Proposal::with(['department', 'latestVersion', 'students'])
            ->where(function ($query) use ($semesterStart) {
                $query->where('submission_status', 'archived')
                      ->orWhere(function ($q) use ($semesterStart) {
                          $q->where('created_at', '<', $semesterStart)
                            ->whereIn('review_status', ['accepted', 'rejected']);
                      });
            })
            ->orderBy('created_at', 'desc');

        // Optional: Filter by department if a student calls this and we only want to show their department's past proposals.
        // If we want them to see all, we don't filter. The user said "so the students can see this proposals".
        // Let's return all.
        
        $proposals = $proposalsQuery->get()->map(function ($proposal) {
            $v = $proposal->latestVersion;
            return [
                'id' => $proposal->proposal_id,
                'title' => $v ? $v->title : 'Untitled',
                'domain' => $proposal->department ? $proposal->department->department_name : 'N/A',
                'tags' => $v ? $v->tags : '',
                'problem' => $v ? $v->problem : '',
                'solution' => $v ? $v->solution : '',
                'objectives' => $v ? $v->objectives : '',
                'functions' => $v ? $v->functions : '',
                'technologies' => $v ? $v->technologies_used : '',
                'department' => $proposal->department ? $proposal->department->department_name : 'Unknown',
                'status' => $proposal->review_status,
                'created_at' => $proposal->created_at->format('Y-m-d'),
                'students' => $proposal->students->map(function ($student) {
                    return [
                        'name' => $student->full_name,
                        'student_number' => $student->student_number
                    ];
                })
            ];
        });

        return response()->json([
            'proposals' => $proposals
        ]);
    }

    /**
     * Add a single historical proposal.
     * Allowed for Admin and Department Head.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,department_id',
            'date' => 'required|date',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'objectives' => 'nullable|string',
            'functions' => 'nullable|string',
            'tags' => 'nullable|string',
            'technologies' => 'nullable|string',
            'tech' => 'nullable|string',
            'domain' => 'nullable|string',
        ]);

        $departmentId = in_array($user->role, ['department_head', 'department_member']) 
            ? $user->department_id 
            : ($validated['department_id'] ?? null);

        if (!$departmentId) {
            return response()->json(['message' => 'Department is required.'], 400);
        }

        DB::beginTransaction();
        try {
            $proposal = Proposal::forceCreate([
                'department_id' => $departmentId,
                'submission_status' => 'archived',
                'review_status' => 'accepted', // Historical proposals are stored as accepted archive
                'created_at' => $validated['date'],
                'updated_at' => $validated['date'],
            ]);

            ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => 1,
                'title' => $validated['title'],
                'tags' => $validated['tags'] ?? $validated['domain'] ?? null,
                'problem' => $validated['problem'] ?? null,
                'solution' => $validated['solution'] ?? null,
                'objectives' => $validated['objectives'] ?? null,
                'functions' => $validated['functions'] ?? null,
                'technologies_used' => $validated['technologies'] ?? $validated['tech'] ?? null,
            ]);

            DB::commit();
            return response()->json(['message' => 'Proposal added successfully.', 'proposal' => $proposal], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding historical proposal: ' . $e->getMessage());
            return response()->json(['message' => 'Error adding proposal.'], 500);
        }
    }

    /**
     * Import historical proposals via CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['csv', 'txt'])) {
                        $fail('The file must be a CSV or TXT file.');
                    }
                },
            ],
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        
        $handle = fopen($file->getRealPath(), "r");
        if (!$handle) {
            return response()->json(['message' => 'Unable to read the uploaded CSV file.'], 422);
        }

        $rawHeader = fgetcsv($handle, 5000, ",");
        if (!$rawHeader) {
            fclose($handle);
            return response()->json(['message' => 'The uploaded file is empty.'], 422);
        }

        // Normalize headers to identify column positions regardless of order or naming variations
        $colMap = [];
        foreach ($rawHeader as $index => $colName) {
            // Remove UTF-8 BOM and trim cleanly
            $clean = str_replace(["\xEF\xBB\xBF", "\xFE\xFF", "\xFF\xFE"], '', trim($colName));
            $cleanLower = mb_strtolower($clean, 'UTF-8');

            if (str_contains($cleanLower, 'title') || str_contains($cleanLower, 'عنوان')) {
                $colMap['title'] = $index;
            } elseif (str_contains($cleanLower, 'problem') || str_contains($cleanLower, 'مشكل')) {
                $colMap['problem'] = $index;
            } elseif (str_contains($cleanLower, 'solution') || str_contains($cleanLower, 'حل')) {
                $colMap['solution'] = $index;
            } elseif (str_contains($cleanLower, 'function') || str_contains($cleanLower, 'وظائف') || str_contains($cleanLower, 'خاصيات')) {
                $colMap['functions'] = $index;
            } elseif (str_contains($cleanLower, 'objective') || str_contains($cleanLower, 'أهداف') || str_contains($cleanLower, 'اهداف')) {
                $colMap['objectives'] = $index;
            } elseif (str_contains($cleanLower, 'tag') || str_contains($cleanLower, 'domain') || str_contains($cleanLower, 'وسوم') || str_contains($cleanLower, 'مجال')) {
                $colMap['tags'] = $index;
            } elseif (str_contains($cleanLower, 'tech') || str_contains($cleanLower, 'تقني')) {
                $colMap['technologies'] = $index;
            } elseif (str_contains($cleanLower, 'date') || str_contains($cleanLower, 'تاريخ')) {
                $colMap['date'] = $index;
            } elseif (str_contains($cleanLower, 'dept') || str_contains($cleanLower, 'department') || str_contains($cleanLower, 'قسم')) {
                $colMap['department_id'] = $index;
            }
        }

        $imported = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                // Determine title
                $titleIndex = $colMap['title'] ?? 0;
                if (!isset($data[$titleIndex]) || trim($data[$titleIndex]) === '') {
                    continue; // Skip empty rows
                }

                $title = trim($data[$titleIndex]);
                $problem = isset($colMap['problem']) && isset($data[$colMap['problem']]) ? trim($data[$colMap['problem']]) : ($data[1] ?? '');
                $solution = isset($colMap['solution']) && isset($data[$colMap['solution']]) ? trim($data[$colMap['solution']]) : ($data[2] ?? '');
                $functions = isset($colMap['functions']) && isset($data[$colMap['functions']]) ? trim($data[$colMap['functions']]) : ($data[3] ?? '');
                $objectives = isset($colMap['objectives']) && isset($data[$colMap['objectives']]) ? trim($data[$colMap['objectives']]) : ($data[4] ?? '');
                $tags = isset($colMap['tags']) && isset($data[$colMap['tags']]) ? trim($data[$colMap['tags']]) : ($data[5] ?? '');
                $technologies = isset($colMap['technologies']) && isset($data[$colMap['technologies']]) ? trim($data[$colMap['technologies']]) : ($data[6] ?? '');

                // Parse date safely
                $rawDate = isset($colMap['date']) && isset($data[$colMap['date']]) ? trim($data[$colMap['date']]) : null;
                if ($rawDate && strtotime($rawDate)) {
                    $date = date('Y-m-d', strtotime($rawDate));
                } else {
                    $date = now()->subYear()->format('Y-m-d');
                }

                // Determine department ID
                $deptFromCsv = isset($colMap['department_id']) && isset($data[$colMap['department_id']]) ? trim($data[$colMap['department_id']]) : (isset($data[8]) ? trim($data[8]) : null);
                
                $departmentId = in_array($user->role, ['department_head', 'department_member']) 
                    ? ($user->department_id ?: ($deptFromCsv ?: 1))
                    : ($deptFromCsv ?: ($user->department_id ?: 1));

                if (!$departmentId) {
                    $failed++;
                    continue; // Skip if no department ID
                }

                $proposal = Proposal::forceCreate([
                    'department_id' => $departmentId,
                    'submission_status' => 'archived',
                    'review_status' => 'accepted',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                ProposalVersion::create([
                    'proposal_id' => $proposal->proposal_id,
                    'version_number' => 1,
                    'title' => $title,
                    'tags' => $tags,
                    'problem' => $problem,
                    'solution' => $solution,
                    'objectives' => $objectives,
                    'functions' => $functions,
                    'technologies_used' => $technologies,
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);
            return response()->json(['message' => "$imported proposals imported successfully. $failed failed."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            Log::error('Error importing historical proposals: ' . $e->getMessage());
            return response()->json(['message' => 'Error importing proposals: ' . $e->getMessage()], 500);
        }
    }
}
