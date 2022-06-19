<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\RateLimiter\RateLimiter;

final class RateLimiterConfig extends InjectableConfig
{
    public const CONFIG = 'rate-limiter';
    protected array $config = [
        'decaySeconds' => 60,
        'aliases' => [],
    ];

    /**
     * @return class-string<RateLimiter>|Autowire
     */
    public function getLimiterByAlias(string $alias): string|Autowire
    {
        if (isset($this->config['aliases'][$alias])) {
            return $this->config['aliases'][$alias];
        }

        return RateLimiter::class;
    }

    public function getDefaultDecaySeconds(): int
    {
        return $this->config['decaySeconds'] ?? 60;
    }
}