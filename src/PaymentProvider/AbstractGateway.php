<?php 
namespace App\PaymentProvider;

use App\PaymentProvider\PaymentProviderInterface;
use App\Service\ApiClient;

abstract class AbstractGateway implements PaymentProviderInterface{
    
    public function __construct(protected ApiClient $apiClient, protected array $config)
    {
        $apiClient->setBaseUrl($this->config['api_url'] ?? "");
    }

    protected function apiPost($path, $payload)
    {
        $options = $this->retrieveCredential();
        return $this->apiClient->makePostRequest($path, $payload, $options);
    }
}