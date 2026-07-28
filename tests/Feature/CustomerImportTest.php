<?php

namespace Tests\Feature;

use App\Jobs\SendCustomerInviteEmail;
use App\Models\Company;
use App\Models\SalesplayAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'customers.csv', 'text/csv', null, true);
    }

    public function test_admin_can_import_customers_from_a_valid_csv(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $csv = "company_name,shop_name,salesplay_shop_id,api_token,customer_name,customer_email\n"
            ."Kedai Ali,Kedai Ali Cawangan 1,shop-001,token-abc,Ali bin Abu,ali@kedaiali.test\n"
            ."Kedai Siti,Kedai Siti Cawangan 1,shop-002,token-def,Siti binti Aminah,siti@kedaisiti.test\n";

        $response = $this->actingAs($admin)->post('/admin/import', [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseHas('companies', ['name' => 'Kedai Ali']);
        $this->assertDatabaseHas('salesplay_accounts', ['salesplay_shop_id' => 'shop-001']);
        $this->assertDatabaseHas('users', ['email' => 'ali@kedaiali.test', 'role' => 'customer']);

        $aliUser = User::where('email', 'ali@kedaiali.test')->first();
        $siti = User::where('email', 'siti@kedaisiti.test')->first();

        Queue::assertPushed(SendCustomerInviteEmail::class, 2);
        Queue::assertPushed(fn (SendCustomerInviteEmail $job) => $job->user->is($aliUser));
        Queue::assertPushed(fn (SendCustomerInviteEmail $job) => $job->user->is($siti));
    }

    public function test_row_with_an_email_that_already_exists_is_skipped_but_others_still_import(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $existingCompany = Company::factory()->create();
        User::factory()->create(['company_id' => $existingCompany->id, 'email' => 'ali@kedaiali.test']);

        $csv = "company_name,shop_name,salesplay_shop_id,api_token,customer_name,customer_email\n"
            ."Kedai Ali,Kedai Ali Cawangan 1,shop-001,token-abc,Ali bin Abu,ali@kedaiali.test\n"
            ."Kedai Siti,Kedai Siti Cawangan 1,shop-002,token-def,Siti binti Aminah,siti@kedaisiti.test\n";

        $response = $this->actingAs($admin)->post('/admin/import', [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertOk();
        $response->assertSee('ali@kedaiali.test');
        $this->assertDatabaseHas('users', ['email' => 'siti@kedaisiti.test']);
        $this->assertDatabaseCount('companies', 2); // the pre-existing one + Kedai Siti only
        Queue::assertPushed(SendCustomerInviteEmail::class, 1);
    }

    public function test_row_missing_required_fields_is_reported_as_failed_without_aborting_the_rest(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();

        $csv = "company_name,shop_name,salesplay_shop_id,api_token,customer_name,customer_email\n"
            .",Kedai Tanpa Nama,shop-001,token-abc,Ali bin Abu,ali@kedaiali.test\n"
            ."Kedai Siti,Kedai Siti Cawangan 1,shop-002,token-def,Siti binti Aminah,siti@kedaisiti.test\n";

        $response = $this->actingAs($admin)->post('/admin/import', [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('salesplay_accounts', ['salesplay_shop_id' => 'shop-001']);
        $this->assertDatabaseHas('salesplay_accounts', ['salesplay_shop_id' => 'shop-002']);
        Queue::assertPushed(SendCustomerInviteEmail::class, 1);
    }

    public function test_row_with_a_duplicate_salesplay_shop_id_is_skipped(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        SalesplayAccount::factory()->create(['salesplay_shop_id' => 'shop-001']);

        $csv = "company_name,shop_name,salesplay_shop_id,api_token,customer_name,customer_email\n"
            ."Kedai Ali,Kedai Ali Cawangan 1,shop-001,token-abc,Ali bin Abu,ali@kedaiali.test\n";

        $response = $this->actingAs($admin)->post('/admin/import', [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['email' => 'ali@kedaiali.test']);
        Queue::assertNotPushed(SendCustomerInviteEmail::class);
    }

    public function test_non_admin_is_forbidden_from_importing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'customer']);

        $this->actingAs($user)->get('/admin/import')->assertForbidden();
    }
}
