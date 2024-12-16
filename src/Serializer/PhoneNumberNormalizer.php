<?php

namespace PaymentSystem\Laravel\Serializer;

use PaymentSystem\ValueObjects\PhoneNumber;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PhoneNumberNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PhoneNumber
    {
        return new PhoneNumber($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, PhoneNumber::class, true);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof PhoneNumber);

        return (string)$data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PhoneNumber;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PhoneNumber::class => true,
        ];
    }
}