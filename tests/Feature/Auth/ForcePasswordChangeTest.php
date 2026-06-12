<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_must_change_password_is_redirected_to_change_password_page(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('password.change'));
    }

    public function test_change_password_page_is_accessible_when_must_change_password_is_set(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertStatus(200);
    }

    public function test_user_without_must_change_password_is_not_redirected_to_change_password(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        // /dashboard redirects to / — not to /change-password
        $response->assertRedirect('/');
    }

    public function test_user_can_update_password_and_flag_is_cleared(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put('/change-password', [
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_password_confirmation_mismatch_keeps_flag_set(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->put('/change-password', [
            'password' => 'NewPassword@123',
            'password_confirmation' => 'DifferentPassword@456',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_guest_cannot_access_change_password_page(): void
    {
        $response = $this->get(route('password.change'));

        $response->assertRedirect(route('login'));
    }
}
