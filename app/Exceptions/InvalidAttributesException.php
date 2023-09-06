<?php

namespace App\Exceptions;

use Exception;

class InvalidAttributesException extends Exception
{
    protected string $modelClass;
    protected array $attrs;
    protected array $errors;

    public static function for(string $modelClass, array $attrs, array $errors): self
    {
        $instance = new static('Invalid attributes for model');
        $instance->modelClass = $modelClass;
        $instance->attrs = $attrs;
        $instance->errors = $errors;

        return $instance;
    }

    public function context(): array
    {
        return [
            'modelClass' => $this->modelClass,
            'attributes' => $this->attrs,
            'errors' => $this->errors,
        ];
    }
}
