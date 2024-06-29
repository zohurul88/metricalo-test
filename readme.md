# Symfony API and Command Project Documentation

## Overview
This documentation provides a comprehensive guide to setting up a Symfony project with an API and a console command. The project utilizes JSON for data exchange and integrates with payment gateways Shift4 and ACI. This project was created as a technical skill test for a company, showcasing my expertise in Symfony, API integration, Docker, and payment gateway implementation.

## Technologies Used
- **Symfony**: 6.4
- **PHP**: 8.1 (As symfony 6.4 require at least php 8.1)
- **Docker**: with Docker compose

## Project Structure
```
├── config/
│   │── gateway.yaml
│   ├── services.yaml
├── src/
│   ├── Command/
│   │   └── ExampleCommand.php
│   ├── Controller/
│   │   └── ExampleController.php
│   ├── DTO/
│   │   └── DTOInterface.php
│   │   └── PaymentDTO.php
│   │   └── Validator.php
│   │   └── ValidatorException.php
│   ├── EventSubscriber/
│   │   └── ExceptionHandlerSubscriber.php
│   ├── PaymentProvider/
│   │   ├── Gateways/
│   │   │   └── Aci.php
│   │   │   └── Shift4.php
│   │   │   └── ErrorNormalizers/
│   │   │       └── AciErrorNormalizer.php
│   │   │       └── ErrorNormalizerInterface.php
│   │   │       └── Shift4ErrorNormalizer.php
│   │   ├── Normalizers/
│   │   │   └── PaymentNormalizer.php
│   │   │   └── NormalizerInterface.php
│   │   └── AbstractGateway.php
│   │   └── FailedException.php
│   │   └── PaymentFactory.php
│   │   └── PaymentProviderInterface.php
│   └── Service/
│       └── ApiClient.php
│   └── Utility/
│       └── CardUtility.php
│       └── CircuitBreakerCacheAdapter.php
├── tests/
│   └── Controller/
│       └── ExampleControllerTest.php
│   └── PaymentProvider/
│       └── Gateways/
│           └── AciTest.php
│           └── Shift4Test.php
├── .docker/
├── .env
└── docker-compose.yml

```


## Setup with Docker

1. **Install Docker**: Ensure Docker is installed on your system.
2. **Rename .env**:
    ```sh
    mv .env.example .env
    ```
3. **Build and Run Containers**:
    ```sh
    docker-compose up --build
    ```
5. **Access Application**: Open your browser and navigate to the localhost and `APP_PORT` port.

## List of API Endpoints

### Payment API
- **POST /api/payment/{aci|shift4}**
  - **Description**: Process a payment through the specified gateway.
  - **Parameters**: amount, currency, cardNumber, cardExpYear, cardExpMonth, cardCvv

- **CURL Example**: 
```bash
curl --location 'http://localhost:{APP_PORT}/app/example/shift4' \
--header 'Content-Type: application/json' \
--data '{
    "amount": 1,
    "currency": "EUR",
    "cardNumber": "4200000000000000",
    "cardExpYear": "2034",
    "cardExpMonth": "05",
    "cardCvv": "123"
}'
```

