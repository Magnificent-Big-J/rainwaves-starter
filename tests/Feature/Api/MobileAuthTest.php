<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use App\Services\MobileAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Rainwaves\LaraAuthSuite\Domain\Notifications\TwoFactorEmailCode;
use ReflectionProperty;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function devicePayload(array $overrides = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'platform' => 'android',
            'model' => 'Pixel 8',
            'os_version' => '15',
            'app_version' => '1.0.0',
        ], $overrides);
    }

    public function test_login_without_two_factor_issues_device_named_mobile_token(): void
    {
        $device = $this->devicePayload();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@rainwaves.test',
            'password' => 'password',
            'device' => $device,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'customer@rainwaves.test')
            ->assertJsonPath('data.device.uuid', $device['uuid'])
            ->assertJsonPath('data.device.is_current', true);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        $accessToken = PersonalAccessToken::findToken($token);
        $this->assertSame($device['uuid'], $accessToken->name);
        $this->assertTrue($accessToken->can(MobileAuthService::TOKEN_ABILITY));

        $this->assertDatabaseHas('devices', [
            'uuid' => $device['uuid'],
            'personal_access_token_id' => $accessToken->getKey(),
        ]);

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'customer@rainwaves.test');
    }

    public function test_login_with_invalid_credentials_returns_422_envelope(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@rainwaves.test',
            'password' => 'wrong-password',
            'device' => $this->devicePayload(),
        ])->assertStatus(422)->assertJson(['success' => false, 'data' => null]);
    }

    public function test_login_validates_device_payload(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@rainwaves.test',
            'password' => 'password',
            'device' => ['uuid' => 'not-a-uuid', 'platform' => 'blackberry'],
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['device.uuid', 'device.platform']]);
    }

    public function test_login_with_two_factor_user_requires_challenge_then_issues_token(): void
    {
        Notification::fake();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $device = $this->devicePayload();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@rainwaves.test',
            'password' => 'password',
            'device' => $device,
        ]);

        $login->assertOk()
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonMissingPath('data.token');

        $pendingAuthId = $login->json('data.pending_auth_id');
        $this->assertNotEmpty($pendingAuthId);
        $this->assertSame(0, $ops->tokens()->count());

        $code = null;
        Notification::assertSentTo($ops, TwoFactorEmailCode::class, function ($notification) use (&$code) {
            $code = (new ReflectionProperty($notification, 'code'))->getValue($notification);

            return true;
        });

        $verify = $this->postJson('/api/v1/auth/two-factor', [
            'pending_auth_id' => $pendingAuthId,
            'code' => $code,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.device.uuid', $device['uuid'])
            ->assertJsonPath('data.user.email', 'ops@rainwaves.test');

        $this->assertNotEmpty($verify->json('data.token'));
        $this->assertDatabaseHas('devices', ['uuid' => $device['uuid'], 'user_id' => $ops->id]);
    }

    public function test_two_factor_challenge_rejects_bad_code(): void
    {
        Notification::fake();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@rainwaves.test',
            'password' => 'password',
            'device' => $this->devicePayload(),
        ]);

        $this->postJson('/api/v1/auth/two-factor', [
            'pending_auth_id' => $login->json('data.pending_auth_id'),
            'code' => '000000',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['code']]);
    }

    public function test_two_factor_challenge_rejects_unknown_pending_id(): void
    {
        $this->postJson('/api/v1/auth/two-factor', [
            'pending_auth_id' => (string) Str::uuid(),
            'code' => '123456',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['pending_auth_id']]);
    }

    public function test_relogin_on_same_device_replaces_the_previous_token(): void
    {
        $user = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $device = $this->devicePayload();
        $payload = ['email' => 'customer@rainwaves.test', 'password' => 'password', 'device' => $device];

        $firstToken = $this->postJson('/api/v1/auth/login', $payload)->json('data.token');
        $secondToken = $this->postJson('/api/v1/auth/login', $payload)->json('data.token');

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame(1, Device::where('uuid', $device['uuid'])->count());

        $this->assertNull(PersonalAccessToken::findToken($firstToken));
        $this->assertNotNull(PersonalAccessToken::findToken($secondToken));
    }

    public function test_logout_revokes_the_current_token_but_keeps_the_device(): void
    {
        $device = $this->devicePayload();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@rainwaves.test',
            'password' => 'password',
            'device' => $device,
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertDatabaseHas('devices', ['uuid' => $device['uuid'], 'personal_access_token_id' => null]);

        // Drop the guard's cached user so the next request re-authenticates.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_login_is_rate_limited(): void
    {
        $payload = [
            'email' => 'customer@rainwaves.test',
            'password' => 'wrong-password',
            'device' => $this->devicePayload(),
        ];

        $limit = (int) config('authx.throttle.login', 5);

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertStatus(429)
            ->assertJson(['success' => false]);
    }
}
