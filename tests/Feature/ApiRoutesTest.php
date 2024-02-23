<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function returns_api_root()
    {
        $response = $this->get('/api/v1');

        $response->assertStatus(200);
    }
}
