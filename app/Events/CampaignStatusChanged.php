<?php

namespace App\Events;

use App\Models\Campaign;
use App\Data\CampaignData;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CampaignStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public string $previousStatus,
        public string $newStatus
    ) {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('campaigns'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'campaign' => CampaignData::fromModel($this->campaign->load(['newsletterList'])),
            'previousStatus' => $this->previousStatus,
            'newStatus' => $this->newStatus,
        ];
    }
}
