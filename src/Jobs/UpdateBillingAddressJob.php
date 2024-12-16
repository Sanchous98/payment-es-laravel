<?php

namespace PaymentSystem\Laravel\Jobs;

use EventSauce\EventSourcing\AggregateRootId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PaymentSystem\Commands\UpdateBillingAddressCommandInterface;
use PaymentSystem\Repositories\BillingAddressRepositoryInterface;
use PaymentSystem\ValueObjects\Country;
use PaymentSystem\ValueObjects\Email;
use PaymentSystem\ValueObjects\PhoneNumber;
use PaymentSystem\ValueObjects\State;

class UpdateBillingAddressJob implements UpdateBillingAddressCommandInterface, ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly AggregateRootId $id,
        private readonly ?string $firstName = null,
        private readonly ?string $lastName = null,
        private readonly ?string $city = null,
        private readonly ?Country $country = null,
        private readonly ?string $postalCode = null,
        private readonly ?Email $email = null,
        private readonly ?PhoneNumber $phoneNumber = null,
        private readonly ?string $addressLine = null,
        private readonly ?string $addressLineExtra = null,
        private readonly ?State $state = null,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->id;
    }

    public function __invoke(BillingAddressRepositoryInterface $repository): void
    {
        $repository->persist($repository->retrieve($this->id)->update($this));
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function getAddressLine(): ?string
    {
        return $this->addressLine;
    }

    public function getAddressLineExtra(): ?string
    {
        return $this->addressLineExtra;
    }

    public function getState(): ?State
    {
        return $this->state;
    }
}