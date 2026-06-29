<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    // ---- 密碼重設 ----

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'a@b.com']);

        $this->post('/forgot-password', ['email' => 'a@b.com'])->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_updates_credentials(): void
    {
        $user = User::factory()->create(['email' => 'a@b.com']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'a@b.com',
            'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    // ---- 個資 ----

    public function test_export_personal_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('participant');
        Player::create(['user_id' => $user->id, 'real_name' => '王小明', 'nickname' => '小明']);

        $this->actingAs($user->load('roles'))->getJson('/api/me/export')
            ->assertOk()
            ->assertJsonPath('player.nickname', '小明');
    }

    public function test_withdraw_portrait_consent(): void
    {
        $user = User::factory()->create();
        $user->assignRole('participant');
        $player = Player::create(['user_id' => $user->id, 'real_name' => 'A', 'portrait_consent' => true]);

        $this->actingAs($user->load('roles'))->postJson('/api/me/withdraw-portrait')
            ->assertOk()->assertJsonPath('portrait_consent', false);

        $this->assertFalse((bool) $player->fresh()->portrait_consent);
    }

    // ---- CI 設定存在 ----

    public function test_ci_workflow_exists(): void
    {
        $this->assertFileExists(base_path('.github/workflows/ci.yml'));
    }
}
