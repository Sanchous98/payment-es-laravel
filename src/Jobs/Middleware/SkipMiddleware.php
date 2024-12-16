<?php

namespace PaymentSystem\Laravel\Jobs\Middleware;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

readonly class SkipMiddleware
{
    private SerializableClosure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = new SerializableClosure($closure);
    }

    public function __invoke(object $job, Closure $next)
    {
        if (call_user_func($this->closure, $job)) {
            return false;
        }

        return $next($job);
    }
}
