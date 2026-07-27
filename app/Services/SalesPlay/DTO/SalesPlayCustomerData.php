<?php

namespace App\Services\SalesPlay\DTO;

final readonly class SalesPlayCustomerData
{
    public function __construct(
        public string $salesplayCustomerId,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $city,
        public ?string $region,
        public ?string $postalCode,
    ) {}
}
