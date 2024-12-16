<?php

namespace PaymentSystem\Laravel\Serializer;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\ClassNameInflector;
use PaymentSystem\Laravel\Uuid;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class AggregateRootIdNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private ClassNameInflector $classInflector,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        assert(isset($data['__type']) && isset($data['__value']));

        $type = $this->classInflector->typeToClassName($data['__type']);
        if (!is_a($type, AggregateRootId::class, true)) {
            throw new NotNormalizableValueException('Unsupported object type.', $data, AggregateRootId::class);
        }

        return $type::fromString($data['__value']);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_a($type, AggregateRootId::class, true);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        assert($data instanceof AggregateRootId);

        return [
            /** @todo avoid hash structure */
            '__type' => $this->classInflector->instanceToType($data),
            '__value' => $data->toString(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AggregateRootId;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AggregateRootId::class => true,
            Uuid::class => true,
        ];
    }
}