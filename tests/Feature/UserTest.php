<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_authorized(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => 'password'
        ]);
        $response->assertStatus(200);
    }

    public function test_login_unauthorized(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrongemail@admin.com',
            'password' => 'password'
        ]);
        $response->assertStatus(401);
    }
}
