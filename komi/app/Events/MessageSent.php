<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Message $message,
        public Conversation $conversation,
        public User $recipient,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->recipient->id)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('user');

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at?->toISOString(),
            'user' => [
                'id' => $this->message->user_id,
                'name' => $this->message->user?->name,
                'username' => $this->message->user?->username,
                'avatar_url' => $this->message->user?->avatar,
            ],
        ];
    }
}
