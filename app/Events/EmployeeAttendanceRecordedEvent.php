<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeAttendanceRecordedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Employee $employee;
    public Attendance $attendance;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Employee $employee, Attendance $attendance)
    {
        $this->employee = $employee;
        $this->attendance = $attendance;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
