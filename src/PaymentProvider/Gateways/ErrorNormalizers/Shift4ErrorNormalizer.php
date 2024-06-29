<?php

namespace App\PaymentProvider\Gateways\ErrorNormalizers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Shift4ErrorNormalizer implements ErrorNormalizerInterface
{
    protected int $errorCode = Response::HTTP_INTERNAL_SERVER_ERROR;
    public function __construct(private \Throwable $exception){
    }
    public function normalizeError(): array
    {
        $error = [
            'type' => 'unknown_error',
            'code' => 'unknown',
            'message' => $this->exception->getMessage(),
        ];

        if ($this->exception instanceof ClientExceptionInterface) { 
            $content = $this->exception->getResponse()->toArray(false);
            $this->errorCode= Response::HTTP_BAD_REQUEST;
            if (isset($content['error'])) {
                $error = [
                    'type' => $content['error']['type'] ?? 'client_error',
                    'code' => $content['error']['code'] ?? 'client_error',
                    'message' => $content['error']['message'] ?? $this->exception->getMessage(),
                ];
            } else {
                $error['message'] = 'Client error occurred';
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
