<?php

namespace App\Rules;

use App\Services\SegmentBuilderService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JqQueryGroup implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (SegmentBuilderService::isGroup($value)) {
            if (empty($value['condition']) || !in_array(strtoupper($value['condition']), ['AND', 'OR'])) {
                $fail('The :attribute must be a valid condition.');
            }

            if (empty($value['rules']) || !is_array($value['rules'])) {
                $fail('The :attribute must be a valid rules.');
            }

            foreach ($value['rules'] as $rule) {
                $this->validate($attribute, $rule, $fail);
            }
        }

        if (SegmentBuilderService::isRule($value)) {
            (new JqQueryRule)->validate($attribute, $value, $fail);
        }
    }
}
