<?php

namespace App\Events;

use App\Models\Import;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ImportStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Import $import
    ) {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('imports'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'Import started successfully!',
            'type' => 'success',
            'should_reload' => false,
        ];
    }
}
