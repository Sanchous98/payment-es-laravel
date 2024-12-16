<?php

namespace PaymentSystem\Laravel\Serializer;

use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class SymfonyPayloadSerializer implements PayloadSerializer
{
    public function __construct(
        private NormalizerInterface&DenormalizerInterface $serializer,
    ) {
    }

    public function serializePayload(object $event): array
    {
        return $this->serializer->normalize($event);
    }

    public function unserializePayload(string $className, array $payload): object
    {
        return $this->serializer->denormalize($payload, $className);
    }
}