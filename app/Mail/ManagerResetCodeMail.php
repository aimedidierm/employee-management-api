<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class ManagerResetCodeMail extends Mailable
{

    use Queueable, SerializesModels;

    public Employee  $manager;
    public $company_name;

    public function __construct(Employee $manager)
    {
        $this->manager = $manager;
        $this->company_name = config("app.name");
    }

    public function build()
    {
        $link = route('manager.reset_link', [
            'reset_code' => $this->manager->reset_code
        ]);

        return $this->view("email.manager.reset_code")
            ->with("manager", $this->manager)
            ->with("company_name", $this->company_name)
            ->with("link", $link);
    }
}
