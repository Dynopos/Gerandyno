<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads a bulk customer-onboarding CSV into raw rows. Expected columns
 * (header names are case-insensitive and spaces become underscores, per
 * WithHeadingRow): company_name, shop_name, salesplay_shop_id, api_token,
 * customer_name, customer_email.
 *
 * Deliberately does no validation or database writes itself — that's
 * CustomerImportController's job, so it can report per-row success/failure
 * instead of aborting the whole file on the first bad row.
 */
class CustomerImport implements ToCollection, WithHeadingRow
{
    private Collection $rows;

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function rows(): Collection
    {
        return $this->rows;
    }
}
