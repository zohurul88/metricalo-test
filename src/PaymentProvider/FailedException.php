<?php

namespace App\PaymentProvider;

use App\PaymentProvider\Gateways\ErrorNormalizers\ErrorNormalizerInterface;

class FailedException extends \Exception
{
    protected array $error;
    public function __construct(ErrorNormalizerInterface $errorNormalizer, string|null $message = null)
    {
        $this->error = $errorNormalizer->normalizeError();
        parent::__construct($message ?? $this->error['message'], $errorNormalizer->errorCode(), $errorNormalizer->previousThrowable());
    }

    public function getError(): array
    {
        return $this->error;
    }

    public function getErrorJson(): string
    {
        return json_encode($this->error);
    }
}
