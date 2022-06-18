<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Config;

use Spiral\Core\InjectableConfig;

final class RateLimiterConfig extends InjectableConfig
{
    public const CONFIG = 'rate-limiter';
    protected array $config = [];
}
