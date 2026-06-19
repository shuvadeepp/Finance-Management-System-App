<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionTest extends TestCase
{
    private function getToken(): string
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'admin@123',
        ]);
        return $response->json('token');
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getToken()];
    }

    // ✅ Test 1: List transactions
    public function test_can_list_transactions()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/transactions');

        $response->assertStatus(200)
                 ->assertJsonIsArray();
    }

    // ✅ Test 2: Without token → 401
    public function test_transactions_require_auth()
    {
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(401);
    }

    // ✅ Test 3: Create transaction success
    public function test_can_create_transaction()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->postJson('/api/transactions', [
                             'transaction_date' => '2026-06-18',
                             'amount'           => 5000,
                             'transaction_type' => 'EXPENSE',
                             'category_id'      => 4,
                             'payment_mode'     => 'UPI',
                             'description'      => 'Unit test transaction',
                         ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Transaction Created Successfully']);
    }

    // ✅ Test 4: Invalid payment_mode → 422
    public function test_create_fails_invalid_payment_mode()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->postJson('/api/transactions', [
                             'transaction_date' => '2026-06-18',
                             'amount'           => 5000,
                             'transaction_type' => 'EXPENSE',
                             'category_id'      => 4,
                             'payment_mode'     => 'BITCOIN',
                         ]);

        $response->assertStatus(422);
    }

    // ✅ Test 5: Filter by transaction_type
    public function test_filter_by_transaction_type()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/transactions?transaction_type=EXPENSE');

        $response->assertStatus(200);

        foreach ($response->json() as $txn) {
            $this->assertEquals('EXPENSE', $txn['transaction_type']);
        }
    }

    // ✅ Test 6: Filter by date range
    public function test_filter_by_date_range()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/transactions?date_from=2026-06-01&date_to=2026-06-30');

        $response->assertStatus(200);

        foreach ($response->json() as $txn) {
            $this->assertGreaterThanOrEqual('2026-06-01', $txn['transaction_date']);
            $this->assertLessThanOrEqual('2026-06-30', $txn['transaction_date']);
        }
    }

    // ✅ Test 7: Non-existing transaction → 404
    public function test_returns_404_for_missing_transaction()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->getJson('/api/transactions/99999');

        $response->assertStatus(404)
                 ->assertJson(['message' => 'Transaction Not Found']);
    }

    // ✅ Test 8: Amount required → 422
    public function test_create_fails_missing_amount()
    {
        $response = $this->withHeaders($this->authHeader())
                         ->postJson('/api/transactions', [
                             'transaction_date' => '2026-06-18',
                             'transaction_type' => 'EXPENSE',
                             'category_id'      => 4,
                         ]);

        $response->assertStatus(422);
    }
}
