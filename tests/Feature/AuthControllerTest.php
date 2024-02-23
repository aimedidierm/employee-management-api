<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_login()
    {
        $password = 'password';
        $employee = Employee::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $employee->email,
            'password' => $password,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'position',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'status',
            ]);
    }

    /** @test */
    public function can_signup()
    {
        $password = 'password';
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+2507867890',
            'national_id' => '1234567890123',
            'dob' => Carbon::now()->subYears(30)->format('Y-m-d'),
            'password' => $password,
        ];

        $response = $this->postJson('/api/v1/auth/signup', $employeeData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'position',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
                'status',
            ]);
    }

    /** @test */
    public function can_logout()
    {
        $employee = Employee::factory()->create();
        $token = $employee->createToken('API Token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Logout success',
                'status' => Response::HTTP_OK,
            ]);

        $this->assertCount(0, $employee->tokens);
    }
}
