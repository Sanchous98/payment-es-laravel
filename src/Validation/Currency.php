<?php

namespace PaymentSystem\Laravel\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\Intl\Currencies;

readonly class Currency implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        Currencies::exists($value) || $fail('validation.currency.invalid', $attribute . ' is not a valid currency.');
    }
}