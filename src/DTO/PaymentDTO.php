<?php

namespace App\DTO;

use App\Utility\CardUtility; 
use Symfony\Component\Validator\Constraints as Assert;

class PaymentDTO implements DTOInterface
{
    const GATEWAYS = "aci|shift4";

    #[Assert\NotBlank(null, 'The amount filed is required')]
    #[Assert\Positive(null, 'The amount filed is garter than zero')]
    public $amount;

    #[Assert\NotBlank(null, 'The currency is required')]
    #[Assert\Currency]
    public $currency;

    #[Assert\NotBlank(null, 'The card number is required')]
    public $cardNumber;

    #[Assert\NotBlank(null, 'The card expiry year is required')]
    #[Assert\Range(
        min: 2023,
        max: 2099,
        notInRangeMessage: "The expiration year must be between {{ min }} and {{ max }}."
    )]
    public $cardExpYear;

    #[Assert\NotBlank(null, 'The card expiry month is required')]
    #[Assert\Range(
        min: 1,
        max: 12,
        notInRangeMessage: "The expiration month must be between {{ min }} and {{ max }}."
    )]
    public $cardExpMonth;

    #[Assert\NotBlank(null, 'The card cvv is required')]
    public $cardCvv;

    public function validateCreditCard(): void
    {
        $cardValidator = CardUtility::validCreditCard($this->cardNumber);
        $errorMessages = [];
        if (!$cardValidator['valid']) {
            $errorMessages[] = [
                'type' => 'validation_error',
                'code' => 'cardNumber',
                'message' => 'Invalid credit card number.'
            ];
        }

        $cvcValidator = CardUtility::validCvc($this->cardCvv, $cardValidator['type']);
        if (!$cvcValidator) {
            $errorMessages[] = [
                'type' => 'validation_error',
                'code' => 'cardCvv',
                'message' => 'Invalid CVC.'
            ];
        }
        if (count($errorMessages) > 0) {
            throw new ValidatorException($errorMessages);
        }
    }
}
