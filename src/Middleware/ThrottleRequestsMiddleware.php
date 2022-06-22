<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Core\Container;
use Spiral\Http\Request\InputManager;
use Spiral\RateLimiter\RateLimiter;
use Spiral\RateLimiter\RateLimiterManager;

final class ThrottleRequestsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterManager $manager,
        private readonly Container $container,
        private readonly ?string $name,
        private readonly array $payload = [],
        private readonly ?int $maxAttempts = null,
        private readonly ?int $decaySeconds = null
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $limiter = $this->manager->getRateLimiter(
            name: $this->name,
            payload: \array_merge($this->payload, ['sig' => $this->resolveRequestSignature($request)]),
            maxAttempts: $this->maxAttempts,
            decaySeconds: $this->decaySeconds
        );

        if ($limiter->isAttemptsExceeded()) {
            return $this->buildResponse($limiter);
        }

        $limiter->hit();

        return $handler->handle($request)
            ->withHeader('X-RateLimit-Limit', $limiter->totalAttempts())
            ->withHeader('X-RateLimit-Remaining', $limiter->remainingAttempts());
    }

    private function buildResponse(RateLimiter $limiter): ResponseInterface
    {
        $factory = $this->container->get(ResponseFactoryInterface::class);

        return $factory->createResponse(429, 'Too Many Attempts.')
            ->withHeader('X-RateLimit-Limit', $limiter->totalAttempts())
            ->withHeader('X-RateLimit-Reset', (new \DateTime())->add($limiter->availableIn())->getTimestamp())
            ->withHeader('Retry-After', $limiter->availableIn()->s);
    }

    /**
     * Resolve request signature.
     */
    private function resolveRequestSignature(ServerRequestInterface $request): string
    {
        if ($this->container->has(AuthContextInterface::class)) {
            $auth = $this->container->get(AuthContextInterface::class);

            return \sha1($auth->getToken()->getID());
        }

        $input = $this->container->get(InputManager::class);

        return \sha1($request->getUri()->getHost() . $input->remoteAddress());
    }
}
