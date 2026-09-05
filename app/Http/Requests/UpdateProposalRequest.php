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
                    $this->validateWordCount(5, 20),
                    'regex:/^(?![\W_]+$).+$/',
                ],
                'problem' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(30, 250),
                ],
                'solution' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(30, 250),
                ],
                'functions' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(20, 200),
                ],
                'objectives' => [
                    'nullable',
                    'string',
                    $this->validateWordCount(20, 200),
                ],
                'tags' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (empty($value)) return;
                        $items = array_filter(array_map('trim', explode(',', $value)));
                        $count = count($items);
                        if ($count < 3) {
                            $fail(__('validation.custom.tags.min_items'));
                        }
                        if ($count > 10) {
                            $fail(__('validation.custom.tags.max_items'));
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
                            $fail(__('validation.custom.tech.min_items'));
                        }
                        if ($count > 12) {
                            $fail(__('validation.custom.tech.max_items'));
                        }
                    }
                ],
            ];
        }

        return [
            'title' => [
                'required',
                'string',
                $this->validateWordCount(5, 20),
                'regex:/^(?![\W_]+$).+$/',
            ],
            'problem' => [
                'required',
                'string',
                $this->validateWordCount(30, 250),
            ],
            'solution' => [
                'required',
                'string',
                $this->validateWordCount(30, 250),
            ],
            'functions' => [
                'required',
                'string',
                $this->validateWordCount(20, 200),
            ],
            'objectives' => [
                'required',
                'string',
                $this->validateWordCount(20, 200),
            ],
            'tags' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    $count = count($items);
                    if ($count < 3) {
                        $fail(__('validation.custom.tags.min_items'));
                    }
                    if ($count > 10) {
                        $fail(__('validation.custom.tags.max_items'));
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
                        $fail(__('validation.custom.tech.min_items'));
                    }
                    if ($count > 12) {
                        $fail(__('validation.custom.tech.max_items'));
                    }
                }
            ],
        ];
    }

    /**
     * Word count validation helper.
     */
    private function validateWordCount(int $min, int $max)
    {
        return function ($attribute, $value, $fail) use ($min, $max) {
            if (empty($value)) return;
            $trimmed = trim($value);
            $words = empty($trimmed) ? 0 : count(preg_split('/\s+/', $trimmed));
            if ($words < $min) {
                $fail(__('validation.custom.' . $attribute . '.word_count_min'));
            }
            if ($words > $max) {
                $fail(__('validation.custom.' . $attribute . '.word_count_max'));
            }
        };
    }
}
