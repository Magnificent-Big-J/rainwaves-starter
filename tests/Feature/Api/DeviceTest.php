<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function loginDevice(string $email = 'customer@rainwaves.test'): array
    {
        $uuid = (string) Str::uuid();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password',
            'device' => ['uuid' => $uuid, 'platform' => 'android'],
        ])->json('data.token');

        return [$token, $uuid];
    }

    public function test_devices_index_lists_own_devices_with_current_flag(): void
    {
        [$token, $uuid] = $this->loginDevice();

        $this->withToken($token)->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $uuid)
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonMissingPath('data.0.id');
    }

    public function test_device_upsert_updates_metadata_without_duplicating(): void
    {
        [$token, $uuid] = $this->loginDevice();

        $this->withToken($token)->postJson('/api/v1/devices', [
            'uuid' => $uuid,
            'platform' => 'android',
            'app_version' => '1.1.0',
            'push_token' => 'future-fcm-token',
        ])->assertOk()->assertJsonPath('data.app_version', '1.1.0');

        $user = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $this->assertSame(1, $user->devices()->count());
        $this->assertDatabaseHas('devices', ['uuid' => $uuid, 'app_version' => '1.1.0']);
    }

    public function test_registering_a_new_device_returns_201(): void
    {
        [$token] = $this->loginDevice();

        $this->withToken($token)->postJson('/api/v1/devices', [
            'uuid' => (string) Str::uuid(),
            'platform' => 'ios',
            'model' => 'iPhone 15',
        ])->assertStatus(201);
    }

    public function test_deleting_a_device_revokes_its_token(): void
    {
        [$token, $uuid] = $this->loginDevice();

        $this->withToken($token)->deleteJson("/api/v1/devices/{$uuid}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('devices', ['uuid' => $uuid]);
        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_cannot_touch_another_users_device(): void
    {
        [, $customerDeviceUuid] = $this->loginDevice();
        [$opsToken] = $this->loginDevice('owner@rainwaves.test');

        $this->withToken($opsToken)->deleteJson("/api/v1/devices/{$customerDeviceUuid}")
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
