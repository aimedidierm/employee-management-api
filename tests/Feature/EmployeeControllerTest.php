<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Employee;
use App\Services\EmployeeCodeGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Factory as FakerFactory;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_get_an_employee()
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee);

        $response = $this->get("/api/v1/employees/get/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data', 'status'])
            ->assertJson(['status' => Response::HTTP_OK]);
    }

    /** @test */
    public function create_an_employee()
    {
        $faker = FakerFactory::create();
        $employee = Employee::factory()->create(['position' => UserType::MANAGER->value]);

        $this->actingAs($employee);

        $employeeData = [
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'phone' => '+2507' . rand(2, 9) . substr(str_shuffle('123456789'), 1, 7),
            "code"  => EmployeeCodeGenerator::generate(),
            "national_id" => "1" . mt_rand(1970, 2004) . substr(str_shuffle('0123456789012345678901234567890123456789'), 1, 11),
            "dob" =>  $faker->date(),
            "status" => UserStatus::ACTIVE->value,
            "position" => UserType::DEVELOPER->value,
            "password" => app('hash')->make("password"),
            "created_at" => Carbon::now(),
        ];

        $response = $this->post("/api/v1/employees/create", $employeeData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['status' => Response::HTTP_OK]);
    }

    /** @test */
    public function can_delete_an_employee()
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee);

        $response = $this->delete("/api/v1/employees/delete/{$employee->code}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['status' => Response::HTTP_OK]);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }
}
