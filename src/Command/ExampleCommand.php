<?php

namespace App\Command;

use App\DTO\PaymentDTO;
use App\DTO\Validator;
use App\DTO\ValidatorException;
use App\PaymentProvider\FailedException;
use App\PaymentProvider\PaymentFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExampleCommand extends Command
{
    protected static $defaultName = 'app:example';

    private Validator $validator;
    private PaymentFactory $paymentFactory;
    private LoggerInterface $logger;

    public function __construct(Validator $validator, PaymentFactory $paymentFactory, LoggerInterface $logger)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->paymentFactory = $paymentFactory;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Process payment for the specified gateway')
            ->addArgument('gateway', InputArgument::REQUIRED, 'The payment gateway (aci|shift4)')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'The amount to be paid')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'The currency of the payment')
            ->addOption('cardNumber', null, InputOption::VALUE_REQUIRED, 'The credit card number')
            ->addOption('cardExpYear', null, InputOption::VALUE_REQUIRED, 'The credit card expiration year')
            ->addOption('cardExpMonth', null, InputOption::VALUE_REQUIRED, 'The credit card expiration month')
            ->addOption('cardCvv', null, InputOption::VALUE_REQUIRED, 'The credit card CVV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $gateway = $input->getArgument('gateway');
        $data = [
            'amount' => $input->getOption('amount'),
            'currency' => $input->getOption('currency'),
            'cardNumber' => $input->getOption('cardNumber'),
            'cardExpYear' => $input->getOption('cardExpYear'),
            'cardExpMonth' => $input->getOption('cardExpMonth'),
            'cardCvv' => $input->getOption('cardCvv')
        ];

        $dto = $this->createPaymentDTO($data);
        $errors = $this->validatePaymentDTO($dto);

        if (!is_null($errors)) {
            $io->error('Validation failed');
            foreach ($errors as $error) {
                $io->error($error);
            }
            return Command::FAILURE;
        }

        try {
            $payment = $this->processPayment($gateway, $dto);
        } catch (FailedException $e) {
            $this->handleFailedException($e, $io);
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->handleGeneralException($e, $io);
            return Command::FAILURE;
        }

        $io->success('Payment successful');
        $io->table(
            ['Transaction ID', 'Date', 'Amount', 'Currency', 'Card Bin'],
            [[$payment->transactionID, $payment->dateOfCreating->format('Y-m-d H:i:s'), $payment->amount, $payment->currency, $payment->cardBin]]
        );

        return Command::SUCCESS;
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

    private function handleFailedException(FailedException $e, SymfonyStyle $io): void
    {
        $this->logger->error('Payment failed', [
            'message' => $e->getMessage(),
            'error' => $e->getError()
        ]);
        $io->error('Payment failed');
        $io->error($e->getError());
    }

    private function handleGeneralException(\Exception $e, SymfonyStyle $io): void
    {
        $this->logger->error('Unexpected error occurred during payment', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $io->error('Internal server error');
    }
}
