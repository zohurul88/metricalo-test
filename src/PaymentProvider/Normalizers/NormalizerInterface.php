<?php

namespace App\PaymentProvider\Normalizers;

interface NormalizerInterface
{
    public function normalize(): array;
    public function serialize(): string;
}
