<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = \Faker\Factory::create();
        return [
            "arrived_at" => Carbon::parse($faker->dateTime(now()->addDay())),
            "left_at" => Carbon::parse($faker->dateTime(now()->addDay())),
        ];
    }
}
