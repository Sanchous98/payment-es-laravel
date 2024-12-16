<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\BillingAddressAggregationRoot;
use PaymentSystem\Commands\CreateBillingAddressCommandInterface;
use PaymentSystem\Repositories\BillingAddressRepositoryInterface;
use PaymentSystem\ValueObjects\Country;
use PaymentSystem\ValueObjects\Email;
use PaymentSystem\ValueObjects\PhoneNumber;
use PaymentSystem\ValueObjects\State;

class CreateBillingAddressJob implements CreateBillingAddressCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $city,
        private readonly Country $country,
        private readonly string $postalCode,
        private readonly Email $email,
        private readonly PhoneNumber $phoneNumber,
        private readonly string $addressLine,
        private readonly string $addressLineExtra = '',
        private readonly ?State $state = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(BillingAddressRepositoryInterface $repository): void
    {
        $repository->persist(BillingAddressAggregationRoot::create($this));
    }

    public function getId(): AggregateRootId
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function getAddressLine(): string
    {
        return $this->addressLine;
    }

    public function getAddressLineExtra(): string
    {
        return $this->addressLineExtra;
    }

    public function getState(): ?State
    {
        return $this->state;
    }
}