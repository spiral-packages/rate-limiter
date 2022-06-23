<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Cache\CacheStorageProviderInterface;
use Spiral\Config\Patch\Append;
use Spiral\Core\Container;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\FactoryInterface;
use Spiral\RateLimiter\Config\RateLimiterConfig;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\RateLimiter\Fingerprint\AuthenticatedFingerprint;
use Spiral\RateLimiter\Fingerprint\CompositeFingerprint;
use Spiral\RateLimiter\Fingerprint\RequestFingerprint;
use Spiral\RateLimiter\RateLimiterManager;
use Spiral\RateLimiter\RequestFingerprintInterface;

final class RateLimiterBootloader extends Bootloader
{
    protected const SINGLETONS = [
        RateLimiterManager::class => [self::class, 'initManager'],
        RequestFingerprintInterface::class => [self::class, 'initFingerprint'],
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(): void
    {
        $this->initConfig();
    }

    public function boot(Container $container, RateLimiterConfig $config)
    {
        foreach ($config->getLimiterAliases() as $alias => $limiter) {
            $container->bindSingleton(
                'rate-limiter:'.$alias,
                static fn(FactoryInterface $factory) => $factory->make($limiter)
            );
        }
    }

    final public function registerRateLimiter(string $alias, string|Container\Autowire $class): void
    {
        $this->config->modify(
            RateLimiterConfig::CONFIG,
            new Append('aliases', $alias, $class)
        );
    }

    private function initManager(
        FactoryInterface $factory,
        RateLimiterConfig $config,
        CacheStorageProviderInterface $provider
    ): RateLimiterManager {
        return new RateLimiterManager(
            $factory,
            $config,
            $provider->storage($config->getCacheStorage())
        );
    }

    private function initFingerprint(
        AuthenticatedFingerprint $authFingerprint,
        RequestFingerprint $requestFingerprint
    ): RequestFingerprintInterface {
        return new CompositeFingerprint($authFingerprint, $requestFingerprint);
    }

    private function initConfig(): void
    {
        $this->config->setDefaults(
            RateLimiterConfig::CONFIG,
            [
                'default' => [
                    'decay_seconds' => 60,
                    'max_attempts' => 100,
                ],
                'aliases' => [],
            ]
        );
    }
}
