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
     * @param \App\Http\Requests\AddingUserRequest $request
     * @return JsonResponse
     */
    public function store(\App\Http\Requests\AddingUserRequest $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if (!$departmentId) {
            return response()->json([
                'message' => 'Your account is not linked to a department.',
            ], 422);
        }

        $validated = $request->validated();
        $validated['department_id'] = $departmentId;

        // Ensure we handle email correctly for the students table
        $studentData = [
            'student_number' => $validated['student_number'],
            'full_name' => $validated['full_name'],
            'official_email' => $validated['email'],
            'semester' => $validated['semester'],
            'department_id' => $departmentId,
            'is_active' => $validated['is_active'] ?? true,
        ];

        DB::beginTransaction();
        try {
            DB::table('students')->insert($studentData);

            DB::table('users')->insertOrIgnore([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'student',
                'department_id' => $departmentId,
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            activity()
                ->causedBy($request->user())
                ->log('Manually added a student profile: ' . $validated['student_number']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save student.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Student created successfully.',
        ]);
    }

    /**
     * Download an empty CSV template with required columns.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ];

        $columns = ['student_number', 'full_name', 'email', 'semester', 'is_active'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk parse students via a CSV or TXT file.
     * Reads the file and returns a staged array of students, marking those that already exist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if (!$departmentId) {
            return response()->json(['message' => 'Your account is not linked to a department.'], 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json(['message' => 'Unable to read uploaded file.'], 422);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'CSV file is empty.'], 422);
        }

        $normalizedHeader = array_map(fn ($value) => strtolower(trim((string) $value)), $header);

        // We mapped official_email to email in the template
        $requiredColumns = ['student_number', 'full_name', 'email', 'semester'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $normalizedHeader, true) && !($column === 'email' && in_array('official_email', $normalizedHeader, true))) {
                fclose($handle);
                return response()->json([
                    'message' => "Missing required column: {$column}",
                ], 422);
            }
        }

        $columnIndexes = array_flip($normalizedHeader);
        $emailIndex = $columnIndexes['email'] ?? $columnIndexes['official_email'];

        $rows = [];
        $emailsToCheck = [];
        $studentNumbersToCheck = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $email = trim((string) ($data[$emailIndex] ?? ''));
            $studentNumber = trim((string) ($data[$columnIndexes['student_number']] ?? ''));

            $row = [
                'student_number' => $studentNumber,
                'full_name' => trim((string) ($data[$columnIndexes['full_name']] ?? '')),
                'email' => $email,
                'semester' => (int) trim((string) ($data[$columnIndexes['semester']] ?? '')),
                'is_active' => true,
                'exists' => false, // Will be updated
            ];

            if (array_key_exists('is_active', $columnIndexes)) {
                $isActiveValue = strtolower(trim((string) ($data[$columnIndexes['is_active']] ?? '')));
                $row['is_active'] = in_array($isActiveValue, ['1', 'true', 'yes', 'active'], true);
            }

            $rows[] = $row;
            if ($email) $emailsToCheck[] = $email;
            if ($studentNumber) $studentNumbersToCheck[] = $studentNumber;
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['message' => 'No valid rows found in CSV.'], 422);
        }

        // Check for existing users/students in bulk
        $existingEmails = DB::table('users')->whereIn('email', $emailsToCheck)->pluck('email')->toArray();
        $existingNumbers = DB::table('students')->whereIn('student_number', $studentNumbersToCheck)->pluck('student_number')->toArray();

        foreach ($rows as &$row) {
            if (in_array($row['email'], $existingEmails) || in_array($row['student_number'], $existingNumbers)) {
                $row['exists'] = true;
            }
        }

        return response()->json([
            'message' => 'CSV parsed successfully.',
            'staged_students' => $rows,
        ]);
    }

    /**
     * Confirm and bulk insert the staged students.
     * Uses AddingUserRequest logic via manual validation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmImport(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;
        if (!$departmentId) {
            return response()->json(['message' => 'Your account is not linked to a department.'], 422);
        }

        $students = $request->input('students', []);
        
        // We will validate using the rules from AddingUserRequest
        $rules = (new \App\Http\Requests\AddingUserRequest())->rules();
        // Adjust rules for batch processing
        $studentRules = [
            'students' => ['required', 'array'],
            'students.*.student_number' => $rules['student_number'],
            'students.*.full_name' => $rules['full_name'],
            'students.*.email' => $rules['email'],
            'students.*.semester' => $rules['semester'],
            'students.*.is_active' => $rules['is_active'],
        ];

        // Ensure we pass the required role for validation closures to work properly
        foreach ($students as &$student) {
            $student['role'] = 'student';
            $student['password'] = $student['student_number'] ?? 'password'; // Password required by AddingUserRequest
        }
        $request->merge(['students' => $students]);

        // Manually instantiate the request with the data so the closure logic in AddingUserRequest works
        $addingRequest = new \App\Http\Requests\AddingUserRequest();
        $addingRequest->merge(['role' => 'student']);

        // Since the array validation is complex, we will validate row by row using the validator
        $validStudents = [];
        $errors = [];

        foreach ($students as $index => $student) {
            $validator = Validator::make($student, $rules);
            if ($validator->fails()) {
                $errors["row_$index"] = $validator->errors();
            } else {
                $validStudents[] = $student;
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'message' => 'Validation failed for some students.',
                'errors' => $errors,
            ], 422);
        }

        if (empty($validStudents)) {
            return response()->json(['message' => 'No students to import.'], 422);
        }

        $studentRows = [];
        $userRows = [];
        $now = now();

        foreach ($validStudents as $student) {
            $studentRows[] = [
                'student_number' => $student['student_number'],
                'full_name' => $student['full_name'],
                'official_email' => $student['email'],
                'department_id' => $departmentId,
                'semester' => $student['semester'],
                'is_active' => $student['is_active'],
            ];

            $userRows[] = [
                'full_name' => $student['full_name'],
                'email' => $student['email'],
                'password' => Hash::make($student['password']),
                'role' => 'student',
                'department_id' => $departmentId,
                'is_active' => $student['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::beginTransaction();
        try {
            DB::table('students')->insertOrIgnore($studentRows);
            DB::table('users')->insertOrIgnore($userRows);

            activity()
                ->causedBy($request->user())
                ->log('Confirmed import of ' . count($studentRows) . ' students via CSV');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save students.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Students imported successfully.',
            'imported_count' => count($studentRows),
        ]);
    }
}
