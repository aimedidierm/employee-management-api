<?php

namespace App\Jobs;

use App\Mail\ManagerResetCodeMail;
use App\Models\Employee;
use Illuminate\Support\Facades\Mail;

class SendResetLinkToManagerJob extends Job
{
    public Employee $manager;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Employee $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->manager)
            ->send(new ManagerResetCodeMail($this->manager));
    }
}
