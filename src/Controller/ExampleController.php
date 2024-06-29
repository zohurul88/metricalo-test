<?php 

namespace App\Controller;

use App\DTO\PaymentDTO;
use App\DTO\Validator;
use App\DTO\ValidatorException;
use App\PaymentProvider\FailedException;
use App\PaymentProvider\PaymentFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ExampleController extends AbstractController
{
    private Validator $validator;
    private PaymentFactory $paymentFactory;
    private LoggerInterface $logger;

    public function __construct(Validator $validator, PaymentFactory $paymentFactory, LoggerInterface $logger)
    {
        $this->validator = $validator;
        $this->paymentFactory = $paymentFactory;
        $this->logger = $logger;
    }

    #[Route('/app/example/{gateway}', name: 'app_example', requirements: ['gateway' => PaymentDTO::GATEWAYS], methods: ['POST'])]
    public function index(string $gateway, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->createPaymentDTO($data);
        $errors = $this->validatePaymentDTO($dto);

        if (!is_null($errors)) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $payment = $this->processPayment($gateway, $dto);
        } catch (FailedException $e) {
            return $this->handleFailedException($e);
        } catch (\Exception $e) {
            return $this->handleGeneralException($e);
        }
        return $this->json([
            'message' => "Payment successful",
            'data' => $payment->normalize()
        ], Response::HTTP_OK);
    }

    private function createPaymentDTO(array $data): PaymentDTO
    {
        $dto = new PaymentDTO();
        $dto->amount = $data['amount'] ?? null;
        $dto->currency = $data['currency'] ?? null;
        $dto->cardNumber = $data['cardNumber'] ?? null;
        $dto->cardExpYear = $data['cardExpYear'] ?? null;
        $dto->cardExpMonth = $data['cardExpMonth'] ?? null;
        $dto->cardCvv = $data['cardCvv'] ?? null;

        return $dto;
    }

    private function validatePaymentDTO(PaymentDTO $dto): ?array
    {
        try {
            $this->validator->validate($dto);
            $dto->validateCreditCard();
            return null;
        } catch (ValidatorException $e) {
            return $e->getErrors();
        }
    }

    private function processPayment(string $gateway, PaymentDTO $dto)
    {
        return $this->paymentFactory->create($gateway)->pay(
            $dto->amount,
            $dto->currency,
            $dto->cardNumber,
            $dto->cardExpYear,
            $dto->cardExpMonth,
            $dto->cardCvv
        );
    }

    private function handleFailedException(FailedException $e): JsonResponse
    {
        $this->logger->error('Payment failed', [
            'message' => $e->getMessage(),
            'error' => $e->getError()
        ]);
        return $this->json([
            'message' => 'Payment failed',
            'errors' => [$e->getError()]
        ], Response::HTTP_BAD_REQUEST);
    }

    private function handleGeneralException(\Exception $e): JsonResponse
    {
        $this->logger->error('Unexpected error occurred during payment', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return $this->json([
            'message' => 'Internal server error',
            'errors' => []
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
