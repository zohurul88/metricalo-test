<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ExampleControllerAciTest extends WebTestCase
{
    public function testSuccessfulPayment()
    {
        $client = static::createClient();

        $client->request('POST', '/app/example/aci', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 10,
            'currency' => 'EUR',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]));
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Payment successful', $responseContent['message']);
    }

    public function testValidationError()
    {
        $client = static::createClient();

        $client->request('POST', '/app/example/aci', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 10,
            'currency' => 'EUR',
            'cardNumber' => '',
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Validation failed', $responseContent['message']);
        $this->assertNotEmpty($responseContent['errors']);
    }

    public function testFailedPayment()
    {
        $client = static::createClient();

        $client->request('POST', '/app/example/aci', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'amount' => 100,
            'currency' => 'USD',
            'cardNumber' => '4024007102349866', // Invalid card number to trigger failure
            'cardExpYear' => '2025',
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Payment failed', $responseContent['message']);
        $this->assertNotEmpty($responseContent['errors']);
    }
}
