<?php

namespace App\Mail;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeAttendanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Employee  $employee;
    public Attendance $attendance;
    public $company_name;

    public function __construct(Employee $employee, Attendance $attendance)
    {
        $this->company_name = config("app.name");
        $this->employee = $employee;
        $this->attendance = $attendance;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.employees.attendance-record')
            ->with([
                'employee' => $this->employee,
                'attendance' => $this->attendance,
                'company_name' => $this->company_name
            ]);
    }
}
