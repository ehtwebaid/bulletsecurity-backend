<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class forceLogout implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $userData;
    // Constructor for event initialization
 public function __construct($userData)
{
    $this->userData=$userData;
}

// Define which channel the event will be broadcast on
public function broadcastOn(): array
{
    return [
        new Channel('forceLogout'), // Channel name where the event will be broadcast
    ];
}

// Define the event name for frontend listeners
public function broadcastAs()
{
    return 'forceLogoutData';  // The event name that the frontend listens for
}

// Pass meaningful data to the frontend
public function broadcastWith()
{
     return $this->userData;
}
}

