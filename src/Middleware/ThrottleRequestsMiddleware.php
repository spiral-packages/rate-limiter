<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\RateLimiter\Exceptions\AttemptsExceededException;
use Spiral\RateLimiter\RateLimiterManager;
use Spiral\RateLimiter\RequestFingerprintInterface;

final class ThrottleRequestsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterManager $manager,
        private readonly RequestFingerprintInterface $fingerprint,
        private readonly ?string $name,
        private readonly array $payload = [],
        private readonly ?int $maxAttempts = null,
        private readonly ?int $decaySeconds = null
    ) {
    }

    /**
     * @throws AttemptsExceededException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signature = $this->fingerprint->getFingerprint($request);

        if ($signature === null) {
            throw new \RuntimeException('Fingerprint can not be resolved for request.');
        }

        $limiter = $this->manager->getRateLimiter(
            name: $this->name ?: 'default',
            payload: \array_merge($this->payload, ['sig' => $signature]),
            maxAttempts: $this->maxAttempts,
            decaySeconds: $this->decaySeconds
        );

        if ($limiter->isAttemptsExceeded()) {
            throw new AttemptsExceededException(
                $limiter->maxAttempts(),
                $limiter->availableAt(),
                $limiter->availableIn()
            );
        }

        $limiter->hit();

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', $limiter->maxAttempts())
            ->withHeader('X-RateLimit-Reset', $limiter->availableAt()->getTimestamp())
            ->withHeader('X-RateLimit-Remaining', $limiter->remainingAttempts());
    }
}
