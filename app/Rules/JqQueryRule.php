<?php

namespace App\Rules;

use App\Enums\JqBuilderFieldEnum;
use App\Enums\JqBuilderOperatorEnum;
use App\Enums\JqBuilderTypeEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JqQueryRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The rules item must be a valid rule.');
        }

//        if (empty($value['id']) || !in_array($value['id'], JqBuilderFieldEnum::toLabels())) {
//            $fail('The rule id must be a valid id.');
//        }

        if (empty($value['field']) || !in_array($value['field'], JqBuilderFieldEnum::toLabels())) {
            $fail('The rule field must be a valid field.');
        }

        if (empty($value['operator']) || !in_array($value['operator'], JqBuilderOperatorEnum::toLabels())) {
            $fail('The rule operator must be a valid operator.');
        }

        if (!isset($value['value'])) {
            $fail('The rule value must be a valid value.');
        }

        if ($field = JqBuilderFieldEnum::tryFrom($value['field'])) {
            $jqRule = $field->toJqRule();

            if ($op = JqBuilderOperatorEnum::tryFrom($value['operator'] ?? '')) {
                if (!in_array($op->label, $jqRule['operators'], true)) {
                    $fail('The rule operator must be a valid operator.');
                }
            }

            if ($type = JqBuilderTypeEnum::tryFrom($jqRule['type'] ?? '')) {
                match ($type->label) {
                    JqBuilderTypeEnum::integer()->label,
                    JqBuilderTypeEnum::double()->label => $this->validateNumber($value, $fail),

                    JqBuilderTypeEnum::string()->label,
                    JqBuilderTypeEnum::date()->label,
                    JqBuilderTypeEnum::time()->label,
                    JqBuilderTypeEnum::datetime()->label => $this->validateString($value, $fail),

                    JqBuilderTypeEnum::boolean()->label => $this->validateBoolean($value, $fail),

                    default => $fail('The rule type must be a valid type.'),
                };
            }
        }

//        $jqBuilderInputs = [
//            'text',
//            'number',
//            'textarea',
//            'radio',
//            'checkbox',
//            'select',
//        ];
//        if (empty($value['input']) || !in_array($value['input'], $jqBuilderInputs, true)) {
//            $fail('The rule input must be a valid input.');
//        }
    }

    private function validateNumber(array $value, Closure $fail): void
    {
        if (!is_numeric($value['value'])) {
            $fail('The rule value must be a valid number.');
        }
    }

    private function validateString(array $value, Closure $fail): void
    {
        if (!is_string($value['value'])) {
            $fail('The rule value must be a valid string.');
        }
    }

    private function validateBoolean(array $value, Closure $fail): void
    {
        if (!is_bool($value['value'])) {
            $fail('The rule value must be a valid boolean.');
        }
    }
}
