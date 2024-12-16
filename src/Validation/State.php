<?php

namespace PaymentSystem\Laravel\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

readonly class State implements ValidationRule
{
    public function __construct(private string $country)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new \PaymentSystem\ValueObjects\State(
                $value,
                new \PaymentSystem\ValueObjects\Country($this->country),
            );
        } catch (Throwable) {
            $fail('validation.state.invalid');
        }
    }
}
