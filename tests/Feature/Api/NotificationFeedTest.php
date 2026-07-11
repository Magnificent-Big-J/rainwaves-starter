<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\SystemAnnouncementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        Sanctum::actingAs($this->user);
    }

    public function test_feed_lists_notifications_with_payload_contract_and_unread_count(): void
    {
        $this->user->notify(new SystemAnnouncementNotification('Maintenance', 'Sunday 02:00 UTC.'));
        $this->user->notify(new SystemAnnouncementNotification('Welcome', 'Thanks for joining.'));

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('data.0.type', 'system.announcement')
            ->assertJsonPath('data.0.route', 'home')
            ->assertJsonStructure([
                'data' => [['id', 'type', 'title', 'body', 'route', 'params', 'read_at', 'created_at']],
                'meta' => ['pagination' => ['current_page', 'per_page', 'last_page', 'total'], 'unread_count'],
            ]);
    }

    public function test_unread_filter_hides_read_notifications(): void
    {
        $this->user->notify(new SystemAnnouncementNotification('Read me', 'x'));
        $this->user->notifications()->first()->markAsRead();
        $this->user->notify(new SystemAnnouncementNotification('Unread', 'y'));

        $this->getJson('/api/v1/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Unread');
    }

    public function test_mark_read_updates_one_notification_and_unread_count(): void
    {
        $this->user->notify(new SystemAnnouncementNotification('A', 'a'));
        $this->user->notify(new SystemAnnouncementNotification('B', 'b'));

        $id = $this->user->notifications()->first()->id;

        $this->postJson("/api/v1/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1);

        $this->assertNotNull($this->user->notifications()->find($id)->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count(): void
    {
        $this->user->notify(new SystemAnnouncementNotification('A', 'a'));
        $this->user->notify(new SystemAnnouncementNotification('B', 'b'));

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_cannot_mark_another_users_notification(): void
    {
        $other = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $other->notify(new SystemAnnouncementNotification('Private', 'z'));

        $id = $other->notifications()->first()->id;

        $this->postJson("/api/v1/notifications/{$id}/read")
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
