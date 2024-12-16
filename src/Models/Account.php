<?php

namespace PaymentSystem\Laravel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ramsey\Uuid\UuidInterface;

/**
 * @property-read UuidInterface $id
 * @property string|null $external_id
 * @property string $description
 * @property Model $credentials
 * @property array $supported_currencies
 */
class Account extends Model
{
    use HasUuids;

    protected $fillable = ['external_id', 'description'];

    protected $casts = [
        'supported_currencies' => 'array',
    ];

    public function credentials(): MorphTo
    {
        return $this->morphTo();
    }
}