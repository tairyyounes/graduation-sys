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
            ->whereNull('deleted_at')
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
        $callerRole = $request->user()->role;

        // Department heads use their own department; admins must supply department_id in the payload.
        if ($callerRole === 'department_head') {
            $departmentId = $request->user()->department_id;
        } else {
            // admin path — department_id comes from the form payload (already validated as exists:departments)
            $departmentId = $request->input('department_id');
        }

        if (!$departmentId) {
            return response()->json([
                'message' => 'A department must be specified for students.',
            ], 422);
        }

        $validated = $request->validated();
        $validated['department_id'] = $departmentId;

        // Ensure we handle email correctly for the students table
        $studentData = [
            'student_number' => $validated['student_number'],
            'full_name'      => $validated['full_name'],
            'official_email' => $validated['email'],
            'semester'       => $validated['semester'] ?? 8,
            'department_id'  => $departmentId,
            'is_active'      => $validated['is_active'] ?? true,
        ];

        DB::beginTransaction();
        try {
            $student = \App\Models\Student::withTrashed()
                ->where('official_email', $validated['email'])
                ->orWhere('student_number', $validated['student_number'])
                ->first();

            if ($student) {
                $student->restore();
                $student->update($studentData);
            } else {
                \App\Models\Student::create($studentData);
            }

            $user = \App\Models\User::withTrashed()
                ->where('email', $validated['email'])
                ->first();

            if ($user) {
                $user->restore();
                $user->update([
                    'full_name'   => $validated['full_name'],
                    'password'    => Hash::make($validated['password']),
                    'role'        => 'student',
                    'department_id' => $departmentId,
                    'is_active'   => $validated['is_active'] ?? true,
                ]);
            } else {
                \App\Models\User::create([
                    'full_name'   => $validated['full_name'],
                    'email'       => $validated['email'],
                    'password'    => Hash::make($validated['password']),
                    'role'        => 'student',
                    'department_id' => $departmentId,
                    'is_active'   => $validated['is_active'] ?? true,
                ]);
            }

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

        $columns = ['student_number', 'full_name', 'email', 'semester', 'password', 'is_active'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Column header aliases for CSV import (English & Arabic).
     */
    private const STUDENT_HEADER_ALIASES = [
        'student_number' => ['student_number', 'student_id', 'student number', 'student no', 'student_no', 'std_no', 'رقم القيد', 'رقم_القيد', 'القيد', 'رقم قيد الطالب', 'رقم الطالب', 'رقم_طالب', 'قيد'],
        'full_name'      => ['full_name', 'name', 'student_name', 'student name', 'الاسم', 'اسم الطالب', 'الاسم الكامل', 'اسم_الطالب', 'الاسم_الرباعي', 'الاسم الثلاثي', 'اسم الطالب ثلاثي', 'اسم الطالب رباعي', 'اسم'],
        'email'          => ['email', 'official_email', 'student_email', 'البريد', 'البريد الالكتروني', 'البريد_الالكتروني', 'الإيميل', 'الايميل'],
        'semester'       => ['semester', 'term', 'الفصل', 'الفصل الدراسي', 'الفصل_الدراسي', 'السمستر', 'المستوى', 'السداسي'],
        'password'       => ['password', 'pass', 'كلمة المرور', 'كلمة_المرور', 'الرمز السري', 'الباسوورد', 'باسوورد', 'الرمز', 'رمز المرور'],
        'is_active'      => ['is_active', 'status', 'active', 'الحالة', 'مفعل', 'نشط'],
    ];

    /**
     * Bulk parse students via a CSV or TXT file.
     * Reads the file and returns a staged array of students, marking those that already exist.
     */
    public function import(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if (!$departmentId) {
            return response()->json(['message' => 'Your account is not linked to a department.'], 422);
        }

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

        $file = $request->file('file');
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false || trim($raw) === '') {
            return response()->json(['message' => 'The uploaded file is empty or unreadable.'], 422);
        }

        // Strip UTF-8 BOM
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        // Check UTF-16
        if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
        } elseif (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
        }

        // Detect delimiter: comma, semicolon, tab
        $firstLine = strtok($raw, "\r\n");
        $delimiter = ',';
        if ($firstLine !== false) {
            $semicolons = substr_count($firstLine, ';');
            $commas = substr_count($firstLine, ',');
            $tabs = substr_count($firstLine, "\t");
            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }

        // Parse lines
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);

        $firstRow = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!$firstRow) {
            fclose($handle);
            return response()->json(['message' => 'CSV file is empty.'], 422);
        }

        // Match headers
        $mappedIndexes = [];
        $isHeaderRow = false;

        foreach ($firstRow as $idx => $cell) {
            $cleaned = strtolower(trim((string)$cell));
            $cleaned = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/u', '', $cleaned);
            $cleaned = trim($cleaned);

            foreach (self::STUDENT_HEADER_ALIASES as $key => $aliases) {
                if (in_array($cleaned, $aliases, true)) {
                    $mappedIndexes[$key] = $idx;
                    $isHeaderRow = true;
                    break;
                }
            }
        }

        $rows = [];
        $emailsToCheck = [];
        $studentNumbersToCheck = [];

        // If first line wasn't header, rewind to include it as a data row
        if (!$isHeaderRow) {
            rewind($handle);
            // Default positional mapping: 0 => student_number, 1 => full_name, 2 => email, 3 => semester, 4 => password
            $mappedIndexes = [
                'student_number' => 0,
                'full_name'      => 1,
                'email'          => 2,
                'semester'       => 3,
                'password'       => 4,
            ];
        }

        while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($data === [null] || implode('', array_map('trim', $data)) === '') {
                continue;
            }

            $studentNumber = isset($mappedIndexes['student_number']) ? trim((string)($data[$mappedIndexes['student_number']] ?? '')) : '';
            $fullName = isset($mappedIndexes['full_name']) ? trim((string)($data[$mappedIndexes['full_name']] ?? '')) : '';
            
            // Clean student number (keep digits only if formatted)
            $studentNumber = preg_replace('/[^\d]/', '', $studentNumber);

            if ($studentNumber === '' && $fullName === '') {
                continue;
            }

            $email = isset($mappedIndexes['email']) ? strtolower(trim((string)($data[$mappedIndexes['email']] ?? ''))) : '';
            if ($email === '' && $studentNumber !== '') {
                $email = "{$studentNumber}@cctt.edu.ly";
            }

            $semester = isset($mappedIndexes['semester']) ? (int)trim((string)($data[$mappedIndexes['semester']] ?? '')) : 8;
            if ($semester < 1 || $semester > 8) {
                $semester = 8;
            }

            $password = isset($mappedIndexes['password']) ? trim((string)($data[$mappedIndexes['password']] ?? '')) : '';
            if ($password === '') {
                $password = $studentNumber;
            }

            $isActive = true;
            if (isset($mappedIndexes['is_active'])) {
                $isActiveVal = strtolower(trim((string)($data[$mappedIndexes['is_active']] ?? '')));
                if (in_array($isActiveVal, ['0', 'false', 'no', 'inactive', 'معطل', 'غير مفعل'], true)) {
                    $isActive = false;
                }
            }

            $row = [
                'student_number' => $studentNumber,
                'full_name'      => $fullName,
                'email'          => $email,
                'semester'       => $semester,
                'password'       => $password,
                'is_active'      => $isActive,
                'exists'         => false,
            ];

            $rows[] = $row;
            if ($email !== '') $emailsToCheck[] = $email;
            if ($studentNumber !== '') $studentNumbersToCheck[] = $studentNumber;
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json(['message' => 'No valid student rows found in the CSV file.'], 422);
        }

        // Check for existing users/students in bulk (excluding soft-deleted)
        $existingEmails = DB::table('users')->whereNull('deleted_at')->whereIn('email', $emailsToCheck)->pluck('email')->toArray();
        $existingNumbers = DB::table('students')->whereNull('deleted_at')->whereIn('student_number', $studentNumbersToCheck)->pluck('student_number')->toArray();

        foreach ($rows as &$row) {
            if (in_array($row['email'], $existingEmails, true) || in_array($row['student_number'], $existingNumbers, true)) {
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
     */
    public function confirmImport(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;
        if (!$departmentId) {
            return response()->json(['message' => 'Your account is not linked to a department.'], 422);
        }

        $students = $request->input('students', []);
        if (empty($students)) {
            return response()->json(['message' => 'No students to import.'], 422);
        }

        $validStudents = [];
        $errors = [];

        foreach ($students as $index => $student) {
            $studentNumber = preg_replace('/[^\d]/', '', trim((string)($student['student_number'] ?? '')));
            $fullName = trim((string)($student['full_name'] ?? ''));
            $email = strtolower(trim((string)($student['email'] ?? '')));
            $semester = (int)($student['semester'] ?? 8);
            if ($semester < 1 || $semester > 8) $semester = 8;
            $password = trim((string)($student['password'] ?? ''));
            if ($password === '') {
                $password = $studentNumber;
            }
            $isActive = (bool)($student['is_active'] ?? true);

            if ($email === '' && $studentNumber !== '') {
                $email = "{$studentNumber}@cctt.edu.ly";
            }

            $rowErrors = [];
            if ($studentNumber === '' || strlen($studentNumber) !== 6) {
                $rowErrors[] = 'Student number must be exactly 6 digits.';
            }
            if ($fullName === '') {
                $rowErrors[] = 'Full name is required.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Valid email is required.';
            }
            if (strlen($password) < 6) {
                $rowErrors[] = 'Password must be at least 6 characters.';
            }

            if (!empty($rowErrors)) {
                $errors["row_$index"] = $rowErrors;
            } else {
                $validStudents[] = [
                    'student_number' => $studentNumber,
                    'full_name'      => $fullName,
                    'email'          => $email,
                    'semester'       => $semester,
                    'password'       => $password,
                    'is_active'      => $isActive,
                ];
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'message' => 'Validation failed for some students.',
                'errors' => $errors,
            ], 422);
        }

        if (empty($validStudents)) {
            return response()->json(['message' => 'No valid students to import.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($validStudents as $student) {
                $studentData = [
                    'student_number' => $student['student_number'],
                    'full_name'      => $student['full_name'],
                    'official_email' => $student['email'],
                    'department_id'  => $departmentId,
                    'semester'       => $student['semester'],
                    'is_active'      => $student['is_active'],
                ];

                $existStudent = \App\Models\Student::withTrashed()
                    ->where('official_email', $student['email'])
                    ->orWhere('student_number', $student['student_number'])
                    ->first();

                if ($existStudent) {
                    $existStudent->restore();
                    $existStudent->update($studentData);
                } else {
                    \App\Models\Student::create($studentData);
                }

                $existUser = \App\Models\User::withTrashed()
                    ->where('email', $student['email'])
                    ->first();

                if ($existUser) {
                    $existUser->restore();
                    $existUser->update([
                        'full_name'     => $student['full_name'],
                        'password'      => Hash::make($student['password']),
                        'role'          => 'student',
                        'department_id' => $departmentId,
                        'is_active'     => $student['is_active'],
                    ]);
                } else {
                    \App\Models\User::create([
                        'full_name'     => $student['full_name'],
                        'email'         => $student['email'],
                        'password'      => Hash::make($student['password']),
                        'role'          => 'student',
                        'department_id' => $departmentId,
                        'is_active'     => $student['is_active'],
                    ]);
                }
            }

            activity()
                ->causedBy($request->user())
                ->log('Confirmed import of ' . count($validStudents) . ' students via CSV');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save students.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Students imported successfully.',
            'imported_count' => count($validStudents),
        ]);
    }

    /**
     * Update a student and their corresponding user account.
     */
    public function update(Request $request, $studentId): JsonResponse
    {
        $departmentId = $request->user()->department_id;
        
        $student = DB::table('students')
            ->where('student_id', $studentId)
            ->where('department_id', $departmentId)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $validated = $request->validate([
            'student_number' => ['required', 'digits:6', Rule::unique('students', 'student_number')->ignore($student->student_id, 'student_id')],
            'full_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->official_email, 'email')],
            'semester' => ['nullable', 'integer'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::beginTransaction();
        try {
            DB::table('students')
                ->where('student_id', $studentId)
                ->update([
                    'student_number' => $validated['student_number'],
                    'full_name' => $validated['full_name'],
                    'official_email' => $validated['email'],
                    'semester' => $validated['semester'],
                    'is_active' => $validated['is_active'],
                ]);

            $userData = [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            // Update user associated with old official_email
            DB::table('users')
                ->where('email', $student->official_email)
                ->where('role', 'student')
                ->update($userData);

            activity()
                ->causedBy($request->user())
                ->log('Updated student profile: ' . $validated['student_number']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update student.', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Student updated successfully.']);
    }

    /**
     * Delete a student and their corresponding user account.
     */
    public function destroy(Request $request, $studentId): JsonResponse
    {
        $departmentId = $request->user()->department_id;
        
        $student = DB::table('students')
            ->where('student_id', $studentId)
            ->where('department_id', $departmentId)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('users')
                ->where('email', $student->official_email)
                ->where('role', 'student')
                ->delete();

            DB::table('students')
                ->where('student_id', $studentId)
                ->delete();

            activity()
                ->causedBy($request->user())
                ->log('Deleted student profile: ' . $student->student_number);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete student.', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
