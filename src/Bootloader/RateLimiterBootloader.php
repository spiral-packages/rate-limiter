<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Config\Patch\Append;
use Spiral\Core\Container;
use Spiral\Config\ConfiguratorInterface;
use Spiral\RateLimiter\Config\RateLimiterConfig;
use Spiral\Console\Bootloader\ConsoleBootloader;

class RateLimiterBootloader extends Bootloader
{
    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(Container $container, ConsoleBootloader $console): void
    {
        $this->initConfig();
    }

    public function registerRateLimiter(string $alias, string|Container\Autowire $class): void
    {
        $this->config->modify(
            RateLimiterConfig::CONFIG,
            new Append('aliases', $alias, $class)
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
