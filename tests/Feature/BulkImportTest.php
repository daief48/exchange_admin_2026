<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
    }

    protected function csvUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bulk') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_bulk_import_depots_inserts_valid_rows_and_skips_duplicates(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $csv = "name,code,contact_email,status\n"
            . "Rangamati Hub,RANGAMATI,rangamati@aci-bd.com,active\n"
            . "Jashore,JASHORE,duplicate@aci-bd.com,active\n"
            . ",MISSINGNAME,x@aci-bd.com,active\n";

        $response = $this->post(route('master.bulk-import', 'depots'), [
            'file' => $this->csvUpload('depots.csv', $csv),
        ]);

        $response->assertRedirect(route('master.index'));

        $this->assertDatabaseHas('depots', ['code' => 'RANGAMATI', 'name' => 'Rangamati Hub']);
        $this->assertDatabaseMissing('depots', ['code' => 'MISSINGNAME']);

        $report = session('bulk_report');
        $this->assertSame(1, $report['inserted']);
        $this->assertSame(1, $report['skipped']);
        $this->assertSame(1, $report['failed']);
        $this->assertStringContainsString('name field is required', implode(' ', $report['errors']));
    }

    public function test_bulk_import_creates_depot_operators_only(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $jashore = Depot::where('name', 'Jashore')->first();
        $benapole = Depot::where('name', 'Benapole')->first();

        $csv = "name,email,password,depot_code\n"
            . "Md. Rahim Uddin,rahim@aci-bd.com,password123,JASHORE\n"
            . "Nusrat Jahan,nusrat@aci-bd.com,password123,BENAPOLE\n"
            . "Dup Admin,admin@gmail.com,password123,JASHORE\n"
            . "No Depot User,nodepot@aci-bd.com,password123,\n";

        $this->post(route('master.bulk-import', 'users'), [
            'file' => $this->csvUpload('users.csv', $csv),
        ])->assertRedirect(route('master.index'));

        $rahim = User::where('email', 'rahim@aci-bd.com')->first();
        $this->assertNotNull($rahim);
        $this->assertTrue($rahim->isDepotOperator());
        $this->assertEquals($jashore->id, $rahim->depot_id);

        $nusrat = User::where('email', 'nusrat@aci-bd.com')->first();
        $this->assertNotNull($nusrat);
        $this->assertTrue($nusrat->isDepotOperator());
        $this->assertEquals($benapole->id, $nusrat->depot_id);

        // Row without a depot is rejected — no half-configured operators.
        $this->assertDatabaseMissing('users', ['email' => 'nodepot@aci-bd.com']);

        $report = session('bulk_report');
        $this->assertSame(2, $report['inserted']);
        $this->assertSame(1, $report['skipped']);
        $this->assertSame(1, $report['failed']);
        $this->assertStringContainsString('depot_code is required', implode(' ', $report['errors']));
    }

    public function test_bulk_import_rejects_depot_operator_without_depot_code(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $csv = "name,email,password,depot_code\n"
            . "Ghost Operator,ghost@aci-bd.com,password123,\n";

        $this->post(route('master.bulk-import', 'users'), [
            'file' => $this->csvUpload('users.csv', $csv),
        ])->assertRedirect(route('master.index'));

        $this->assertDatabaseMissing('users', ['email' => 'ghost@aci-bd.com']);
        $this->assertStringContainsString('depot_code is required', implode(' ', session('bulk_report')['errors']));
    }

    public function test_single_user_creation_is_restricted_to_depot_operators(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $depot = Depot::where('name', 'Sylhet')->first();

        // Attempting to provision a head_office account from this module is rejected.
        $this->from(route('master.index'))->post(route('master.users.store'), [
            'name' => 'Sneaky Admin',
            'employee_id' => 'EMP-SNEAKY',
            'email' => 'sneaky@aci-bd.com',
            'password' => 'password123',
            'role' => 'head_office',
            'depot_id' => $depot->id,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@aci-bd.com']);

        // A normal depot operator creation works and is always forced to that role.
        $this->post(route('master.users.store'), [
            'name' => 'Sylhet In-Charge',
            'employee_id' => 'EMP-SYLHET-01',
            'email' => 'sylhet@aci-bd.com',
            'password' => 'password123',
            'depot_id' => $depot->id,
        ]);

        $user = User::where('email', 'sylhet@aci-bd.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isDepotOperator());
        $this->assertEquals($depot->id, $user->depot_id);
        $this->assertEquals('EMP-SYLHET-01', $user->employee_id);
    }

    public function test_bulk_import_is_restricted_to_head_office_admin(): void
    {
        $depotUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($depotUser);

        $this->post(route('master.bulk-import', 'depots'), [
            'file' => $this->csvUpload('depots.csv', "name,code\nX,X1\n"),
        ])->assertStatus(403);
    }

    public function test_bulk_upload_templates_download_successfully(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $this->get(route('master.template', 'depots'))->assertStatus(200);
        $this->get(route('master.template', 'businesses'))->assertStatus(200);
        $this->get(route('master.template', 'expense-heads'))->assertStatus(200);
        $this->get(route('master.template', 'users'))->assertStatus(200);
        $this->get(route('master.template', 'unknown'))->assertStatus(404);
    }
}
