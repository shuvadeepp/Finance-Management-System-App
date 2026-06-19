<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    private function authHeader(): array
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin@123',
        ]);
        return ['Authorization' => 'Bearer ' . $response->json('token')];
    }

    // ✅ Test 1: Dashboard returns 200
    public function test_dashboard_returns_ok()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/dashboard');

        $response->assertStatus(200);
    }

    // ✅ Test 2: Dashboard has all required keys
    public function test_dashboard_has_required_structure()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'source',
                     'data' => [
                         'financial_overview' => [
                             'total_income',
                             'total_expense',
                             'net_savings',
                             'savings_percentage',
                         ],
                         'top_spending_categories',
                         'spending_breakdown',
                         'monthly_chart',
                         'budget_progress',
                         'recent_transactions',
                     ],
                 ]);
    }

    // ✅ Test 3: net_savings = total_income - total_expense
    public function test_net_savings_is_correct()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/dashboard');

        $data     = $response->json('data.financial_overview');
        $expected = $data['total_income'] - $data['total_expense'];

        $this->assertEquals($expected, $data['net_savings']);
    }

    // ✅ Test 4: Second call source = redis (cache hit)
    public function test_second_dashboard_call_is_cached()
    {
        $headers = $this->authHeader();

        // First call — DB se
        $this->withHeaders($headers)->getJson('/api/dashboard');

        // Second call — Redis se
        $response = $this->withHeaders($headers)->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson(['source' => 'redis']);
    }

    // ✅ Test 5: Without token → 401
    public function test_dashboard_requires_auth()
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }
}
