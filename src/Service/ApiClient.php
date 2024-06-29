<?php

namespace App\Service;

use App\Utility\CircuitBreakerCacheAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use LeoCarmo\CircuitBreaker\CircuitBreaker; 
use LeoCarmo\CircuitBreaker\Adapters\AdapterInterface;
use Symfony\Component\HttpClient\HttpClient;

class ApiClient
{ 
    protected HttpClientInterface $client;
    protected CircuitBreaker $circuitBreaker;
    protected string $baseurl;

    /**
     * ApiClient constructor.
     *
     * @param HttpClientInterface $client
     * @param AdapterInterface $adapter
     * @param ParameterBagInterface $params
     */
    public function __construct(
        protected ParameterBagInterface $params,
        protected CircuitBreakerCacheAdapter $adapter
    ) {
        $this->client = HttpClient::create();
    }

    /**
     * Set the base URL for API requests.
     *
     * @param string $baseurl
     * @return $this
     */
    public function setBaseUrl(string $baseurl): self
    {
        $this->baseurl = $baseurl;
        return $this;
    }

    /**
     * Make a POST request to the given path with the provided payload and options.
     *
     * @param string $path
     * @param array $payload
     * @param array $options
     * @return ResponseInterface
     * @throws \Exception
     */
    public function makePostRequest(
        string $path,
        array $payload,
        array $options = []
    ): ResponseInterface {
        $url = sprintf('%s/%s', rtrim($this->baseurl, '/'), ltrim($path, '/'));
        $this->circuitBreaker = new CircuitBreaker($this->adapter, md5($url));
        if ($this->circuitBreaker->isAvailable()) {
            try {
                $options = [
                    ...$payload,
                    ...$options,
                ];
                $response = $this->client->request('POST', $url, $options);

                if ($response->getStatusCode() >= 500) {
                    $this->circuitBreaker->failure();
                } else {
                    $this->circuitBreaker->success();
                }

                return $response;
            } catch (\Exception $e) {
                $this->circuitBreaker->failure();
                throw $e;
            }
        } else {
            throw new \Exception('Service unavailable due to circuit breaker');
        }
    }
}
