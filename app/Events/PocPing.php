<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PocPing implements ShouldBroadcastNow
{
    public string $message;
    public string $time;

    public function __construct(string $message = 'pong')
    {
        $this->message = $message;
        $this->time = now()->toISOString();
    }

    public function broadcastOn(): array
    {
        return [new Channel('poc')];
    }

    public function broadcastAs(): string
    {
        return 'PocPing';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'time' => $this->time,
        ];
    }
}

