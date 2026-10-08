<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAiCorpus;
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
            SyncAiCorpus::dispatch();
            return response()->json(['message' => 'Proposal added successfully.', 'proposal' => $proposal], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding historical proposal: ' . $e->getMessage());
            return response()->json(['message' => 'Error adding proposal.'], 500);
        }
    }

    /**
     * Column order used when the CSV has no header row
     * (matches the format shown in the import modal).
     */
    private const POSITIONAL_COLUMNS = [
        'title', 'tags', 'problem', 'solution', 'objectives',
        'functions', 'technologies', 'date', 'department_id',
    ];

    /**
     * Accepted header names per column (lower-cased, compared exactly).
     */
    private const HEADER_ALIASES = [
        'title'         => ['title', 'project title', 'العنوان', 'عنوان', 'عنوان المشروع'],
        'tags'          => ['tags', 'tag', 'domain', 'keywords', 'الوسوم', 'وسوم', 'المجال', 'الكلمات المفتاحية'],
        'problem'       => ['problem', 'problem statement', 'description', 'المشكلة', 'مشكلة'],
        'solution'      => ['solution', 'proposed solution', 'الحل', 'حل', 'الحل المقترح'],
        'objectives'    => ['objectives', 'objective', 'goals', 'الأهداف', 'الاهداف', 'أهداف', 'اهداف'],
        'functions'     => ['functions', 'function', 'features', 'الوظائف', 'وظائف', 'الخاصيات'],
        'technologies'  => ['technologies', 'technology', 'tech', 'technologies used', 'technologies_used', 'التقنيات', 'تقنيات', 'التقنيات المستخدمة'],
        'date'          => ['date', 'submission date', 'التاريخ', 'تاريخ'],
        'department_id' => ['deptid', 'dept id', 'dept', 'department', 'department_id', 'department id', 'القسم', 'قسم'],
    ];

    /** Max row errors returned to the client (the total is always reported). */
    private const MAX_REPORTED_ERRORS = 100;

    /**
     * Import historical proposals via CSV.
     *
     * The whole file is validated first. If any row is invalid nothing is
     * imported and the response lists every problem with its row number,
     * so the file can be fixed and re-uploaded without creating duplicates.
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
            'department_id' => 'nullable|exists:departments,department_id',
        ], [
            // PHP rejected the upload before Laravel saw it (upload_max_filesize / post_max_size).
            'file.uploaded' => sprintf(
                'The file was rejected by the server before it could be read. It is probably larger than the PHP upload limit (upload_max_filesize = %s, post_max_size = %s).',
                ini_get('upload_max_filesize'),
                ini_get('post_max_size')
            ),
            'file.max' => 'The file is larger than the 10 MB limit.',
            'department_id.exists' => 'The selected default department does not exist.',
        ]);

        $user = auth()->user();
        $isDeptUser = in_array($user->role, ['department_head', 'department_member']);

        $contents = file_get_contents($request->file('file')->getRealPath());
        if ($contents === false) {
            return $this->importError('read_failed', 'Unable to read the uploaded CSV file.');
        }

        // Strip UTF-8 BOM, then make sure the text is UTF-8 (Excel often saves ANSI/UTF-16)
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        if (str_starts_with($contents, "\xFF\xFE") || str_starts_with($contents, "\xFE\xFF")) {
            return $this->importError('not_utf8', 'The file is saved as UTF-16. Save it as "CSV UTF-8 (Comma delimited)" and upload again.');
        }
        if (!mb_check_encoding($contents, 'UTF-8')) {
            return $this->importError('not_utf8', 'The file is not UTF-8 encoded. Save it as "CSV UTF-8 (Comma delimited)" and upload again.');
        }
        if (trim($contents) === '') {
            return $this->importError('empty', 'The uploaded file is empty.');
        }

        // Parse all rows (keeping physical line numbers for error messages)
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);
        $rows = [];
        $line = 0;
        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;
            if ($data === [null] || implode('', array_map('trim', $data)) === '') {
                continue; // blank line
            }
            $rows[] = ['line' => $line, 'data' => $data];
        }
        fclose($handle);

        if (empty($rows)) {
            return $this->importError('empty', 'The uploaded file has no rows.');
        }

        // Header row is optional: use it only if it actually looks like a header
        $colMap = $this->detectHeader($rows[0]['data']);
        $hasHeader = $colMap !== null;
        if ($hasHeader) {
            array_shift($rows);
            if (!isset($colMap['title'])) {
                return $this->importError('header_no_title', 'The header row has no "Title" column.');
            }
        } else {
            $colMap = array_flip(self::POSITIONAL_COLUMNS);
        }

        if (empty($rows)) {
            return $this->importError('no_rows', 'The file only contains a header row, no proposals.');
        }

        $departments = DB::table('departments')->pluck('department_id', 'department_name');
        $deptIds = $departments->values()->map(fn ($id) => (string) $id)->all();
        $deptByName = $departments->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id])->all();
        $defaultDept = $isDeptUser ? $user->department_id : ($request->input('department_id') ?: null);

        $minColumns = $hasHeader ? 1 : 7; // positional: Title..Technologies are required, Date/DeptID optional
        $errors = [];
        $errorCount = 0;
        $invalidRows = 0;
        $valid = [];

        foreach ($rows as $row) {
            $data = $row['data'];
            $rowErrors = [];
            $get = fn (string $key) => isset($colMap[$key], $data[$colMap[$key]]) ? trim((string) $data[$colMap[$key]]) : '';

            if (!$hasHeader && count($data) < $minColumns) {
                $rowErrors[] = ['code' => 'column_count', 'params' => ['expected' => $minColumns, 'got' => count($data)],
                    'message' => sprintf('Expected at least %d columns but found %d. Check for missing commas or unquoted text containing commas.', $minColumns, count($data))];
            }

            $title = $get('title');
            if ($title === '') {
                $rowErrors[] = ['code' => 'title_missing', 'params' => [], 'message' => 'Title is empty.'];
            } elseif (mb_strlen($title) > 255) {
                $rowErrors[] = ['code' => 'title_too_long', 'params' => ['max' => 255, 'got' => mb_strlen($title)],
                    'message' => sprintf('Title is %d characters long (max 255).', mb_strlen($title))];
            }

            $rawDate = $get('date');
            $date = now()->subYear()->format('Y-m-d');
            if ($rawDate !== '') {
                $parsed = \DateTime::createFromFormat('!Y-m-d', $rawDate);
                if (!$parsed || $parsed->format('Y-m-d') !== $rawDate) {
                    $rowErrors[] = ['code' => 'invalid_date', 'params' => ['value' => $rawDate],
                        'message' => sprintf('Date "%s" is not a valid date in YYYY-MM-DD format.', $rawDate)];
                } elseif ($parsed > now()) {
                    $rowErrors[] = ['code' => 'future_date', 'params' => ['value' => $rawDate],
                        'message' => sprintf('Date "%s" is in the future.', $rawDate)];
                } else {
                    $date = $rawDate;
                }
            }

            // Department: department users always import into their own department
            $departmentId = $defaultDept;
            $rawDept = $get('department_id');
            if (!$isDeptUser && $rawDept !== '') {
                if (in_array($rawDept, $deptIds, true)) {
                    $departmentId = (int) $rawDept;
                } elseif (isset($deptByName[mb_strtolower($rawDept)])) {
                    $departmentId = $deptByName[mb_strtolower($rawDept)];
                } else {
                    $departmentId = null;
                    $rowErrors[] = ['code' => 'dept_not_found', 'params' => ['value' => $rawDept],
                        'message' => sprintf('Department "%s" does not exist.', $rawDept)];
                }
            } elseif (!$departmentId) {
                $rowErrors[] = ['code' => 'dept_missing', 'params' => [],
                    'message' => 'No department: the DeptID column is empty and no default department was selected.'];
            }

            if ($rowErrors) {
                $invalidRows++;
                foreach ($rowErrors as $e) {
                    $errorCount++;
                    if (count($errors) < self::MAX_REPORTED_ERRORS) {
                        $errors[] = ['row' => $row['line'], 'title' => mb_substr($title, 0, 80)] + $e;
                    }
                }
                continue;
            }

            $valid[] = [
                'department_id' => $departmentId,
                'date' => $date,
                'version' => [
                    'title' => $title,
                    'tags' => $get('tags'),
                    'problem' => $get('problem'),
                    'solution' => $get('solution'),
                    'objectives' => $get('objectives'),
                    'functions' => $get('functions'),
                    'technologies_used' => $get('technologies'),
                ],
            ];
        }

        if ($errorCount > 0) {
            return response()->json([
                'code' => 'invalid_rows',
                'message' => sprintf('Nothing was imported: %d problem(s) found in the file. Fix them and upload again.', $errorCount),
                'total_rows' => count($rows),
                'error_count' => $errorCount,
                'invalid_rows' => $invalidRows,
                'has_header' => $hasHeader,
                'row_errors' => $errors,
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($valid as $item) {
                $proposal = Proposal::forceCreate([
                    'department_id' => $item['department_id'],
                    'submission_status' => 'archived',
                    'review_status' => 'accepted',
                    'created_at' => $item['date'],
                    'updated_at' => $item['date'],
                ]);

                ProposalVersion::create(['proposal_id' => $proposal->proposal_id, 'version_number' => 1] + $item['version']);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing historical proposals: ' . $e->getMessage());
            return response()->json([
                'code' => 'db_error',
                'message' => 'Database error while saving, nothing was imported: ' . $e->getMessage(),
            ], 500);
        }

        SyncAiCorpus::dispatch();

        return response()->json([
            'code' => 'imported',
            'message' => sprintf('%d proposals imported successfully.', count($valid)),
            'imported' => count($valid),
            'has_header' => $hasHeader,
        ], 200);
    }

    /**
     * Return a column map if the row looks like a header, otherwise null.
     * A row counts as a header when at least two cells are known column names.
     */
    private function detectHeader(array $row): ?array
    {
        $map = [];
        foreach ($row as $index => $cell) {
            $clean = mb_strtolower(trim(preg_replace('/\(.*\)/u', '', (string) $cell)), 'UTF-8');
            foreach (self::HEADER_ALIASES as $key => $aliases) {
                if (!isset($map[$key]) && in_array($clean, $aliases, true)) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        return count($map) >= 2 ? $map : null;
    }

    private function importError(string $code, string $message): JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message], 422);
    }
}
