<?php

namespace Tests\Feature\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function register_attendance_successfully()
    {
        $employee = Employee::factory()->create();

        $this->actingAs($employee);

        $response = $this->postJson('/api/v1/employees/register-attendance', [
            'employee_id' => $employee->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'message' => 'Attendance successfully registered',
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
        ]);
    }

    /** @test */
    public function get_attendance_successfully()
    {
        $employee = Employee::factory()->create();
        $this->actingAs($employee);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->getJson('/api/v1/attendances', [
            'from' => now()->subDays(7)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'date',
                        'data' => [
                            '*' => [
                                'time_arrived_at',
                                'time_left_at',
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
