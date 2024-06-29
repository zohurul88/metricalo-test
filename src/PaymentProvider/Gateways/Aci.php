<?php

namespace App\PaymentProvider\Gateways;

use App\PaymentProvider\AbstractGateway;
use App\PaymentProvider\FailedException;
use App\PaymentProvider\Gateways\ErrorNormalizers\AciErrorNormalizer;
use App\PaymentProvider\Normalizers\NormalizerInterface;
use App\PaymentProvider\Normalizers\PaymentNormalizer;
use App\PaymentProvider\PaymentProviderInterface;
use App\Utility\CardUtility;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class Aci extends AbstractGateway implements PaymentProviderInterface
{
    const PAYMENT_TYPE_DEBIT = 'DB';

    // prod could be different good idea to handle from a central class all type of code 
    const PAYMENT_DEBIT_SUCCESS_CODE = '000.100.110';

    public function retrieveCredential(): array
    {
        return [
            "headers" => [
                'authorization' => 'Bearer ' . ($this->config['credentials']['authorization'] ?? ""),
                'Content-Type'=>'application/x-www-form-urlencoded'
            ],
        ];
    }

    public function pay(
        float $amount,
        string $currency,
        string $cardNumber,
        string $cardExpYear,
        string $cardExpMonth,
        string $cardCvv
    ): NormalizerInterface {
        $cardBrand = CardUtility::cardBrand($cardNumber);
        $payload = [
            'entityId' => $this->config['credentials']['entityId'] ?? "",
            'amount' => $amount,
            'currency' => $currency,
            'paymentBrand' => strtoupper($cardBrand),
            'paymentType' => self::PAYMENT_TYPE_DEBIT,
            'card.number' => $cardNumber,
            'card.holder' => 'Jane Jones',
            'card.expiryMonth' => $cardExpMonth,
            'card.expiryYear' => $cardExpYear,
            'card.cvv' => $cardCvv,
        ];

        try {
            $response = $this->apiPost('/payments', ['body' => $payload]);
            $res = json_decode($response->getContent());
        } catch (\Exception $e) {
            throw new FailedException(new AciErrorNormalizer($e), "Payment Failed");
        }

        if ($res?->result?->code !== self::PAYMENT_DEBIT_SUCCESS_CODE) {
            throw new FailedException(new AciErrorNormalizer(new \Exception("Payment Failed")));
        }

        return new PaymentNormalizer(
            $res->id,
            new \DateTime($res->timestamp),
            $res->amount,
            $res->currency,
            $res->card->bin
        );
    }
}
