<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DepartmentMemberManagementController extends Controller
{
    /**
     * Retrieve a list of all department members in the same department.
     */
    public function index(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $members = User::query()
            ->where('department_id', $departmentId)
            ->where('role', 'department_member')
            ->select([
                'id',
                'full_name',
                'email',
                'role',
                'department_id',
                'is_active',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn ($user) => $this->transformUser($user));

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Create a new department member in the system.
     */
    public function store(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        DB::beginTransaction();
        try {
            $user = new User();
            $user->full_name = $validated['full_name'];
            $user->email = $validated['email'];
            $user->role = 'department_member';
            $user->department_id = $departmentId;
            $user->is_active = $validated['is_active'];
            $user->password = Hash::make($validated['password']);
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'Department member created successfully.',
            'member' => $this->transformUser($user),
        ], 201);
    }

    /**
     * Update an existing department member.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if ($user->department_id !== $departmentId || $user->role !== 'department_member') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::beginTransaction();
        try {
            $user->full_name = $validated['full_name'];
            $user->email = $validated['email'];
            $user->is_active = $validated['is_active'];
            
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'message' => 'Department member updated successfully.',
            'member' => $this->transformUser($user),
        ]);
    }

    /**
     * Delete a department member from the system.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        if ($user->department_id !== $departmentId || $user->role !== 'department_member') {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();
        try {
            $user->is_active = false;
            $user->save();
            $user->delete(); // Soft delete

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'Department member deleted successfully.']);
    }

    /**
     * Helper function to normalize user data for the frontend.
     */
    private function transformUser(object $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->full_name ?? $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'departmentId' => $user->department_id,
            'isActive' => (bool) $user->is_active,
            'status' => $user->is_active ? 'Active' : 'Disabled',
        ];
    }
}
