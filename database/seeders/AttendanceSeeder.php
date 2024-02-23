<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $employees = Employee::select("id")->get()->pluck("id");

        $dates = collect([]);

        for ($i = 0; $i < 1000; $i++) {
            $dates->add($this->genereateFakeAttendance());
        }

        Attendance::factory()
            ->count(600)
            ->sequence(function ($data) use ($employees, $dates) {
                $date = $dates->random();

                return [
                    "employee_id" => $employees->random(),
                    "arrived_at" => $date["arrived_at"],
                    "left_at" => $date["left_at"],
                ];
            })
            ->create();
    }

    function genereateFakeAttendance()
    {
        $faker = \Faker\Factory::create();

        $arrived_at = Carbon::parse($faker->dateTime(now()->addDay()));
        $arrived_at->year(today()->year);
        $timeJoin = $faker->randomElement(range(6, 12));
        $arrived_at->setHours($timeJoin);

        $left_at = $arrived_at->copy();
        $left_at->hours($timeJoin + $faker->randomElement(range(1, 9)));

        $chance = $faker->randomElement(range(0, 100));

        return [
            "arrived_at" => $arrived_at,
            "left_at" => ($chance >= 20) ? $left_at  :  null,
        ];
    }
}
