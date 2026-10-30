<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminInsp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
    }

    public function test_super_admin_login_triggers_otp_email_notification(): void
    {
        Notification::fake();

        $response = $this->post('/login', [
            'email' => 'superadmin@inspektorat.go.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('2fa.show'));
        $this->assertSame($this->superAdmin->id, session('auth.2fa.user_id'));

        Notification::assertSentTo(
            $this->superAdmin,
            TwoFactorOtpNotification::class,
            function ($notification) {
                return strlen($notification->otp) === 6 && ctype_digit($notification->otp);
            }
        );
    }

    public function test_two_factor_screen_renders_for_pending_user(): void
    {
        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->get(route('2fa.show'));

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Dua Langkah (2FA)');
        $response->assertSee('Kode Verifikasi OTP');
    }

    public function test_two_factor_screen_redirects_uninitiated_guest_to_login(): void
    {
        $response = $this->get(route('2fa.show'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_accessing_two_factor_screen_is_redirected_to_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('2fa.show'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_cannot_verify_with_invalid_otp(): void
    {
        $this->superAdmin->generateTwoFactorOtp();

        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.verify'), [
            'otp' => '999999',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp');
    }

    public function test_super_admin_cannot_verify_with_expired_otp(): void
    {
        $otp = $this->superAdmin->generateTwoFactorOtp();

        // Expire the OTP
        $this->superAdmin->update([
            'otp_expires_at' => now()->subMinute(),
        ]);

        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.verify'), [
            'otp' => $otp,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp');
    }

    public function test_super_admin_can_authenticate_with_valid_otp(): void
    {
        $otp = $this->superAdmin->generateTwoFactorOtp();

        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.verify'), [
            'otp' => $otp,
        ]);

        $this->assertAuthenticatedAs($this->superAdmin);
        $response->assertRedirect(route('dashboard'));

        // OTP code should be cleared
        $this->assertNull($this->superAdmin->fresh()->otp_code);
        $this->assertNull($this->superAdmin->fresh()->otp_expires_at);
    }

    public function test_super_admin_can_resend_otp(): void
    {
        Notification::fake();

        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.resend'));

        $response->assertSessionHas('success');
        Notification::assertSentTo($this->superAdmin, TwoFactorOtpNotification::class);
    }

    public function test_super_admin_can_cancel_two_factor_and_return_to_login(): void
    {
        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.cancel'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('auth.2fa.user_id'));
    }

    public function test_verification_is_rate_limited_after_multiple_failures(): void
    {
        $this->superAdmin->generateTwoFactorOtp();

        for ($i = 0; $i < 5; $i++) {
            $this->withSession([
                'auth.2fa.user_id' => $this->superAdmin->id,
            ])->post(route('2fa.verify'), [
                'otp' => '000000',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->withSession([
            'auth.2fa.user_id' => $this->superAdmin->id,
        ])->post(route('2fa.verify'), [
            'otp' => '000000',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('otp');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('otp'));
    }
}
