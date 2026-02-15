<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class updateCalendar
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $calendarData;

    /**
     * Create a new event instance.
     */
    public function __construct($calendarData)
    {
        $this->calendarData=$calendarData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new channel('updateCalendar'),
        ];
    }
        // Event name in frontend
    public function broadcastAs()
    {
        return 'updateCalendar';
    }

    // 🔥 DATA SENT TO FRONTEND
    public function broadcastWith()
    {
        return $this->calendarData;
    }
}
