<?php

namespace PaymentSystem\Laravel\Serializer;

use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\Country;
use PaymentSystem\ValueObjects\Email;
use PaymentSystem\ValueObjects\PhoneNumber;
use PaymentSystem\ValueObjects\State;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BillingAddressNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new BillingAddress(
            $data['first_name'],
            $data['last_name'],
            $data['city'],
            new Country($data['country']),
            $data['postal_code'],
            new Email($data['email']),
            new PhoneNumber($data['phone']),
            $data['address_line'],
            $data['address_line_extra'] ?? '',
            isset($data['state']) ? new State($data['state']) : null,
        );
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_a($type, BillingAddress::class, true);
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        assert($data instanceof BillingAddress);

        return $data->jsonSerialize();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BillingAddress;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BillingAddress::class => true
        ];
    }
}
