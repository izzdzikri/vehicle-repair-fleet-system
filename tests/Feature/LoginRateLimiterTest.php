<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        RateLimiter::clear('badlogin@example.com|127.0.0.1');
        parent::tearDown();
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        $email = 'badlogin@example.com';

        // 5 consecutive bad attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email'    => $email,
                'password' => 'wrongpassword',
            ]);
            $response->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        }

        // 6th attempt should be locked by rate limiter
        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Too many login attempts', $errorMsg);
    }
}
