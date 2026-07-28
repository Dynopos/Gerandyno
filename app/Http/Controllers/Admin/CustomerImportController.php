<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CustomerImport;
use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Bulk-onboards customers from a CSV: one row creates a Company, its
 * SalesplayAccount, and a login User, then emails that user a
 * password-reset link so they set their own password. Built for onboarding
 * large batches (hundreds to thousands of rows) that would be impractical
 * to enter one at a time through the Company/SalesPlay Account admin forms.
 *
 * Each row is validated and processed independently so one bad row (a
 * missing field, a duplicate email or shop ID) doesn't abort the rest of
 * the file — the result summary reports exactly what happened per row.
 */
class CustomerImportController extends Controller
{
    private const REQUIRED_COLUMNS = [
        'company_name', 'shop_name', 'salesplay_shop_id', 'api_token', 'customer_name', 'customer_email',
    ];

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('admin.import.create');
    }

    public function store(Request $request): View
    {
        $this->authorize('create', Company::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $import = new CustomerImport;
        Excel::import($import, $request->file('file'));

        $results = $this->processRows($import->rows());

        return view('admin.import.create', ['results' => $results]);
    }

    /**
     * @return array{created: array<int, array{row: int, email: string}>, skipped: array<int, array{row: int, reason: string}>, failed: array<int, array{row: int, reason: string}>}
     */
    private function processRows(Collection $rows): array
    {
        $results = ['created' => [], 'skipped' => [], 'failed' => []];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for the header row

            $data = collect(self::REQUIRED_COLUMNS)
                ->mapWithKeys(fn (string $column) => [$column => trim((string) ($row[$column] ?? ''))])
                ->all();

            if (collect($data)->every(fn (string $value) => $value === '')) {
                continue;
            }

            $validator = Validator::make($data, [
                'company_name' => ['required', 'string', 'max:255'],
                'shop_name' => ['required', 'string', 'max:255'],
                'salesplay_shop_id' => ['required', 'string', 'max:255'],
                'api_token' => ['required', 'string'],
                'customer_name' => ['required', 'string', 'max:255'],
                'customer_email' => ['required', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                $results['failed'][] = ['row' => $rowNumber, 'reason' => $validator->errors()->first()];

                continue;
            }

            if (User::where('email', $data['customer_email'])->exists()) {
                $results['skipped'][] = ['row' => $rowNumber, 'reason' => __('app.admin.import.duplicate_email', ['email' => $data['customer_email']])];

                continue;
            }

            if (SalesplayAccount::where('salesplay_shop_id', $data['salesplay_shop_id'])->exists()) {
                $results['skipped'][] = ['row' => $rowNumber, 'reason' => __('app.admin.import.duplicate_shop', ['shop_id' => $data['salesplay_shop_id']])];

                continue;
            }

            $user = DB::transaction(function () use ($data): User {
                $company = Company::create(['name' => $data['company_name'], 'status' => 'active']);

                SalesplayAccount::create([
                    'company_id' => $company->id,
                    'shop_name' => $data['shop_name'],
                    'salesplay_shop_id' => $data['salesplay_shop_id'],
                    'api_token' => $data['api_token'],
                    'status' => 'active',
                ]);

                return User::create([
                    'company_id' => $company->id,
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'password' => Str::random(40),
                    'role' => 'customer',
                ]);
            });

            SendCustomerInviteEmail::dispatch($user);

            $results['created'][] = ['row' => $rowNumber, 'email' => $data['customer_email']];
        }

        return $results;
    }
}
