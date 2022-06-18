<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Config\ConfiguratorInterface;
use Spiral\RateLimiter\Commands;
use Spiral\RateLimiter\Config\RateLimiterConfig;
use Spiral\Console\Bootloader\ConsoleBootloader;

class RateLimiterBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [];
    protected const DEPENDENCIES = [
        ConsoleBootloader::class
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(Container $container, ConsoleBootloader $console): void
    {
        $this->initConfig();

        $console->addCommand(Commands\RateLimiterCommand::class);
    }

    public function boot(Container $container): void
    {
    }

    private function initConfig(): void
    {
        $this->config->setDefaults(
            RateLimiterConfig::CONFIG,
            []
        );
    }
}
