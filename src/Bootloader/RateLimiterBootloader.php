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
use Spiral\RateLimiter\RateLimiterManager;

final class RateLimiterBootloader extends Bootloader
{
    protected const SINGLETONS = [
        RateLimiterManager::class => [self::class, 'initManager'],
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(Container $container, ConsoleBootloader $console): void
    {
        $this->initConfig();
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

    private function initConfig(): void
    {
        $this->config->setDefaults(
            RateLimiterConfig::CONFIG,
            [
                'decaySeconds' => 60,
                'aliases' => [],
            ]
        );
    }
}
