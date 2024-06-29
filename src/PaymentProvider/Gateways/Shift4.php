<?php

namespace App\PaymentProvider\Gateways;

use App\PaymentProvider\AbstractGateway;
use App\PaymentProvider\FailedException;
use App\PaymentProvider\Gateways\ErrorNormalizers\Shift4ErrorNormalizer;
use App\PaymentProvider\Normalizers\NormalizerInterface;
use App\PaymentProvider\Normalizers\PaymentNormalizer;
use App\PaymentProvider\PaymentProviderInterface;

class Shift4 extends AbstractGateway implements PaymentProviderInterface
{
    public function retrieveCredential(): array
    {
        return [
            'auth_basic' => [
                'username' => $this->config['credentials']['secretKey'] ?? "",
                'password' => '',
            ]
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
        $card = $this->createCard($cardNumber, $cardExpYear, $cardExpMonth, $cardCvv);
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'card' => $card,
        ];
        try {
            $response = $this->apiPost('/charges', ['json' => $payload]);
            $res = json_decode($response->getContent());
        } catch (\Exception $e) {
            throw new FailedException(new Shift4ErrorNormalizer($e), "Payment Failed");
        }

        if (!$res->captured) {
            throw new FailedException(new Shift4ErrorNormalizer(new \Exception("Payment Failed - Not Captured")));
        }

        return new PaymentNormalizer(
            $res->id,
            (new \DateTime())->setTimestamp($res->created),
            $res->amount,
            $res->currency,
            $res->card->first6
        );
    }

    protected function createCard(
        string $cardNumber,
        string $cardExpYear,
        string $cardExpMonth,
        string $cardCvv
    ) {
        return [
            'number' => $cardNumber,
            'expYear' => $cardExpYear,
            'expMonth' => $cardExpMonth,
            'cvc' => $cardCvv,
        ];
    }
}
