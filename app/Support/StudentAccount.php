<?php

namespace App\Support;

/**
 * Rules tying a student's login account to their registration number (رقم القيد):
 * the email is always "<student_number>@cctt.edu.ly" and the initial password is the number itself.
 */
class StudentAccount
{
    public const EMAIL_DOMAIN = 'cctt.edu.ly';

    public static function emailFor(string $studentNumber): string
    {
        return $studentNumber . '@' . self::EMAIL_DOMAIN;
    }

    /** The student number embedded in an official email, or null if the email is not in the student format. */
    public static function numberFromEmail(string $email): ?string
    {
        return preg_match('/^(\d+)@cctt\.edu\.ly$/i', trim($email), $m) ? $m[1] : null;
    }

    public static function emailMatchesNumber(string $email, string $studentNumber): bool
    {
        return strtolower(trim($email)) === self::emailFor($studentNumber);
    }

    /**
     * Validation closure for an email field: it must equal "<student_number>@cctt.edu.ly".
     * Skipped while the student number itself is invalid, since that field reports its own error.
     */
    public static function emailMatchRule(?string $studentNumber): \Closure
    {
        return function ($attribute, $value, $fail) use ($studentNumber) {
            if ($studentNumber === null || !preg_match('/^\d{6}$/', $studentNumber)) {
                return;
            }
            if (!self::emailMatchesNumber((string) $value, $studentNumber)) {
                $fail(__('validation.custom.email.student_number_mismatch'));
            }
        };
    }
}
