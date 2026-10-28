<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_endpoint_throttles_excessive_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'invalid@kejora.gov.my',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(302);
        }

        // 6th attempt should be blocked with 429 Too Many Requests
        $response = $this->post('/login', [
            'email' => 'invalid@kejora.gov.my',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_public_kiosk_endpoint_has_throttling_applied(): void
    {
        $response = $this->get('/tv/kiosk/0000000000000000000000000000000000000000000000000000000000000000');
        $response->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_public_calendar_feed_endpoint_has_throttling_applied(): void
    {
        $response = $this->get('/calendar/feed/0000000000000000000000000000000000000000000000000000000000000000.ics');
        $response->assertHeader('X-RateLimit-Limit', '60');
    }
}

