<?php

namespace App\Tests\PaymentProvider\Gateways;

use App\PaymentProvider\FailedException;
use App\PaymentProvider\Gateways\Shift4;
use App\PaymentProvider\Normalizers\PaymentNormalizer;
use App\Service\ApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Shift4Test extends TestCase
{
    private $apiClient;
    private $parameterBag;
    private $shift4;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $config = [
            'api_url' => 'https://api.shift4.com',
            'credentials' => [
                'secretKey' => 'test_secret_key'
            ]
        ];

        $this->shift4 = new Shift4($this->apiClient, $config);
    }

    public function testRetrieveCredential()
    {
        $credentials = $this->shift4->retrieveCredential();
        $this->assertArrayHasKey('auth_basic', $credentials);
        $this->assertEquals('test_secret_key', $credentials['auth_basic']['username']);
    }

    public function testPaySuccess()
    {
        $responseContent = json_encode([
            'captured' => true,
            'id' => 'test_id',
            'created' => time(),
            'amount' => 100.0,
            'currency' => 'USD',
            'card' => [
                'first6' => '411111'
            ]
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseContent);

        $this->apiClient->method('makePostRequest')->willReturn($response);

        $normalizer = $this->shift4->pay(100.0, 'USD', '4111111111111111', '2025', '12', '123');

        $this->assertInstanceOf(PaymentNormalizer::class, $normalizer);
        $this->assertEquals('test_id', $normalizer->transactionID);
        $this->assertEquals(100.0, $normalizer->amount);
        $this->assertEquals('USD', $normalizer->currency);
    }

    public function testPayFailure()
    {
        $this->expectException(FailedException::class);

        $responseContent = json_encode([
            'captured' => false,
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseContent);

        $this->apiClient->method('makePostRequest')->willReturn($response);

        $this->shift4->pay(100.0, 'USD', '4111111111111111', '2025', '12', '123');
    }
}
