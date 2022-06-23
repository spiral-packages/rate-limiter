<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Exceptions;

use Spiral\Http\Exception\ClientException;

class AttemptsExceededException extends ClientException
{
    public function __construct(
        public readonly int $maxAttempts,
        public readonly \DateTimeImmutable $availableAt,
        public readonly \DateInterval $retryAfter,
        string $message = 'Too Many Attempts.',
    ) {
        parent::__construct(429, $message);
    }
}
