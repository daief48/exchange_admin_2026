<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Seed database
        $this->seed(MasterDataSeeder::class);
    }

    public function test_backdated_entries_are_rejected_with_422_unprocessable_entity(): void
    {
        $dhakaUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($dhakaUser);

        $depot = Depot::first();
        $business = Business::first();
        $head = ExpenseHead::first();

        // Yesterday's date
        $yesterday = Carbon::yesterday()->toDateString();

        $response = $this->postJson(route('expenses.store'), [
            'date' => $yesterday,
            'depot_id' => $depot->id,
            'business_id' => $business->id,
            'expense_head_id' => $head->id,
            'amount' => 5000.00,
            'remarks' => 'Backdated test entry attempt',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    public function test_current_date_expense_entry_is_accepted_and_persisted(): void
    {
        $dhakaUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($dhakaUser);

        $depot = Depot::first();
        $business = Business::first();
        $head = ExpenseHead::first();

        $today = Carbon::today()->toDateString();

        $response = $this->post(route('expenses.store'), [
            'date' => $today,
            'depot_id' => $depot->id,
            'business_id' => $business->id,
            'expense_head_id' => $head->id,
            'amount' => 12500.50,
            'remarks' => 'Valid current day logistics test entry',
        ]);

        $response->assertRedirect(route('expenses.create'));
        $this->assertDatabaseHas('expenses', [
            'date' => $today,
            'amount' => 12500.50,
            'remarks' => 'Valid current day logistics test entry',
        ]);
    }

    public function test_depot_users_attempting_to_export_raw_data_receive_403_forbidden(): void
    {
        $dhakaUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($dhakaUser);

        $response = $this->get(route('expenses.export'));
        $response->assertStatus(403);
    }

    public function test_head_office_users_can_export_raw_data(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $response = $this->get(route('expenses.export', ['format' => 'csv']));
        $response->assertStatus(200);
    }

    public function test_missing_entry_scheduler_accurately_identifies_zero_entry_depots(): void
    {
        // Target date with known empty depot
        $testDate = '2026-12-31';

        $exitCode = Artisan::call('app:check-missing-depot-entries', [
            '--date' => $testDate,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verify missing entry logs created
        $allDepotsCount = Depot::where('status', true)->count();
        $this->assertDatabaseHas('missing_entry_logs', [
            'date' => $testDate,
        ]);
    }

    public function test_depot_operator_is_blocked_from_expense_logs_but_can_create(): void
    {
        $dhakaUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($dhakaUser);

        // Expense logs are reserved for Head Office / audit roles
        $this->get(route('expenses.index'))->assertStatus(403);

        // Depot operators keep their daily entry form
        $this->get(route('expenses.create'))->assertOk();
    }

    public function test_budgets_page_renders_depot_expense_head_matrix(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $response = $this->get(route('budgets.index', ['year' => now()->year, 'month' => 'ALL']));

        $response->assertOk();
        $response->assertSee('Depot × Expense Head Expense Summary');
        $response->assertSee('% of Grand Total');
        $response->assertSee(Depot::where('status', true)->count() . ' Depots ×');
        $response->assertSee('Total (BDT)');
    }

    public function test_budgets_matrix_respects_depot_and_expense_head_filters(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $dhaka = Depot::where('name', 'Dhaka')->first();
        $head = ExpenseHead::where('status', true)->orderBy('name')->first();

        $response = $this->get(route('budgets.index', [
            'year' => now()->year,
            'month' => 'ALL',
            'depot_ids' => [$dhaka->id],
            'expense_head_ids' => [$head->id],
        ]));

        $response->assertOk();
        $response->assertSee('1 Depots × 1 Heads');

        // Matrix cells must equal the filtered depot × head sum for the year
        $expected = (float) Expense::where('year', now()->year)
            ->where('depot_id', $dhaka->id)
            ->where('expense_head_id', $head->id)
            ->sum('amount');
        $response->assertSee(number_format($expected, 2));
    }

    public function test_expense_voucher_pdf_downloads_for_head_office(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $expense = Expense::first();

        $response = $this->get(route('expenses.pdf', $expense));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_depot_operator_cannot_download_other_depot_expense_pdf(): void
    {
        $dhakaUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($dhakaUser);

        $otherDepotExpense = Expense::where('depot_id', '!=', $dhakaUser->depot_id)->first();

        $this->get(route('expenses.pdf', $otherDepotExpense))->assertStatus(403);
    }
}
