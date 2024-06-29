<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionHandlerSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onExceptions'],
        ];
    }

    public function onExceptions(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {
            $response = new JsonResponse([
                'message' => 'The URL you are looking for is not found.',
                'errors' => [],
            ], Response::HTTP_NOT_FOUND);
            $event->setResponse($response);
            return;
        }

        $isProduction = $_ENV['APP_ENV'] === 'prod';
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $errorRes = [
            'type' => 'exception',
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        if (!$isProduction) {
            $errorRes['file'] = $exception->getFile();
            $errorRes['line'] = $exception->getLine();
            $errorRes['exception'] = get_class($exception);
            $errorRes['trace'] = $exception->getTrace();
        }

        $response = new JsonResponse([
            'message' => 'An error occurred',
            'errors' => [$errorRes],
        ], $statusCode);

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logIt($exception);
        } elseif ($exception instanceof \Exception) {
            $this->logIt($exception);
        }

        $this->logger->info("BAD REQ " . $exception->getMessage(), $exception->getTrace());
        $event->setResponse($response);
    }

    private function logIt(\Throwable $exception): void
    {
        $this->logger->critical($exception->getMessage(), [
            'trace' => $exception->getTraceAsString(),
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'code' => $exception->getCode(),
        ]);
    }
}
