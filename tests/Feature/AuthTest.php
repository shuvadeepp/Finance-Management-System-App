<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    // ✅ Test 1: Register success
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'testuser_' . time(),
            'password' => 'test@1234',
            'role'     => 'EMPLOYEE',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'username', 'role'],
                 ]);
    }

    // ✅ Test 2: Duplicate username → 422
    public function test_register_fails_duplicate_username()
    {
        $this->postJson('/api/register', [
            'username' => 'dup_user_test',
            'password' => 'test@1234',
            'role'     => 'EMPLOYEE',
        ]);

        $response = $this->postJson('/api/register', [
            'username' => 'dup_user_test',
            'password' => 'test@1234',
            'role'     => 'EMPLOYEE',
        ]);

        $response->assertStatus(422);
    }

    // ✅ Test 3: Invalid role → 422
    public function test_register_fails_invalid_role()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'roletest_' . time(),
            'password' => 'test@1234',
            'role'     => 'SUPERADMIN',
        ]);

        $response->assertStatus(422);
    }

    // ✅ Test 4: Login success
    public function test_user_can_login()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin@123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    // ✅ Test 5: Wrong password → 401
    public function test_login_fails_wrong_password()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid Password']);
    }

    // ✅ Test 6: Wrong username → 401
    public function test_login_fails_wrong_username()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'nonexistentuser',
            'password' => 'test@1234',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid Username']);
    }

    // ✅ Test 7: Logout success
    public function test_user_can_logout()
    {
        $login = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin@123',
        ]);

        $token = $login->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }

    // ✅ Test 8: Logout without token → 401
    public function test_logout_requires_token()
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }
}
