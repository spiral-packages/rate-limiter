<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Fingerprint;

use Psr\Http\Message\RequestInterface;
use Spiral\Http\Request\InputManager;
use Spiral\RateLimiter\RequestFingerprintInterface;

final class RequestFingerprint implements RequestFingerprintInterface
{
    public function __construct(
        private readonly InputManager $manager
    ) {
    }

    public function getFingerprint(RequestInterface $request): ?string
    {
        return \sha1($request->getUri()->getHost().$this->manager->remoteAddress());
    }
}
