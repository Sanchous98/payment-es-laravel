<?php

namespace PaymentSystem\Laravel\Serializer;

use PaymentSystem\ValueObjects\Email;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EmailNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Email
    {
        return new Email($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, Email::class, true);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        assert($data instanceof Email);

        return (string)$data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Email;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Email::class => true,
        ];
    }
}