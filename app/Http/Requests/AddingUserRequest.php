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
                \Illuminate\Validation\Rule::in(['admin', 'student', 'department_member']),
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
                \Illuminate\Validation\Rule::requiredIf(fn() => $this->role === 'student' || $this->role === 'department_member'), 
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


            // Status
            'is_active' => ['required', 'boolean'],

            // Password
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}

