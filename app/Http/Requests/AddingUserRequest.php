<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddingUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow admins and department heads (both can create students/users).
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && in_array($user->role, ['admin', 'department_head']);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-assign department_id when missing for student role, regardless of admin status.
        if ($this->has('role') && $this->role === 'student' && !$this->has('department_id')) {
            $deptId = $this->user() ? $this->user()->department_id : null;
            if ($deptId) {
                $this->merge(['department_id' => $deptId]);
            }
        }
        // Also assign for other roles (member, head) when missing.
        if ($this->user() && $this->user()->role !== 'admin' && in_array($this->role, ['department_member', 'department_head']) && !$this->has('department_id')) {
            $this->merge(['department_id' => $this->user()->department_id]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Full name
            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s]+$/u',
                
            ],

            // Role
            'role' => [
                'required',
                \Illuminate\Validation\Rule::in(['admin', 'student', 'department_member', 'department_head']),
            ],
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if ($this->role === 'student') {
                        if (!preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                            $fail('Student Email Must Be In xxxxxx@cctt.edu.ly format');
                        }
                    }
                }
            ],

            // Department ID — required for students unless the admin will assign it later
            'department_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student' && ($this->user()?->role ?? null) !== 'admin'),
                'nullable',
                'exists:departments,department_id',
            ],

            // Student number
            'student_number' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student'),
                'nullable',
                'digits:6',
                \Illuminate\Validation\Rule::unique('students', 'student_number')->whereNull('deleted_at'),
            ],            // Semester — optional; controllers may default to 8 if not provided
            'semester' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],


            // Status
            'is_active' => ['required', 'boolean'],

            // Password
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}

