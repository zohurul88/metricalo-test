<?php

namespace App\PaymentProvider;

use App\Service\ApiClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentFactory
{
    public function __construct(private ParameterBagInterface $parameterBag, private ApiClient $apiClient)
    {
    }

    public function create(string $gateway): PaymentProviderInterface
    {
        $config = $this->parameterBag->get("gateway");
        $gateway = strtolower($gateway);
        if (!isset($config[$gateway])) {
            throw new \InvalidArgumentException("Payment provider {$gateway} is not configured.");
        }

        /**
         * It might be possible to check from a database 
         * configuration to handle this without deploying new code.
         */
        if ($config[$gateway]['enabled'] !== true) {
            throw new \InvalidArgumentException("Payment provider {$gateway} unavailable right now.");
        }

        $gatewayClass = $config[$gateway]['providerClass'];
        if (!class_exists($gatewayClass) || !class_implements($gatewayClass, PaymentProviderInterface::class)) {
            throw new \InvalidArgumentException("Invalid payment provider: {$gateway}");
        }

        return new $gatewayClass($this->apiClient, $config[$gateway]);
    }
}
