<?php

namespace App\Utility;

use Freelancehunt\Validators\CreditCard;

class CardUtility extends CreditCard
{
    public static function maskCardNumber(string $cardNumber): string
    {
        $stars = implode("", array_fill(0, strlen($cardNumber) - 10, "*"));
        return substr($cardNumber, 0, 6) . $stars . substr($cardNumber, -4);
    }

    public static function cardBrand(string $cardNumber): string
    {
        return self::creditCardType($cardNumber);
    }
}
