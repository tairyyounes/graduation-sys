<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Retrieve a list of all users along with their associated departments.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', 'users.email', '=', 'students.official_email')
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
            $user = new User();
            $user->full_name = $validated['full_name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->department_id = $validated['department_id'];
            $user->is_active = $validated['is_active'];
            $user->password = Hash::make($validated['password']);
            $user->save();

            if ($validated['role'] === 'student') {
                DB::table('students')->insert([
                    'student_number' => $validated['student_number'],
                    'full_name' => $validated['full_name'],
                    'official_email' => $validated['email'],
                    'department_id' => $validated['department_id'],
                    'semester' => 8, // Default requirement
                    'is_active' => $validated['is_active'],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Fetch fresh user to return
        $freshUser = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', 'users.email', '=', 'students.official_email')
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
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'student', 'department_member'])],
            'department_id' => [
                Rule::requiredIf(fn() => $request->role === 'student' || $request->role === 'department_member'), 
                'nullable', 
                'exists:departments,department_id'
            ],
            'student_number' => [
                Rule::requiredIf(fn() => $request->role === 'student'), 
                'nullable', 
                'string', 
                'max:255', 
                Rule::unique('students', 'student_number')->where(function ($query) use ($user) {
                    return $query->where('official_email', '!=', $user->email);
                })
            ],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
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
                $existingStudent = DB::table('students')->where('official_email', $oldEmail)->first();
                if ($existingStudent) {
                    DB::table('students')->where('official_email', $oldEmail)->update([
                        'student_number' => $validated['student_number'],
                        'full_name' => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id' => $validated['department_id'],
                        'is_active' => $validated['is_active'],
                    ]);
                } else {
                    DB::table('students')->insert([
                        'student_number' => $validated['student_number'],
                        'full_name' => $validated['full_name'],
                        'official_email' => $validated['email'],
                        'department_id' => $validated['department_id'],
                        'semester' => 8,
                        'is_active' => $validated['is_active'],
                    ]);
                }
            } elseif ($oldRole === 'student' && $validated['role'] !== 'student') {
                // Remove student record if they are no longer a student
                DB::table('students')->where('official_email', $oldEmail)->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $freshUser = User::query()
            ->leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->leftJoin('students', 'users.email', '=', 'students.official_email')
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
    DB::beginTransaction();
    try {
        if ($user->role === 'student') {
            DB::table('students')->where('official_email', $user->email)->update([
                'deleted_at' => now(),
                'is_active' => false,
            ]);
        }

        $user->is_active = false;
        $user->save();
        $user->delete(); // now soft deletes (sets deleted_at) instead of hard delete

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
     * @param object $user
     * @return array
     */
    private function transformUser(object $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
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
