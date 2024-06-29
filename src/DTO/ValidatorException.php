<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Response;

class ValidatorException extends \InvalidArgumentException
{

    public function __construct(
        public $errors = [],
        $message = 'Validation error',
        $code = Response::HTTP_BAD_REQUEST,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
