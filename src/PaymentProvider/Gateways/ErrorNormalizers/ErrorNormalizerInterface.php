<?php

namespace App\PaymentProvider\Gateways\ErrorNormalizers;

interface ErrorNormalizerInterface
{
    public function normalizeError(): array;
    public function previousThrowable(): \Throwable;
    public function errorCode(): int;
}
