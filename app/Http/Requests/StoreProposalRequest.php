<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated students can store proposals
        return Auth::check() && optional($this->user()->student)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                $this->validateWordCount(5, 20),
                'regex:/^(?![\W_]+$).+$/', // not only symbols
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
                },
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
                },
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
