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
            ->where('created_at', '<', $semesterStart)
            ->whereIn('review_status', ['accepted', 'rejected'])
            ->orderBy('created_at', 'desc');

        // Optional: Filter by department if a student calls this and we only want to show their department's past proposals.
        // If we want them to see all, we don't filter. The user said "so the students can see this proposals".
        // Let's return all.
        
        $proposals = $proposalsQuery->get()->map(function ($proposal) {
            return [
                'id' => $proposal->proposal_id,
                'title' => $proposal->latestVersion ? $proposal->latestVersion->title : 'Untitled',
                'domain' => $proposal->latestVersion ? $proposal->latestVersion->domain : 'N/A', // Assuming domain exists, if not it will be null
                'problem' => $proposal->latestVersion ? $proposal->latestVersion->problem : '',
                'solution' => $proposal->latestVersion ? $proposal->latestVersion->solution : '',
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
            'domain' => 'nullable|string|max:255', // tags/domain
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'objectives' => 'nullable|string',
            'functions' => 'nullable|string',
            'technologies' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,department_id',
            'date' => 'required|date',
        ]);

        $departmentId = $user->role === 'department_head' ? $user->department_id : $validated['department_id'];

        if (!$departmentId) {
            return response()->json(['message' => 'Department ID is required.'], 400);
        }

        DB::beginTransaction();
        try {
            $proposal = Proposal::create([
                'department_id' => $departmentId,
                'submission_status' => 'archived',
                'review_status' => 'accepted', // We assume imported historical proposals are accepted
                'created_at' => $validated['date'],
                'updated_at' => $validated['date'],
            ]);

            ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => 1,
                'title' => $validated['title'],
                'tags' => $validated['domain'], // mapping domain to tags if domain column doesn't exist directly
                'problem' => $validated['problem'],
                'solution' => $validated['solution'],
                'objectives' => $validated['objectives'],
                'functions' => $validated['functions'],
                'technologies_used' => $validated['technologies'],
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
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        
        $handle = fopen($file->getRealPath(), "r");
        $header = fgetcsv($handle, 1000, ",");
        
        // Expected columns: Title, Domain, Problem, Solution, Objectives, Functions, Technologies, Date, Department ID
        // To make it simple, we will map by index assuming the user downloads a template.
        // Format: [Title, Domain, Problem, Solution, Objectives, Functions, Technologies, Date, Department ID]

        $imported = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                // Skip empty rows
                if (!isset($data[0]) || trim($data[0]) === '') {
                    continue;
                }

                $title = trim($data[0]);
                $domain = isset($data[1]) ? trim($data[1]) : '';
                $problem = isset($data[2]) ? trim($data[2]) : '';
                $solution = isset($data[3]) ? trim($data[3]) : '';
                $objectives = isset($data[4]) ? trim($data[4]) : '';
                $functions = isset($data[5]) ? trim($data[5]) : '';
                $technologies = isset($data[6]) ? trim($data[6]) : '';
                $date = isset($data[7]) && trim($data[7]) !== '' ? trim($data[7]) : now()->subYear()->format('Y-m-d'); // Default to 1 year ago
                
                $departmentId = $user->role === 'department_head' 
                    ? $user->department_id 
                    : (isset($data[8]) && trim($data[8]) !== '' ? trim($data[8]) : null);

                if (!$departmentId) {
                    $failed++;
                    continue; // Skip if no department ID for admin
                }

                $proposal = Proposal::create([
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
                    'tags' => $domain,
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
            fclose($handle);
            Log::error('Error importing historical proposals: ' . $e->getMessage());
            return response()->json(['message' => 'Error importing proposals. Please check your CSV format.'], 500);
        }
    }
}
