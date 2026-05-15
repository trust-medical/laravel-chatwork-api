<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-token',
    ]);
});

function queueableNotifiable(int $roomId): object
{
    return new class($roomId)
    {
        use Notifiable;

        public function __construct(private readonly int $roomId) {}

        public function routeNotificationForChatwork(): int
        {
            return $this->roomId;
        }
    };
}

it('runs synchronously when the notification does not implement ShouldQueue', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    queueableNotifiable(801)->notify(new ChatworkMessage('Sync'));

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/801/messages');
});

it('dispatches SendQueuedNotifications job for a ShouldQueue notification', function () {
    Queue::fake();

    $notification = new class() extends ChatworkNotification implements ShouldQueue
    {
        use Queueable;

        public function toChatwork(object $notifiable): ChatworkMessage
        {
            return new ChatworkMessage('Queued body');
        }
    };

    queueableNotifiable(802)->notify($notification);

    Queue::assertPushed(SendQueuedNotifications::class);
});
