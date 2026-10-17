<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
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
            'file_name' => [
                'required',
                'string',
                'ends_with:.zip',
                function ($attribute, $value, $fail) {
                    if (str_contains($value, '..') || str_starts_with($value, '/') || str_starts_with($value, '\\')) {
                        $fail(__('The backup file path is invalid or contains forbidden traversal characters.'));
                    }
                    if (preg_match('/[<>:"|?*]/', $value)) {
                        $fail(__('The backup file name contains invalid characters.'));
                    }
                },
            ],
            'disk' => ['nullable', 'string', 'in:backups,backup_offsite,local,s3'],
            'confirm_phrase' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (trim((string) $value) !== 'RESTORE') {
                        $fail(__('You must type RESTORE exactly to confirm the restoration.'));
                    }
                },
            ],
        ];
    }
}
