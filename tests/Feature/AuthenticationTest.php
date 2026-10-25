<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
    }

    public function test_head_office_admin_logs_in_with_new_email_and_shared_password(): void
    {
        $response = $this->post(route('login'), [
            'login' => 'admin@gmail.com',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@gmail.com')->first());
    }

    public function test_depot_operator_can_log_in_with_employee_id(): void
    {
        $response = $this->post(route('login'), [
            'login' => 'EMP-101',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'dhaka.depot@aci-bd.com')->first());
    }

    public function test_depot_operator_can_log_in_with_email_as_well(): void
    {
        $response = $this->post(route('login'), [
            'login' => 'dhaka.depot@aci-bd.com',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_employee_id_login_is_case_insensitive(): void
    {
        $this->post(route('login'), [
            'login' => 'emp-101',
            'password' => '123456',
        ]);

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->post(route('login'), [
            'login' => 'EMP-101',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
