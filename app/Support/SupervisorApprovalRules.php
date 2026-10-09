<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Validation for the "academic supervisor" part of the proposal form.
 * Optional while drafting; required for a submitted proposal.
 */
class SupervisorApprovalRules
{
    public const ACCEPTED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const MAX_KB = 5120; // 5 MB

    /**
     * @param bool $strict      whether the fields are mandatory (submission / revision)
     * @param bool $hasExisting whether the proposal already has an approval document stored
     * @param bool $removing    whether the student asked to remove the stored document
     */
    public static function rules(bool $strict, bool $hasExisting = false, bool $removing = false): array
    {
        return [
            'supervisor_name' => [
                $strict ? 'required' : 'nullable',
                'string',
                'max:150',
            ],
            'supervisor_approval' => [
                Rule::requiredIf($strict && (!$hasExisting || $removing)),
                'nullable',
                'file',
                'extensions:' . implode(',', self::ACCEPTED_EXTENSIONS),
                'mimes:' . implode(',', self::ACCEPTED_EXTENSIONS),
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:' . self::MAX_KB,
            ],
            'remove_supervisor_approval' => ['sometimes', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'supervisor_name.required'       => __('messages.supervisor.name_required'),
            'supervisor_name.max'            => __('messages.supervisor.name_max'),
            'supervisor_approval.required'   => __('messages.supervisor.approval_required'),
            'supervisor_approval.file'       => __('messages.supervisor.approval_file'),
            'supervisor_approval.uploaded'   => __('messages.supervisor.approval_max'),
            'supervisor_approval.extensions' => __('messages.supervisor.approval_mimes'),
            'supervisor_approval.mimes'      => __('messages.supervisor.approval_mimes'),
            'supervisor_approval.mimetypes'  => __('messages.supervisor.approval_mimes'),
            'supervisor_approval.max'        => __('messages.supervisor.approval_max'),
        ];
    }
}
