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
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->role, ['student', 'department_member', 'department_head']) && !$this->has('department_id')) {
            $this->merge([
                'department_id' => $this->user()?->department_id,
            ]);
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
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if ($this->role === 'student') {
                        if (!preg_match('/^[A-Za-z0-9._%+-]+@cctt\.edu\.ly$/', $value)) {
                            $fail('Student Email Must Be In xxxxxx@cctt.edu.ly format');
                        }
                    }
                }
            ],

            // Department ID
            'department_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => in_array($this->role, ['student', 'department_member', 'department_head'])), 
                'nullable', 
                'exists:departments,department_id'
            ],

            // Student number
            'student_number' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student'),
                'nullable',
                'digits:6',
                'unique:students,student_number'
            ],

            // Semester
            'semester' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student'),
                'nullable',
                'integer'
            ],


            // Status
            'is_active' => ['required', 'boolean'],

            // Password
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}

