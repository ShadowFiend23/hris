<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $company = Company::factory()->create();
        $this->setupModuleAccess($company->id);
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($adminRole);

        $response = $this->actingAs($user)->followingRedirects()->get(route('dashboard'));
        $response->assertOk();
    }
}
