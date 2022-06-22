<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Fingerprint;

use Psr\Http\Message\RequestInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Core\Container;
use Spiral\RateLimiter\RequestFingerprintInterface;

final class AuthenticatedFingerprint implements RequestFingerprintInterface
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function getFingerprint(RequestInterface $request): ?string
    {
        if ($this->container->has(AuthContextInterface::class)) {
            $auth = $this->container->get(AuthContextInterface::class);

            return \sha1($auth->getToken()->getID());
        }

        return null;
    }
}
