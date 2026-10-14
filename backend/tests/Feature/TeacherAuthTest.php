<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_login_read_profile_and_revoke_token(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $login = $this->postJson('/api/teacher/login', ['email' => $teacher->email, 'password' => 'password']);
        $login->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonMissingPath('user.password');
        $headers = ['Authorization' => 'Bearer '.$login->json('token')];
        $this->getJson('/api/user', $headers)->assertOk()->assertJsonPath('id', $teacher->id);
        $this->postJson('/api/teacher/logout', [], $headers)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_password_unknown_user_and_admin_cannot_login(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([[$teacher->email, 'wrong'], ['missing@example.com', 'password'], [$admin->email, 'password']] as [$email, $password]) {
            $this->postJson('/api/teacher/login', compact('email', 'password'))
                ->assertUnauthorized()->assertJsonPath('message', 'Invalid teacher credentials.');
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requires_valid_input_and_protected_routes_require_token(): void
    {
        $this->postJson('/api/teacher/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/teacher/logout')->assertUnauthorized();
    }

    public function test_expired_token_cannot_access_profile(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('expired', ['teacher'], now()->subMinute())->plainTextToken;
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_repeated_login_attempts_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/teacher/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/teacher/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
