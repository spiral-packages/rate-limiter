<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Commands;

use Spiral\Console\Command;

class RateLimiterCommand extends Command
{
    protected const SIGNATURE = 'rate-limiter {argument : Argument description} {--o|option : Option description}';
    protected const DESCRIPTION = 'My command';
    protected const ARGUMENTS = [];

    public function perform(): int
    {
        return self::SUCCESS;
    }
}
