<?php

namespace App\Events;

use App\Models\Import;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ImportCompleted implements ShouldBroadcast
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
        $message = match ($this->import->status) {
            'completed' => "Import completed! {$this->import->successful_rows} subscribers imported.",
            'failed' => 'Import failed. Please check the error details.',
            default => 'Import status updated.'
        };

        return [
            'message' => $message,
            'type' => $this->import->status === 'completed' ? 'success' : 'error',
            'should_reload' => $this->import->status === 'completed',
        ];
    }
}
