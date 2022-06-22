<?php

declare(strict_types=1);

namespace Spiral\RateLimiter\Fingerprint;

use Psr\Http\Message\RequestInterface;
use Spiral\RateLimiter\RequestFingerprintInterface;

final class CompositeFingerprint implements RequestFingerprintInterface
{
    /** @var RequestFingerprintInterface[] */
    private array $fingerprints;

    public function __construct(RequestFingerprintInterface ...$fingerprints)
    {
        $this->fingerprints = $fingerprints;
    }

    public function getFingerprint(RequestInterface $request): ?string
    {
        foreach ($this->fingerprints as $fingerprint) {
            $fingerprint = $fingerprint->getFingerprint($request);

            if ($fingerprint !== null) {
                return $fingerprint;
            }
        }

        return null;
    }
}
