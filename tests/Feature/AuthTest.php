<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('GORONTALO');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $response = $this->post('/login', [
            'email' => 'superadmin@inspektorat.go.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'superadmin@inspektorat.go.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_rate_limiting_locks_after_repeated_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'attacker@gorontaloprov.go.id',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be blocked with rate limiting message
        $response = $this->post('/login', [
            'email' => 'attacker@gorontaloprov.go.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('email'));
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => 'superadmin@inspektorat.go.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Super Admin');
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
