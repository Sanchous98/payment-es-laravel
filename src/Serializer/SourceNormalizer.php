<?php

namespace PaymentSystem\Laravel\Serializer;

use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\Contracts\TokenizedSourceInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SourceNormalizer implements NormalizerAwareInterface, NormalizerInterface, DenormalizerAwareInterface, DenormalizerInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!$data instanceof SourceInterface) {
            throw new NotNormalizableValueException('Unsupported object type.', $data, SourceInterface::class);
        }

        return [
            'type' => $data::TYPE,
            $data::TYPE => $this->normalizer->normalize($data, $format, $context + ['type' => $data::TYPE]),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SourceInterface && !isset($context['type']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [SourceInterface::class => false, TokenizedSourceInterface::class => false];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SourceInterface
    {
        $typeKey = $data['type'] ?? null;

        assert($typeKey !== null);

        return $this->denormalizer->denormalize($data[$typeKey] ?? [], $type, $format, $context + ['type' => $typeKey]);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, SourceInterface::class, true) && !isset($context['type']);
    }
}