<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Employee;
use App\Services\EmployeeCodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use ReflectionClass;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '+2507' . rand(2, 9) . substr(str_shuffle('123456789'), 1, 7),
            "code"  => EmployeeCodeGenerator::generate(),
            "national_id" => "1" . mt_rand(1970, 2004) . substr(str_shuffle('0123456789012345678901234567890123456789'), 1, 11),
            "dob" =>  $this->faker->date(),
            "status" => $this->faker->randomElement(array_values((new ReflectionClass(UserStatus::class))->getConstants())),
            "position" => $this->faker->randomElement(array_values((new ReflectionClass(UserType::class))->getConstants())),
            "password" => app('hash')->make("password"),
            "created_at" => Carbon::now(),
        ];
    }
}
