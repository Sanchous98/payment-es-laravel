<?php

namespace PaymentSystem\Laravel;

use DateTimeInterface;
use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Fields\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\UuidInterface;

readonly class Uuid implements AggregateRootId, UuidInterface, Castable
{
    public function __construct(
        private UuidInterface $uuid,
    ) {
    }

    public static function fromBytes(string $aggregateRootId): static
    {
        return new self(\Ramsey\Uuid\Uuid::fromBytes($aggregateRootId));
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function unserialize(string $data): void
    {
        $this->uuid->unserialize($data);
    }

    public function getNumberConverter(): NumberConverterInterface
    {
        return $this->uuid->getNumberConverter();
    }

    public function getFieldsHex(): array
    {
        return $this->uuid->getFieldsHex();
    }

    public function getClockSeqHiAndReservedHex(): string
    {
        return $this->uuid->getClockSeqHiAndReservedHex();
    }

    public function getClockSeqLowHex(): string
    {
        return $this->uuid->getClockSeqLowHex();
    }

    public function getClockSequenceHex(): string
    {
        return $this->uuid->getClockSequenceHex();
    }

    public function getDateTime(): DateTimeInterface
    {
        return $this->uuid->getDateTime();
    }

    public function getLeastSignificantBitsHex(): string
    {
        return $this->uuid->getLeastSignificantBitsHex();
    }

    public function getMostSignificantBitsHex(): string
    {
        return $this->uuid->getMostSignificantBitsHex();
    }

    public function getNodeHex(): string
    {
        return $this->uuid->getNodeHex();
    }

    public function getTimeHiAndVersionHex(): string
    {
        return $this->uuid->getTimeHiAndVersionHex();
    }

    public function getTimeLowHex(): string
    {
        return $this->uuid->getTimeLowHex();
    }

    public function getTimeMidHex(): string
    {
        return $this->uuid->getTimeMidHex();
    }

    public function getTimestampHex(): string
    {
        return $this->uuid->getTimestampHex();
    }

    public function getVariant(): ?int
    {
        return $this->uuid->getVariant();
    }

    public function getVersion(): ?int
    {
        return $this->uuid->getVersion();
    }

    public function compareTo(UuidInterface $other): int
    {
        return $this->uuid->compareTo($other);
    }

    public function equals(?object $other): bool
    {
        return $this->uuid->equals($other);
    }

    public function getBytes(): string
    {
        return $this->uuid->getBytes();
    }

    public function getFields(): FieldsInterface
    {
        return $this->uuid->getFields();
    }

    public function getHex(): Hexadecimal
    {
        return $this->uuid->getHex();
    }

    public function getInteger(): IntegerObject
    {
        return $this->uuid->getInteger();
    }

    public function getUrn(): string
    {
        return $this->uuid->getUrn();
    }

    public function __toString(): string
    {
        return $this->uuid->__toString();
    }

    public function __serialize(): array
    {
        return ['string' => $this->uuid->serialize()];
    }

    public function serialize(): ?string
    {
        return $this->uuid->serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->uuid = Uuid::fromString($data['string']);
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new self(\Ramsey\Uuid\Uuid::fromString($aggregateRootId));
    }

    public function jsonSerialize(): mixed
    {
        return $this->uuid->jsonSerialize();
    }

    /**
     * @return CastsAttributes<self, self>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get(Model $model, string $key, mixed $value, array $attributes): ?Uuid
            {
                if ($value === null) {
                    return null;
                }

                return Uuid::fromString($value);
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                if ($value === null) {
                    return null;
                }

                return (string)$value;
            }
        };
    }
}
