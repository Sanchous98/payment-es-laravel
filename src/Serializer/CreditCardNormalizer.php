<?php

namespace PaymentSystem\Laravel\Serializer;

use DateTimeImmutable;
use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\Contracts\TokenizedSourceInterface;
use PaymentSystem\ValueObjects\CreditCard;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreditCardNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): CreditCard
    {
        return new CreditCard(
            new CreditCard\Number($data['first6'], $data['last4'], $data['brand']),
            new CreditCard\Expiration(DateTimeImmutable::createFromFormat('ny', $data['expiration'])),
            new CreditCard\Holder($data['holder']),
            new CreditCard\Cvc(),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, CreditCard::class, true) ||
            is_a($type, SourceInterface::class, true) &&
            $context['type'] === CreditCard::TYPE;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof CreditCard);

        return [
            ...$data->number->jsonSerialize(),
            'holder' => (string)$data->holder,
            'expiration' => (string)$data->expiration,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CreditCard || $data instanceof SourceInterface && $context['type'] === CreditCard::TYPE;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CreditCard::class => false,
            SourceInterface::class => false,
            TokenizedSourceInterface::class => false,
        ];
    }
}