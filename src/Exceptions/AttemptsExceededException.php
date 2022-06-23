<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Exceptions;

class AttemptsExceededException extends RateLimiterException
{
    public function __construct(
        public readonly int $maxAttempts,
        public readonly \DateTimeImmutable $availableAt,
        public readonly \DateInterval $retryAfter,
        string $message = 'Too Many Attempts.',
    ) {
        parent::__construct($message, 429);
    }
}
