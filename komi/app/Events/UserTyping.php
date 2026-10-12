<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserTyping implements ShouldBroadcastNow
{
    public function __construct(
        public int $conversationId,
        public User $typingUser,
        public User $recipient,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->recipient->id)];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->typingUser->id,
            'name' => $this->typingUser->name,
        ];
    }
}
