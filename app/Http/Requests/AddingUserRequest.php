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
        // Auto-assign department_id when missing for non-admin users (e.g. department head creating a student/member)
        if ($this->user() && $this->user()->role !== 'admin' && empty($this->department_id)) {
            if ($this->user()->department_id) {
                $this->merge(['department_id' => $this->user()->department_id]);
            }
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
                'max:50',
                'regex:/^[\pL\s]+$/u',
                
            ],

            // Role
            'role' => [
                'required',
                \Illuminate\Validation\Rule::in(['admin', 'student', 'department_member', 'department_head']),
            ],
            // email
            'email' => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    if ($this->role === 'student') {
                        if (!preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                            $fail(__('validation.custom.email.student_format'));
                        }
                    } else if ($this->role === 'department_member' && !preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                        $fail(__('validation.custom.email.member_format'));
                    }
                }
            ],

            // Department ID — required for students, department members, and department heads
            'department_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => in_array($this->role, ['student', 'department_member', 'department_head'])),
                'nullable',
                'exists:departments,department_id',
            ],

            // Student number
            'student_number' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student'),
                'nullable',
                'digits:6',
                \Illuminate\Validation\Rule::unique('students', 'student_number')->whereNull('deleted_at'),
            ],
            
            // Semester — optional; controllers may default to 8 if not provided
            'semester' => [
                'nullable',
                'integer',
                'min:1',
                'max:8',
            ],

            // Status
            'is_active' => ['required', 'boolean'],

            // Password
            'password' => ['required', 'string', 'min:8', 'max:32'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'department_id.required' => __('validation.custom.department_id.required'),
            'department_id.required_if' => __('validation.custom.department_id.required_if'),
            'department_id.exists' => __('validation.custom.department_id.exists'),
            'student_number.required' => __('validation.custom.student_number.required'),
            'student_number.required_if' => __('validation.custom.student_number.required_if'),
            'student_number.digits' => __('validation.custom.student_number.digits'),
            'student_number.unique' => __('validation.custom.student_number.unique'),
            'full_name.required' => __('validation.custom.full_name.required'),
            'full_name.regex' => __('validation.custom.full_name.regex'),
            'email.required' => __('validation.custom.email.required'),
            'email.email' => __('validation.custom.email.email'),
            'email.unique' => __('validation.custom.email.unique'),
            'password.required' => __('validation.custom.password.required'),
            'password.min' => __('validation.custom.password.min'),
            'password.max' => __('validation.custom.password.max'),
        ];
    }
}

