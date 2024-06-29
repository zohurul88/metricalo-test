<?php

namespace App\Tests\PaymentProvider\Gateways;

use App\PaymentProvider\FailedException;
use App\PaymentProvider\Gateways\Aci;
use App\PaymentProvider\Normalizers\PaymentNormalizer;
use App\Service\ApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AciTest extends TestCase
{
    private $apiClient;
    private $parameterBag;
    private $aci;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $config = [
            'api_url' => 'https://eu-test.oppwa.com/v1',
            'credentials' => [
                'authorization' => 'test_authorization',
                'entityId' => 'test_entity_id'
            ]
        ];

        $this->aci = new Aci($this->apiClient, $config);
    }

    public function testRetrieveCredential()
    {
        $credentials = $this->aci->retrieveCredential();
        $this->assertArrayHasKey('headers', $credentials);
        $this->assertEquals('Bearer test_authorization', $credentials['headers']['authorization']);
    }

    public function testPaySuccess()
    {
        $responseContent = json_encode([
            'result' => [
                'code' => '000.100.110'
            ],
            'id' => 'test_id',
            'timestamp' => '2024-06-28T12:08:02+0000',
            'amount' => 100.0,
            'currency' => 'EUR',
            'card' => [
                'bin' => '411111'
            ]
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseContent);

        $this->apiClient->method('makePostRequest')->willReturn($response);

        $normalizer = $this->aci->pay(100.0, 'EUR', '4111111111111111', '2025', '12', '123');

        $this->assertInstanceOf(PaymentNormalizer::class, $normalizer);
        $this->assertEquals('test_id', $normalizer->transactionID);
        $this->assertEquals(100.0, $normalizer->amount);
        $this->assertEquals('EUR', $normalizer->currency);
    }

    public function testPayFailure()
    {
        $this->expectException(FailedException::class);

        $responseContent = json_encode([
            'result' => [
                'code' => '000.200.000'
            ]
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseContent);

        $this->apiClient->method('makePostRequest')->willReturn($response);

        $this->aci->pay(100.0, 'EUR', '4111111111111111', '2025', '12', '123');
    }
}
