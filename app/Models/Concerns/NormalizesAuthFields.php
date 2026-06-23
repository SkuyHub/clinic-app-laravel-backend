<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Hash;

trait NormalizesAuthFields
{
    public static function normalizePasswordOnUpdate(array $input): array
    {
        if (array_key_exists('password', $input)) {
            if (blank($input['password'])) {
                unset($input['password']);
            } else {
                $input['password'] = Hash::make($input['password']);
            }
        }

        return $input;
    }

    public static function normalizeBooleanField(array $input, string $field, ?bool $defaultIfMissing = null): array
    {
        if (array_key_exists($field, $input)) {
            $input[$field] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        } elseif ($defaultIfMissing !== null) {
            $input[$field] = $defaultIfMissing;
        }

        return $input;
    }
}