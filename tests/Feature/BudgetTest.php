<?php

namespace Tests\Feature;

use Tests\TestCase;

class BudgetTest extends TestCase
{
    private function authHeader(): array
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin@123',
        ]);
        return ['Authorization' => 'Bearer ' . $response->json('token')];
    }

    // ✅ Test 1: Create budget success
    public function test_can_create_budget()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->postJson('/api/budgets', [
                             'category_id'   => 4,
                             'budget_month'  => 6,
                             'budget_year'   => 2026,
                             'budget_amount' => 10000,
                         ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Budget Saved Successfully']);
    }

    // ✅ Test 2: List budgets
    public function test_can_list_budgets()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/budgets');

        $response->assertStatus(200)
                 ->assertJsonIsArray();
    }

    // ✅ Test 3: Budget overview has required keys
    public function test_budget_overview_has_correct_structure()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/budget-overview');

        $response->assertStatus(200)
                 ->assertJsonStructure(['budget', 'spent', 'remaining', 'status']);
    }

    // ✅ Test 4: Budget overview status is valid value
    public function test_budget_overview_status_is_valid()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/budget-overview');

        $status = $response->json('status');
        $this->assertContains($status, ['Safe', 'Warning', 'Exceeded']);
    }

    // ✅ Test 5: Budget tracking returns percentage and status
    public function test_budget_tracking_has_status_and_percentage()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/budget-tracking');

        $response->assertStatus(200);

        foreach ($response->json() as $row) {
            $this->assertArrayHasKey('percentage', $row);
            $this->assertArrayHasKey('status', $row);
            $this->assertContains($row['status'], ['Safe', 'Warning', 'Exceeded']);
        }
    }

    // ✅ Test 6: Budget without token → 401
    public function test_budget_requires_auth()
    {
        $response = $this->getJson('/api/budgets');
        $response->assertStatus(401);
    }

    // ✅ Test 7: Invalid month → 422
    public function test_create_fails_invalid_month()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->postJson('/api/budgets', [
                             'category_id'   => 4,
                             'budget_month'  => 13,
                             'budget_year'   => 2026,
                             'budget_amount' => 10000,
                         ]);

        $response->assertStatus(422);
    }
}
