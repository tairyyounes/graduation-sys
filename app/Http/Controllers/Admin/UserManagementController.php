<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\AddingUserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Retrieve a list of all users along with their associated departments and student numbers.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', fn($join) => $join->on('users.email', '=', 'students.official_email')->whereNull('students.deleted_at'))
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.role',
                'users.department_id',
                'users.is_active',
                'departments.department_name',
                'students.student_number',
            ])
            ->orderBy('users.id')
            ->get()
            ->map(fn ($user) => $this->transformUser($user));

        $departments = DB::table('departments')
            ->select(['department_id', 'department_name'])
            ->orderBy('department_name')
            ->get();

        return response()->json([
            'users' => $users,
            'departments' => $departments,
        ]);
    }

    /**
     * Create a new user in the system.
     *
     * @param AddingUserRequest $request
     * @return JsonResponse
     */
    public function store(AddingUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['role'] === 'admin') {
            $validated['department_id'] = null;
        }

        DB::beginTransaction();
        try {
            $user = User::withTrashed()->where('email', $validated['email'])->first();
            if ($user) {
                $user->restore();
                $user->full_name = $validated['full_name'];
                $user->role = $validated['role'];
                $user->department_id = $validated['department_id'];
                $user->is_active = $validated['is_active'];
                $user->password = Hash::make($validated['password']);
                $user->save();
            } else {
                $user = new User();
                $user->full_name = $validated['full_name'];
                $user->email = $validated['email'];
                $user->role = $validated['role'];
                $user->department_id = $validated['department_id'];
                $user->is_active = $validated['is_active'];
                $user->password = Hash::make($validated['password']);
                $user->save();
            }

            if ($validated['role'] === 'student') {
                $student = Student::withTrashed()
                    ->where('official_email', $validated['email'])
                    ->orWhere('student_number', $validated['student_number'])
                    ->first();

                if ($student) {
                    $student->restore();
                    $student->update([
                        'student_number' => $validated['student_number'],
                        'full_name'      => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id'  => $validated['department_id'],
                        'semester'       => $validated['semester'] ?? 8,
                        'is_active'      => $validated['is_active'],
                    ]);
                } else {
                    Student::create([
                        'student_number' => $validated['student_number'],
                        'full_name'      => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id'  => $validated['department_id'],
                        'semester'       => $validated['semester'] ?? 8,
                        'is_active'      => $validated['is_active'],
                    ]);
                }
            } else {
                // If the user was previously registered as a student, soft-delete student record
                Student::where('official_email', $validated['email'])->delete();
            }

            activity()
                ->performedOn($user)
                ->causedBy($request->user())
                ->log("Created user: {$user->full_name} ({$user->role})");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Fetch fresh user to return
        $freshUser = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', fn($join) => $join->on('users.email', '=', 'students.official_email')->whereNull('students.deleted_at'))
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.role',
                'users.department_id',
                'users.is_active',
                'departments.department_name',
                'students.student_number',
            ])
            ->where('users.id', $user->id)
            ->first();

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $this->transformUser($freshUser),
        ], 201);
    }

    /**
     * Update an existing user in the system.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $oldStudent = Student::withTrashed()->where('official_email', $user->email)->first();
        $oldStudentId = $oldStudent ? $oldStudent->student_id : null;

        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\s]+$/u',
            ],
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->role === 'student') {
                        if (!preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                            $fail(__('validation.custom.email.student_format'));
                        }
                    } else if (in_array($request->role, ['department_member', 'department_head']) && !preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                        $fail(__('validation.custom.email.member_format'));
                    }
                }
            ],
            'role' => ['required', Rule::in(['admin', 'student', 'department_member', 'department_head'])],
            'department_id' => [
                Rule::requiredIf(fn() => in_array($request->role, ['student', 'department_member', 'department_head'])), 
                'nullable', 
                'exists:departments,department_id'
            ],
            'student_number' => [
                Rule::requiredIf(fn() => $request->role === 'student'), 
                'nullable', 
                'digits:6', 
                Rule::unique('students', 'student_number')->ignore($oldStudentId, 'student_id')->whereNull('deleted_at')
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:32'],
        ], [
            'full_name.required' => __('validation.custom.full_name.required'),
            'full_name.regex' => __('validation.custom.full_name.regex'),
            'email.required' => __('validation.custom.email.required'),
            'email.email' => __('validation.custom.email.email'),
            'email.unique' => __('validation.custom.email.unique'),
            'department_id.required' => __('validation.custom.department_id.required'),
            'department_id.required_if' => __('validation.custom.department_id.required_if'),
            'department_id.exists' => __('validation.custom.department_id.exists'),
            'student_number.required' => __('validation.custom.student_number.required'),
            'student_number.required_if' => __('validation.custom.student_number.required_if'),
            'student_number.digits' => __('validation.custom.student_number.digits'),
            'student_number.unique' => __('validation.custom.student_number.unique'),
            'password.min' => __('validation.custom.password.min'),
            'password.max' => __('validation.custom.password.max'),
        ]);

        if ($validated['role'] === 'admin') {
            $validated['department_id'] = null;
        }

        DB::beginTransaction();
        try {
            $oldEmail = $user->email;
            $oldRole = $user->role;

            $user->full_name = $validated['full_name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->department_id = $validated['department_id'];
            $user->is_active = $validated['is_active'];
            
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            if ($validated['role'] === 'student') {
                $existingStudent = Student::withTrashed()
                    ->where('official_email', $oldEmail)
                    ->orWhere('student_number', $validated['student_number'])
                    ->first();

                if ($existingStudent) {
                    $existingStudent->restore();
                    $existingStudent->update([
                        'student_number' => $validated['student_number'],
                        'full_name' => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id' => $validated['department_id'],
                        'is_active' => $validated['is_active'],
                    ]);
                } else {
                    Student::create([
                        'student_number' => $validated['student_number'],
                        'full_name' => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id' => $validated['department_id'],
                        'semester' => 8,
                        'is_active' => $validated['is_active'],
                    ]);
                }
            } elseif ($oldRole === 'student' && $validated['role'] !== 'student') {
                // Soft-delete student record if they are no longer a student
                Student::where('official_email', $oldEmail)->delete();
            }

            activity()
                ->performedOn($user)
                ->causedBy($request->user())
                ->log("Updated user: {$user->full_name}");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $freshUser = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', fn($join) => $join->on('users.email', '=', 'students.official_email')->whereNull('students.deleted_at'))
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.role',
                'users.department_id',
                'users.is_active',
                'departments.department_name',
                'students.student_number',
            ])
            ->where('users.id', $user->id)
            ->first();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $this->transformUser($freshUser),
        ]);
    }

    /**
     * Delete a user from the system.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($user->role === 'student') {
                Student::where('official_email', $user->email)->delete();
            }

            $user->is_active = false;
            $user->save();
            $user->delete();

            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log("Deleted user: {$user->full_name}");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * Helper function to normalize user data for the frontend.
     *
     * @param object|null $user
     * @return array
     */
    private function transformUser(?object $user): array
    {
        if (!$user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->full_name ?? '',
            'email' => $user->email,
            'role' => $user->role,
            'departmentId' => $user->department_id,
            'department' => $user->department_name ?? '—', 
            'status' => $user->is_active ? 'Active' : 'Disabled',
            'isActive' => (bool) $user->is_active,
            'studentNumber' => $user->student_number ?? '',
        ];
    }
}
