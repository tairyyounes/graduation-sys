<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Student;
use App\Models\ProjectMember;

class UpdateProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only the owning student can update, and only when it's a draft or a revision has been requested
        $student = optional($this->user()->student);
        if (! $student) return false;
        $proposal = $this->route('proposal');
        if (! $proposal) return false;

        $isOwner = $proposal->students()->where('project_members.student_id', $student->student_id)->exists();
        if (! $isOwner) return false;

        $canUpdate = $proposal->submission_status === 'draft' || $proposal->review_status === 'revision_requested';
        if (! $canUpdate) return false;

        // Enforce update limit (max 3 updates = 4 versions total) before validation runs
        if ($proposal->versions()->count() >= 4) {
            abort(response()->json([
                'message' => 'You can only update your proposal three times.'
            ], 422));
        }

        return true;
    }

    /**
     * Validation rules for updating a proposal (creates a new version).
     */
    public function rules(): array
    {
        $proposal = $this->route('proposal');
        $isDraft = $proposal && $proposal->submission_status === 'draft';

        if ($isDraft) {
            return [
                'title' => [
                    'required',
                    'string',
                    $this->validateWordCount(5, 20, 'proposal title', 'The proposal title must be clear and contain at least 5 words.'),
                    'regex:/^(?![\W_]+$).+$/',
                ],
                'problem' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(30, 250, 'problem statement', 'The problem statement must contain at least 30 words and clearly explain the issue.'),
                ],
                'solution' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(30, 250, 'proposed solution', 'The proposed solution must contain at least 30 words and clearly explain how the system solves the problem.'),
                ],
                'functions' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(20, 200, 'system functions', 'Please describe the main system functions in at least 20 words.'),
                ],
                'objectives' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(20, 200, 'project objectives', 'Please write at least 20 words explaining the project objectives.'),
                ],
                'tags' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (empty($value)) return;
                        $items = array_filter(array_map('trim', explode(',', $value)));
                        $count = count($items);
                        if ($count < 3) {
                            $fail('Please add at least 3 relevant tags.');
                        }
                        if ($count > 10) {
                            $fail('The tags cannot exceed 10 items.');
                        }
                    }
                ],
                'tech' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (empty($value)) return;
                        $items = array_filter(array_map('trim', explode(',', $value)));
                        $count = count($items);
                        if ($count < 2) {
                            $fail('Please add at least 2 technologies that will be used in the project.');
                        }
                        if ($count > 12) {
                            $fail('The technologies cannot exceed 12 items.');
                        }
                    }
                ],
            ];
        }

        return [
            'title' => [
                'required',
                'string',
                $this->validateWordCount(5, 20, 'proposal title', 'The proposal title must be clear and contain at least 5 words.'),
                'regex:/^(?![\W_]+$).+$/',
            ],
            'problem' => [
                'required',
                'string',
                $this->validateWordCount(30, 250, 'problem statement', 'The problem statement must contain at least 30 words and clearly explain the issue.'),
            ],
            'solution' => [
                'required',
                'string',
                $this->validateWordCount(30, 250, 'proposed solution', 'The proposed solution must contain at least 30 words and clearly explain how the system solves the problem.'),
            ],
            'functions' => [
                'required',
                'string',
                $this->validateWordCount(20, 200, 'system functions', 'Please describe the main system functions in at least 20 words.'),
            ],
            'objectives' => [
                'required',
                'string',
                $this->validateWordCount(20, 200, 'project objectives', 'Please write at least 20 words explaining the project objectives.'),
            ],
            'tags' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    $count = count($items);
                    if ($count < 3) {
                        $fail('Please add at least 3 relevant tags.');
                    }
                    if ($count > 10) {
                        $fail('The tags cannot exceed 10 items.');
                    }
                }
            ],
            'tech' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    $count = count($items);
                    if ($count < 2) {
                        $fail('Please add at least 2 technologies that will be used in the project.');
                    }
                    if ($count > 12) {
                        $fail('The technologies cannot exceed 12 items.');
                    }
                }
            ],
        ];
    }

    /**
     * Word count validation helper.
     */
    private function validateWordCount(int $min, int $max, string $fieldName, string $customMinMessage)
    {
        return function ($attribute, $value, $fail) use ($min, $max, $fieldName, $customMinMessage) {
            if (empty($value)) return;
            $trimmed = trim($value);
            $words = empty($trimmed) ? 0 : count(preg_split('/\s+/', $trimmed));
            if ($words < $min) {
                $fail($customMinMessage);
            }
            if ($words > $max) {
                $fail("The {$fieldName} cannot exceed {$max} words.");
            }
        };
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The proposal title is required.',
            'title.string' => 'The proposal title must be a string.',
            'title.regex' => 'The proposal title must not consist only of symbols.',
            'problem.required' => 'The problem statement is required.',
            'problem.string' => 'The problem statement must be a string.',
            'solution.required' => 'The proposed solution is required.',
            'solution.string' => 'The proposed solution must be a string.',
            'functions.required' => 'Please describe the main system functions in at least 20 words.',
            'functions.string' => 'The system functions must be a string.',
            'objectives.required' => 'Please write at least 20 words explaining the project objectives.',
            'objectives.string' => 'The project objectives must be a string.',
            'tags.required' => 'Please add at least 3 relevant tags.',
            'tags.string' => 'The tags must be a string.',
            'tech.required' => 'Please add at least 2 technologies that will be used in the project.',
            'tech.string' => 'The technologies must be a string.',
        ];
    }
}
