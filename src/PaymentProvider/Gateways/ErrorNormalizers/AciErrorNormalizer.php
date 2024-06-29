<?php

namespace App\PaymentProvider\Gateways\ErrorNormalizers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AciErrorNormalizer implements ErrorNormalizerInterface
{
    protected int $errorCode = Response::HTTP_INTERNAL_SERVER_ERROR;
    public function __construct(private \Throwable $exception)
    {
    }
    public function normalizeError(): array
    {
        $error = [
            'type' => 'unknown_error',
            'code' => 'unknown',
            'message' => 'An unknown error occurred.',
        ];
        if ($this->exception instanceof ClientExceptionInterface) {
            $response = $this->exception->getResponse()->toArray(false);

            if (isset($response['result']['code'])) {
                $error['code'] = $response['result']['code'];
                $error['message'] = $response['result']['description'] ?? 'An error occurred.';

                if (isset($response['result']['parameterErrors'])) {
                    $error['details'] = [];
                    foreach ($response['result']['parameterErrors'] as $paramError) {
                        $error['details'][] = [
                            'parameter' => $paramError['name'] ?? 'unknown',
                            'value' => $paramError['value'] ?? 'unknown',
                            'message' => $paramError['message'] ?? 'unknown error',
                        ];
                    }
                }
            }
        } elseif ($this->exception instanceof ServerExceptionInterface) {
            $error = [
                'type' => 'server_error',
                'code' => 'server_error',
                'message' => 'Server error occurred',
            ];
        } elseif ($this->exception instanceof TransportExceptionInterface) {
            $error = [
                'type' => 'network_error',
                'code' => 'network_error',
                'message' => 'Network error occurred',
            ];
        }
        return $error;
    }

    public function previousThrowable(): \Throwable
    {
        return $this->exception;
    }

    public function errorCode(): int
    {
        return $this->errorCode;
    }
}
