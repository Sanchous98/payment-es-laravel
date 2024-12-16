<?php

namespace PaymentSystem\Laravel\Serializer;

use PaymentSystem\ValueObjects\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CountryNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Country
    {
        return new Country($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, Country::class, true);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof Country);

        return (string)$data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Country;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Country::class => true,
        ];
    }
}