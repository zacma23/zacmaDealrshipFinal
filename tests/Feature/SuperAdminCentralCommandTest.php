<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SmmPlatform;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminCentralCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $resellerUser;
    protected User $customerUser;
    protected Organization $childPanelOrg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->childPanelOrg = Organization::create([
            'name' => 'Alpha SMM Agency',
            'slug' => 'alpha-smm',
            'subdomain' => 'alpha',
            'custom_domain' => 'panel.alpha-agency.com',
            'custom_domain_status' => 'active',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Global Super Administrator',
            'email' => 'admin@zacma.com',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
        Wallet::create(['user_id' => $this->superAdmin->id, 'balance' => 1000.00]);

        $this->resellerUser = User::create([
            'organization_id' => $this->childPanelOrg->id,
            'name' => 'Reseller Boss',
            'email' => 'boss@alpha-agency.com',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_ORGANIZATION_ADMIN,
            'is_active' => true,
        ]);
        Wallet::create(['user_id' => $this->resellerUser->id, 'balance' => 500.00]);

        $this->customerUser = User::create([
            'name' => 'Customer John',
            'email' => 'john@gmail.com',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
        Wallet::create(['user_id' => $this->customerUser->id, 'balance' => 50.00]);
    }

    public function test_super_admin_can_access_all_command_center_modules()
    {
        $this->actingAs($this->superAdmin);

        $routes = [
            route('super-admin.smm.dashboard'),
            route('super-admin.smm.platforms'),
            route('super-admin.smm.services'),
            route('super-admin.smm.providers'),
            route('super-admin.smm.orders'),
            route('super-admin.smm.wallets'),
            route('super-admin.smm.child-panels'),
            route('super-admin.smm.tickets'),
            route('super-admin.users.index'),
            route('super-admin.settings.index'),
            route('super-admin.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertOk();
        }

        // Test root super-admin dashboard route
        $resp = $this->get(route('super-admin.dashboard'));
        $resp->assertOk();
    }

    public function test_non_super_admin_cannot_access_super_admin_routes()
    {
        $this->actingAs($this->customerUser);

        $response = $this->get(route('super-admin.smm.dashboard'));
        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_super_admin_can_impersonate_and_stop_impersonation()
    {
        $this->actingAs($this->superAdmin);

        // Impersonate customer
        $response = $this->post(route('super-admin.impersonate', $this->customerUser->id));
        $response->assertRedirect($this->customerUser->getDashboardUrl());
        $this->assertEquals($this->customerUser->id, auth()->id());
        $this->assertEquals($this->superAdmin->id, session('impersonator_id'));

        // Stop impersonation
        $stopResponse = $this->post(route('super-admin.stop-impersonation'));
        $stopResponse->assertRedirect(route('super-admin.smm.dashboard'));
        $this->assertEquals($this->superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_super_admin_can_toggle_user_status()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('super-admin.users.toggle', $this->customerUser->id));
        $response->assertSessionHas('success');
        $this->assertFalse($this->customerUser->fresh()->is_active);

        $response = $this->post(route('super-admin.users.toggle', $this->customerUser->id));
        $response->assertSessionHas('success');
        $this->assertTrue($this->customerUser->fresh()->is_active);
    }

    public function test_quick_login_authenticates_super_admin()
    {
        $response = $this->get(route('login.quick', 'super_admin'));
        $response->assertRedirect(route('super-admin.smm.dashboard'));
        $this->assertTrue(auth()->check());
        $this->assertTrue(auth()->user()->isSuperAdmin());
    }
}