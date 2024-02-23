<?php

namespace App\Listeners;

use App\Events\EmployeeAttendanceRecordedEvent;
use App\Jobs\SendEmployeeAttendanceMailJob;

class SendEmailEmployeeAttendanceNotification
{


    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EmployeeAttendanceRecordedEvent  $event
     * @return void
     */
    public function handle(EmployeeAttendanceRecordedEvent $event)
    {
        dispatch(new SendEmployeeAttendanceMailJob($event->employee, $event->attendance));
    }
}
