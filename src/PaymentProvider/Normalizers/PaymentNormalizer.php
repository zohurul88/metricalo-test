<?php

namespace App\PaymentProvider\Normalizers;

class PaymentNormalizer implements NormalizerInterface
{
    public string $transactionID;
    public \DateTimeInterface $dateOfCreating;
    public float $amount;
    public string $currency;
    public string $cardBin;

    public function __construct(string $transactionID, \DateTimeInterface $dateOfCreating, float $amount, string $currency, string $cardBin)
    {
        $this->transactionID = $transactionID;
        $this->dateOfCreating = $dateOfCreating;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->cardBin = $cardBin;
    }

    public function normalize(): array
    {
        return [
            'transactionID' => $this->transactionID,
            'dateOfCreating' => $this->dateOfCreating->format(DATE_RFC3339),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'cardBin' => $this->cardBin
        ];
    }

    public function serialize(): string
    {
        return json_encode($this->normalize());
    }
}
