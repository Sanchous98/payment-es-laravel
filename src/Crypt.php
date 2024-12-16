<?php

namespace PaymentSystem\Laravel;

use Illuminate\Contracts\Encryption\Encrypter;
use PaymentSystem\Contracts\DecryptInterface;
use PaymentSystem\Contracts\EncryptInterface;

readonly class Crypt implements DecryptInterface, EncryptInterface
{
    public function __construct(
        private Encrypter $crypter,
    ) {
    }

    public function decrypt(string $data): string
    {
        return $this->crypter->decrypt($data);
    }

    public function encrypt(#[\SensitiveParameter] string $data): string
    {
        return $this->crypter->encrypt($data);
    }
}
