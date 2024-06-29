<?php

namespace App\Utility;

use LeoCarmo\CircuitBreaker\Adapters\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CircuitBreakerCacheAdapter implements AdapterInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function isOpen(string $service): bool
    {
        return $this->cache->get($service . '_open', function (ItemInterface $item) {
            return false;
        });
    }

    public function reachRateLimit(string $service, int $failureRateThreshold): bool
    {
        $failures = $this->getFailuresCounter($service);
        return $failures >= $failureRateThreshold;
    }

    public function setOpenCircuit(string $service, int $timeWindow): void
    {
        $this->cache->get($service . '_open', function (ItemInterface $item) use ($timeWindow) {
            $item->expiresAfter($timeWindow);
            return true;
        });
    }

    public function setHalfOpenCircuit(string $service, int $timeWindow, int $intervalToHalfOpen): void
    {
        $this->cache->get($service . '_half_open', function (ItemInterface $item) use ($timeWindow, $intervalToHalfOpen) {
            $item->expiresAfter($timeWindow + $intervalToHalfOpen);
            return true;
        });
    }

    public function isHalfOpen(string $service): bool
    {
        return $this->cache->get($service . '_half_open', function (ItemInterface $item) {
            return false;
        });
    }

    public function incrementFailure(string $service, int $timeWindow): bool
    {
        $failures = $this->cache->get($service . '_failures', function (ItemInterface $item) use ($timeWindow) {
            $item->expiresAfter($timeWindow);
            return 0;
        });

        $failures++;
        $this->cache->get($service . '_failures', function (ItemInterface $item) use ($failures, $timeWindow) {
            $item->expiresAfter($timeWindow);
            return $failures;
        });

        return true;
    }

    public function setSuccess(string $service): void
    {
        $this->cache->delete($service . '_open');
        $this->cache->delete($service . '_failures');
        $this->cache->delete($service . '_half_open');
    }

    public function getFailuresCounter(string $service): int
    {
        return $this->cache->get($service . '_failures', function (ItemInterface $item) {
            return 0;
        });
    }
}
