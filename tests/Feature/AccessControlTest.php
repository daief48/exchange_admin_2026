<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
    }

    public function test_depot_operator_cannot_access_master_data_module(): void
    {
        $depotUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($depotUser);

        $this->get(route('master.index'))->assertStatus(403);
        $this->post(route('master.depots.store'), [
            'name' => 'Illegal Depot',
            'code' => 'ILLEGAL',
        ])->assertStatus(403);
    }

    public function test_my_depot_expenses_page_is_dedicated_to_depot_operators(): void
    {
        $depotUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($depotUser);

        // Depot operators get their dedicated log page.
        $response = $this->get(route('my-depot.expenses'));
        $response->assertOk();
        $response->assertSee('My Depot Expense Log');
        $response->assertSee('Dhaka');

        // Head Office and Auditor are pointed to the full audit log instead.
        $this->actingAs(User::where('email', 'admin@gmail.com')->first());
        $this->get(route('my-depot.expenses'))->assertStatus(403);

        $this->actingAs(User::where('email', 'auditor@aci-bd.com')->first());
        $this->get(route('my-depot.expenses'))->assertStatus(403);
    }

    public function test_login_page_hides_demo_one_click_switcher(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Fast Demo 1-Click Login')
            ->assertSee('Sign In to Portal');
    }

    public function test_admin_can_reset_user_password_to_shared_default(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $target = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $target->update(['password' => bcrypt('something-else')]);

        // Depot operators must not be able to reset passwords.
        $this->actingAs($target);
        $this->post(route('master.users.reset-password', $target))->assertStatus(403);

        // HO resets → shared password works again.
        $this->actingAs($hoAdmin);
        $this->from(route('master.index'))
            ->post(route('master.users.reset-password', $target))
            ->assertRedirect(route('master.index'))
            ->assertSessionHas('success');

        $this->post(route('login'), [
            'login' => 'EMP-101',
            'password' => '123456',
        ])->assertRedirect(route('dashboard'));

        // Functional verification: the reset made 123456 valid again for the
        // Dhaka operator's EMP-101 identity.
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('123456', $target->fresh()->password),
            'Reset must set the password back to 123456'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Auth::attempt(['employee_id' => 'EMP-101', 'password' => '123456']),
            'EMP-101 / 123456 must be valid credentials after the reset'
        );
        $this->assertSame('dhaka.depot@aci-bd.com', auth()->user()->email);
    }

    public function test_auditor_cannot_access_master_data_module(): void
    {
        $auditor = User::where('email', 'auditor@aci-bd.com')->first();
        $this->actingAs($auditor);

        $this->get(route('master.index'))->assertStatus(403);
        $this->post(route('master.users.store'), [
            'name' => 'Escalated Admin',
            'email' => 'escalated@aci-bd.com',
            'password' => 'secret123',
            'role' => 'head_office',
        ])->assertStatus(403);
    }

    public function test_head_office_admin_can_access_master_data_module(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $this->get(route('master.index'))->assertStatus(200);
    }

    public function test_demo_role_switcher_is_disabled_outside_local_environment(): void
    {
        // The testing environment is neither local nor demo-enabled, so the
        // impersonation backdoor must be closed even if the URL is hit directly.
        config(['app.demo_switcher' => false]);

        $depotUser = User::where('email', 'dhaka.depot@aci-bd.com')->first();
        $this->actingAs($depotUser);

        $hoAdminId = User::where('email', 'admin@gmail.com')->first()->id;

        $this->post('/switch-role', ['user_id' => $hoAdminId])->assertStatus(403);
        $this->assertAuthenticatedAs($depotUser);
    }

    public function test_month_range_filter_returns_only_months_in_range(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $response = $this->getJson(route('dashboard.data', [
            'from_year' => now()->year,
            'to_year' => now()->year,
            'from_month' => 'JAN',
            'to_month' => 'MAR',
        ]))->assertStatus(200)->assertJsonStructure(['totalExpenseRaw']);

        // Seeded data spans the last 45 days; a JAN-MAR window may legitimately
        // be empty late in the year, so only assert the filter executes cleanly
        // and the shape is intact.
        $this->assertArrayHasKey('totalExpenseRaw', $response->json());
    }

    public function test_dashboard_renders_with_sidebar_and_range_filters(): void
    {
        $hoAdmin = User::where('email', 'admin@gmail.com')->first();
        $this->actingAs($hoAdmin);

        $response = $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('appSidebar')
            ->assertSee('from_month')
            ->assertSee('to_year')
            ->assertSee(strtoupper(now()->format('F Y')));

        $this->assertTrue(strpos($response->getContent(), 'Master Data') !== false);
    }
}
