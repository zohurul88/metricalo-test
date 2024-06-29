<?php

namespace App\Tests\PaymentProvider;

use App\PaymentProvider\PaymentFactory;
use App\Service\ApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentFactoryTest extends TestCase
{
    private $parameterBag;
    private $apiClient;
    private $paymentFactory;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->apiClient = $this->createMock(ApiClient::class);

        $this->paymentFactory = new PaymentFactory($this->parameterBag, $this->apiClient);
    }

    public function testCreatePaymentProvider()
    {
        $config = [
            'shift4' => [
                'enabled' => true,
                'providerClass' => 'App\PaymentProvider\Gateways\Shift4'
            ]
        ];

        $this->parameterBag->method('get')->willReturn($config);

        $provider = $this->paymentFactory->create('shift4');

        $this->assertInstanceOf('App\PaymentProvider\Gateways\Shift4', $provider);
    }

    public function testCreatePaymentProviderDisabled()
    {
        $config = [
            'shift4' => [
                'enabled' => false,
                'providerClass' => 'App\PaymentProvider\Gateways\Shift4'
            ]
        ];

        $this->parameterBag->method('get')->willReturn($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment provider shift4 unavailable right now.');

        $this->paymentFactory->create('shift4');
    }

    public function testCreatePaymentProviderNotConfigured()
    {
        $config = [];

        $this->parameterBag->method('get')->willReturn($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment provider shift4 is not configured.');

        $this->paymentFactory->create('shift4');
    }
}
