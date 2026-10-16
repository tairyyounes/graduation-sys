<?php

namespace App\Support;

/**
 * Validation for the "academic supervisor" part of the proposal form.
 * Optional while drafting; required for a submitted proposal.
 */
class SupervisorRules
{
    /**
     * @param bool $strict whether the supervisor name is mandatory (submission / revision)
     */
    public static function rules(bool $strict): array
    {
        return [
            'supervisor_name' => [
                $strict ? 'required' : 'nullable',
                'string',
                'max:150',
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'supervisor_name.required' => __('messages.supervisor.name_required'),
            'supervisor_name.max'      => __('messages.supervisor.name_max'),
        ];
    }
}
