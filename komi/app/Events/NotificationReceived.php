<?php

namespace App\Events;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NotificationReceived implements ShouldBroadcastNow
{
    public function __construct(
        public AppNotification $notification,
        public int $unreadCount,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWith(): array
    {
        $this->notification->loadMissing('fromUser');

        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'post_id' => $this->notification->post_id,
            'comment_id' => $this->notification->comment_id,
            'conversation_id' => $this->notification->conversation_id,
            'message_id' => $this->notification->message_id,
            'created_at' => $this->notification->created_at?->toISOString(),
            'unread_count' => $this->unreadCount,
            'from_user' => [
                'id' => $this->notification->fromUser?->id,
                'name' => $this->notification->fromUser?->name,
                'username' => $this->notification->fromUser?->username,
                'avatar_url' => $this->notification->fromUser?->avatar,
            ],
        ];
    }
}
