<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    public function test_returns_a_successful_response(): void
    {
        $company = Company::factory()->create();
        $this->setupModuleAccess($company->id);
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($adminRole);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
    }
}
