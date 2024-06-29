<?php

namespace App\PaymentProvider;

use App\PaymentProvider\Normalizers\NormalizerInterface;

interface PaymentProviderInterface
{
    public function pay(
        float $amount,
        string $currency,
        string $cardNumber,
        string $cardExpYear,
        string $cardExpMonth,
        string $cardCvv
    ): NormalizerInterface;

    public function retrieveCredential(): array;
}
