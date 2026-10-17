<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-backups') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['full', 'db', 'files'])],
        ];
    }

    /**
     * Get validated backup type with fallback.
     */
    public function getBackupType(): string
    {
        return $this->validated('type') ?? 'full';
    }
}