## List of Files
- **config/gateway.yaml**: Configuration for payment gateways.
- **src/Command/ExampleCommand.php**: Console command to process payments.
- **src/Controller/ExampleController.php**: Controller handling API requests.
- **src/DTO/**: Data Transfer Objects for validation and processing.
- **src/EventSubscriber/**: Event subscribers for handling global exceptions.
- **src/PaymentProvider/**: Payment provider integrations, error normalizers, and normalizers.
- **src/Service/ApiClient.php**: Service for making external API requests.
- **src/Utility/**: Utility classes.
- **tests/**: Unit and functional tests.

## The Command with Example

### ExampleCommand
- **Description**: Process payment through specified gateway.
- **Usage**:
    ```sh
    php bin/console app:example shift4 --amount=100 --currency=EUR --cardNumber=4111111111111111 --cardExpYear=2025 --cardExpMonth=12 --cardCvv=123
    ```

## How to Add a New Gateway

1. **Create Gateway Class**: Implement the `PaymentProviderInterface` in `src/PaymentProvider/Gateways/`.
2. **Update Configuration**: Add gateway configuration in `config/packages/gateway.yaml`:
    ```yaml
    parameters:
        gateway:
            new_gateway:
                enabled: true
                api_url: "https://api.newgateway.com"
                providerClass: "App\\PaymentProvider\\Gateways\\NewGateway"
                credentials:
                    apiKey: "%env(resolve:NEW_GATEWAY_API_KEY)%"
    ```
4. **Implement Normalizer**: If necessary, create a new normalizer for handling gateway-specific responses.
4. **Implement ErrorNormalizer**: If necessary, create a new ErrorNormalizer for handling gateway-specific all responses.

## PaymentFactory
The `PaymentFactory` class is responsible for creating instances of payment providers. It utilizes the Symfony `ParameterBagInterface` to fetch configuration details for the gateways and the `ApiClient` service to handle API requests.

### Key Methods
- **create(string $gateway)**: This method accepts a gateway name, retrieves its configuration, and returns an instance of the corresponding payment provider class. It ensures the gateway is enabled and correctly configured.

### Usage
When a payment request is made, the factory creates the appropriate gateway instance based on the configuration defined in `gateway.yaml`.

## ApiClient
The `ApiClient` service is responsible for making HTTP requests to external APIs. It leverages Symfony's `HttpClientInterface` for HTTP requests and integrates a circuit breaker pattern to enhance the reliability of API calls.

### Key Features
- **Circuit Breaker Integration**: The client uses a circuit breaker to prevent excessive retries and manage failures gracefully.

## Circuit Breaker
The circuit breaker is implemented using the `CircuitBreakerCacheAdapter`. It helps to avoid repeated API calls when an endpoint is known to be failing, thus improving the resilience of the application.

## About the Tests

### Running Tests
1. **Install PHPUnit**: Ensure PHPUnit is installed.
2. **Run Tests**:
    ```sh
    php bin/phpunit
    ```

### Test Files
- **Controller Tests**: `tests/Controller/ExampleControllerTest.php`
- **Gateway Tests**: `tests/PaymentProvider/Gateways/AciTest.php`, `tests/PaymentProvider/Gateways/Shift4Test.php`

## TODO for Enhancing API Authentication
- **JWT Authentication or API Key Authentication**: Implement JSON Web Token (JWT) or use API keys to manage access and monitor usage.
  - **Package**: Use `lexik/jwt-authentication-bundle` for JWT.
  - **Package**: Use `Symfony\Component\Security\Http\Authenticator\Passport\ApiKeyBadge` for API keys.
- **Rate Limiting**: Implement rate limiting to prevent abuse and ensure fair usage.
  - **Package**: Use `symfony/rate-limiter` for rate limiting.
- **HTTPS**: Always use HTTPS to encrypt data in transit.

## Additional TODO/Recommendations for PCI-DSS Compliance
As this project involves payment gateway integrations, it is crucial to follow PCI-DSS guidelines to ensure the security of cardholder data. Here are some additional recommendations:

- **Encrypt Sensitive Data**: Use strong encryption to protect sensitive data both in transit and at rest.
- **Trancet Sensitive Data**: Use strong encryption to protect sensitive data both in transit and at rest.
- **Secure Access Control**: Implement strict access controls to ensure only authorized personnel can access cardholder data.
- **Regular Security Testing**: Conduct regular security testing, including vulnerability scans and penetration tests, to identify and address security weaknesses.
- **Maintain Audit Trails**: Ensure that all access to cardholder data and network resources is logged and monitored.
- **Use Secure Coding Practices**: Follow secure coding guidelines to prevent common vulnerabilities such as SQL injection, cross-site scripting (XSS), and cross-site request forgery (CSRF).
- **Truncate Card Number and CVC When Logging Data**: Ensure that logs do not contain full card numbers or CVCs. Only log the last four digits of the card number if necessary for troubleshooting.

This documentation covers the setup, usage, and extension of the Symfony project for handling API requests and console commands, focusing on payment processing while ensuring adherence to security standards and PCI-DSS compliance.
