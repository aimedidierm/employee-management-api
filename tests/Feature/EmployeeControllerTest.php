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
