<?php

declare(strict_types=1);

namespace Spiral\RateLimiter;

use Psr\Http\Message\RequestInterface;

interface RequestFingerprintInterface
{
    public function getFingerprint(RequestInterface $request): ?string;
}
