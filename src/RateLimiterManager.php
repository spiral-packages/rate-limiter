<?php

declare(strict_types=1);

namespace Spiral\RateLimiter;

use Psr\SimpleCache\CacheInterface;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\RateLimiter\Config\RateLimiterConfig;

final class RateLimiterManager
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly RateLimiterConfig $config,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param array<non-empty-string,non-empty-string|int> $payload
     */
    public function getRateLimiter(
        string $name,
        array $payload = [],
        ?int $maxAttempts = null,
        ?int $decaySeconds = null
    ): RateLimiter {
        $decaySeconds ??= $this->config->getDefaultDecaySeconds();

        $key = $name;

        $class = $this->config->getLimiterByAlias($name);

        if ($payload !== []) {
            $key .= ':'.\http_build_query($payload, arg_separator: ':');
        }

        $parameters = [
            'key' => $key,
            'cache' => $this->cache,
        ];

        if ($maxAttempts !== null) {
            $parameters['maxAttempts'] = $maxAttempts;
        }

        if ($decaySeconds !== null) {
            $parameters['decaySeconds'] = $decaySeconds;
        }

        if ($class instanceof Autowire) {
            return $class->resolve($this->factory, $parameters);
        }

        return $this->factory->make($class, $parameters);
    }
}
