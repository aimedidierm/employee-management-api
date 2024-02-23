<?php

namespace App\Jobs;

use App\Mail\EmployeeAttendanceMail;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmployeeAttendanceMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Employee $employee;
    public Attendance $attendance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Employee $employee, Attendance $attendance)
    {
        $this->employee = $employee;
        $this->attendance = $attendance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Mail::to($this->employee->email)
            ->send(new EmployeeAttendanceMail($this->employee, $this->attendance));
    }
}
