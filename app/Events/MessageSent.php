<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk logging

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Log message untuk debugging
        Log::info('MessageSent event initialized', ['message' => $message]);

        // Validasi jika message tidak ada
        if (is_null($message)) {
            throw new \InvalidArgumentException('Message cannot be null');
        }

        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Pastikan chatroom_id valid
        if (empty($this->message->chatroom_id)) {
            throw new \Exception('Chatroom ID is not set in the message');
        }

        return new PrivateChannel('chatroom.' . $this->message->chatroom_id);
    }
}
