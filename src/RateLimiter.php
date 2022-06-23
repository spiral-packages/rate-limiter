<?php

namespace Spiral\RateLimiter;

use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use Spiral\Core\InvokerInterface;
use Spiral\RateLimiter\Exceptions\AttemptsExceededException;

final class RateLimiter
{
    /**
     * @var non-empty-string
     */
    public readonly string $key;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly InvokerInterface $invoker,
        string $key,
        private readonly int $maxAttempts,
        private readonly int $decaySeconds = 60
    ) {
        $this->key = $this->cleanKey($key);
    }

    /**
     * Attempts to execute a callback if it's not limited.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function attempt(\Closure $closure): void
    {
        if ($this->isAttemptsExceeded()) {
            throw new AttemptsExceededException(
                $this->key, $this->maxAttempts, $this->availableIn()
            );
        }

        $this->invoker->invoke($closure);
        $this->hit();
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function hit(): void
    {
        $interval = \DateInterval::createFromDateString(\sprintf('+ %d seconds', $this->decaySeconds));

        $timer = (new \DateTime())->add($interval)->getTimestamp();

        if (! $this->cache->has($this->getTimerCacheKey())) {
            $this->cache->set(
                $this->getTimerCacheKey(),
                $timer,
                $interval,
            );
        }

        $isNew = ! $this->cache->has($this->key);
        if ($isNew) {
            $this->cache->set($this->key, 0, $interval);
        }

        $hits = (int)$this->cache->get($this->key);
        $this->cache->set($this->key, ++$hits);

        if (! $isNew && $hits == 1) {
            $this->cache->set($this->key, 1, $interval);
        }
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function isAttemptsExceeded(): bool
    {
        if ($this->totalAttempts() >= $this->maxAttempts) {
            if ($this->cache->has($this->getTimerCacheKey())) {
                return true;
            }

            $this->resetAttempts();
        }

        return false;
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function totalAttempts(): int
    {
        return (int)$this->cache->get($this->key, 0);
    }

    final public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Get the number of retries left for the given key.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function remainingAttempts(): int
    {
        return $this->maxAttempts - $this->totalAttempts();
    }

    /**
     * Reset the number of attempts for the given key.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function resetAttempts(): void
    {
        $this->cache->delete($this->key);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    final public function clear(): void
    {
        $this->resetAttempts();

        $this->cache->delete($this->getTimerCacheKey());
    }

    /**
     * Get the number of seconds until limiter is accessible again.
     */
    final public function availableIn(): \DateInterval
    {
        $currentTime = (new DateTimeImmutable())->getTimestamp();
        $lastUsedTime = (int)$this->cache->get($this->getTimerCacheKey());

        return \DateInterval::createFromDateString(
            \sprintf('+ %d seconds', \max(0, $lastUsedTime - $currentTime))
        );
    }

    /**
     * Get the date when limiter is accessible again.
     */
    final public function availableAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable(
            (new \DateTime())->add($this->availableIn())
        );
    }

    private function getTimerCacheKey(): string
    {
        return $this->key.':timer';
    }

    /**
     * Clean the rate limiter key from unicode characters.
     */
    private function cleanKey(string $key): string
    {
        return preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($key));
    }
}
