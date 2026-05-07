<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudentImportController extends Controller
{
    /**
     * Retrieve a list of students for the currently authenticated department member.
     * This method fetches students that belong specifically to the user's department.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get the department ID attached to the current user's profile
        $departmentId = $request->user()->department_id;

        // If the user doesn't belong to a department, they cannot view students
        if (!$departmentId) {
            return response()->json([
                'students' => [],
                'message' => 'Your account is not linked to a department.',
            ], 422);
        }

        // Fetch students from the database restricted to this specific department
        $students = DB::table('students')
            ->select([
                'student_id',
                'student_number',
                'full_name',
                'official_email',
                'semester',
                'is_active',
            ])
            ->where('department_id', $departmentId)
            ->orderBy('student_id', 'desc')
            ->limit(200) // Prevent fetching thousands of records at once
            ->get();

        return response()->json([
            'students' => $students,
        ]);
    }

    /**
     * Manually add a single student to the department.
     * Also automatically generates a user account for them so they can log in.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if (!$departmentId) {
            return response()->json([
                'message' => 'Your account is not linked to a department.',
            ], 422);
        }

        // Validate the incoming student data
        $validator = Validator::make($request->all(), [
            // Student numbers must be unique across the entire students table
            'student_number' => ['required', 'string', 'max:255', 'unique:students,student_number'],
            'full_name' => ['required', 'string', 'max:255'],
            // Official email must also be unique
            'official_email' => ['required', 'email', 'max:255', 'unique:students,official_email'],
            // Business rule: Student must specifically be in the 8th semester
            'semester' => ['required', 'integer', 'in:8'],
            'is_active' => ['boolean'],
        ], [
            // Custom error message for the semester validation
            'semester.in' => 'The student must be in the 8th semester.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['department_id'] = $departmentId;

        // Insert the student profile into the 'students' table
        DB::table('students')->insert($validated);

        // Additionally, we create a corresponding User account so they can log into the system
        // We use insertOrIgnore to prevent crashes in case an identical email already exists in users table
        DB::table('users')->insertOrIgnore([
            'full_name' => $validated['full_name'],
            'email' => $validated['official_email'],
            // The default login password is set to their student number
            'password' => Hash::make($validated['student_number']),
            'role' => 'student',
            'department_id' => $departmentId,
            'is_active' => $validated['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log this action using Spatie Activitylog since we are using raw DB queries
        activity()
            ->causedBy($request->user())
            ->log('Manually added a student profile: ' . $validated['student_number']);

        return response()->json([
            'message' => 'Student created successfully.',
        ]);
    }

    /**
     * Bulk import students via a CSV or TXT file.
     * Reads the file, validates the data row by row, and creates both Student profiles and User accounts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if (!$departmentId) {
            return response()->json([
                'message' => 'Your account is not linked to a department.',
            ], 422);
        }

        // Validate that a file was actually uploaded, and that it's a CSV or text file under 2MB
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        
        // Open the uploaded file securely in read mode
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json(['message' => 'Unable to read uploaded file.'], 422);
        }

        // Read the very first line of the CSV to get the headers/column names
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'CSV file is empty.'], 422);
        }

        // Normalize the headers (convert to lowercase and trim spaces) to ensure matching
        $normalizedHeader = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            $header
        );

        // Define the minimum required columns the CSV must have
        $requiredColumns = ['student_number', 'full_name', 'official_email', 'semester'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                fclose($handle);
                return response()->json([
                    'message' => "Missing required column: {$column}",
                ], 422);
            }
        }

        // Create a map of column names to their index numbers (e.g., 'full_name' => 1)
        $columnIndexes = array_flip($normalizedHeader);

        $rows = [];
        $rowNumber = 1;

        // Loop through the rest of the CSV line by line
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip completely empty rows
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            // Map the CSV data to an associative array using our known indexes
            $row = [
                'student_number' => trim((string) ($data[$columnIndexes['student_number']] ?? '')),
                'full_name' => trim((string) ($data[$columnIndexes['full_name']] ?? '')),
                'official_email' => trim((string) ($data[$columnIndexes['official_email']] ?? '')),
                'semester' => (int) trim((string) ($data[$columnIndexes['semester']] ?? '')),
                'is_active' => true, // default to active if column is missing
                'department_id' => $departmentId,
            ];

            // If the CSV provides an 'is_active' column, parse its truthy value
            if (array_key_exists('is_active', $columnIndexes)) {
                $isActiveValue = strtolower(trim((string) ($data[$columnIndexes['is_active']] ?? '')));
                $row['is_active'] = in_array($isActiveValue, ['1', 'true', 'yes', 'active'], true);
            }

            // Validate the parsed row data before inserting to ensure database integrity
            $validator = Validator::make($row, [
                'student_number' => ['required', 'string', 'max:255'],
                'full_name' => ['required', 'string', 'max:255'],
                'official_email' => ['required', 'email', 'max:255'],
                'semester' => ['required', 'integer', 'in:8'],
                'department_id' => ['required', 'integer'],
                'is_active' => ['required', Rule::in([true, false])],
            ], [
                'semester.in' => 'The student must be in the 8th semester.',
            ]);

            // If any row fails validation, reject the entire file and inform the user which row failed
            if ($validator->fails()) {
                fclose($handle);
                return response()->json([
                    'message' => "Validation failed at CSV row {$rowNumber}.",
                    'errors' => $validator->errors(),
                ], 422);
            }

            $rows[] = $row;
        }

        fclose($handle); // Always close the file handle when done reading

        if (empty($rows)) {
            return response()->json(['message' => 'No valid rows found in CSV.'], 422);
        }

        // Use insertOrIgnore to batch-insert all students. If a student number already exists, it skips it instead of crashing.
        DB::table('students')->insertOrIgnore($rows);

        // Prepare the payload for batch creating the actual login accounts
        $userRows = [];
        $now = now();
        foreach ($rows as $row) {
            $userRows[] = [
                'full_name' => $row['full_name'],
                'email' => $row['official_email'],
                'password' => Hash::make($row['student_number']), // Default password is the student number
                'role' => 'student',
                'department_id' => $departmentId,
                'is_active' => $row['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Batch insert the login accounts
        DB::table('users')->insertOrIgnore($userRows);

        // Log this bulk action since DB facade operations bypass Eloquent event hooks
        activity()
            ->causedBy($request->user())
            ->log('Imported ' . count($rows) . ' students via CSV');

        return response()->json([
            'message' => 'Students processed successfully.',
            'imported_count' => count($rows),
        ]);
    }
}
