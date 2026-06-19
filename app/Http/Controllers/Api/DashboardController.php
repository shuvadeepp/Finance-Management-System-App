<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = JWTAuth::parseToken()->authenticate();
        $cacheKey = 'dashboard_user_' . $user->id;

        // Redis cache check
        $cached = Redis::get($cacheKey);
        if ($cached !== null) {
            Log::info('Dashboard cache HIT', ['user_id' => $user->id]);
            return response()->json([
                'source' => 'redis',
                'data'   => json_decode($cached, true),
            ]);
        }

        Log::info('Dashboard cache MISS', ['user_id' => $user->id]);

        // ── Financial Overview ──────────────────────────────────────────────
        $totalIncome = DB::table('transactions')
            ->where('created_by', $user->id)
            ->where('transaction_type', 'INCOME')
            ->whereNull('deleted_at')
            ->sum('amount');

        $totalExpense = DB::table('transactions')
            ->where('created_by', $user->id)
            ->where('transaction_type', 'EXPENSE')
            ->whereNull('deleted_at')
            ->sum('amount');

        $netSavings        = $totalIncome - $totalExpense;
        $savingsPercentage = $totalIncome > 0
            ? round(($netSavings / $totalIncome) * 100, 2)
            : 0;

        // ── Top Spending Categories ─────────────────────────────────────────
        $topSpendingCategories = DB::table('transactions as t')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.created_by', $user->id)
            ->where('t.transaction_type', 'EXPENSE')
            ->whereNull('t.deleted_at')
            ->select('c.category_name', DB::raw('SUM(t.amount) as total_spent'))
            ->groupBy('c.category_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // ── Spending Breakdown (Expense by Category) ────────────────────────
        $spendingBreakdown = DB::table('transactions as t')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.created_by', $user->id)
            ->where('t.transaction_type', 'EXPENSE')
            ->whereNull('t.deleted_at')
            ->select('c.category_name', DB::raw('SUM(t.amount) as amount'))
            ->groupBy('c.category_name')
            ->get();

        // ── Monthly Income vs Expense (last 6 months) ───────────────────────
        $monthlyData = DB::table('transactions')
            ->where('created_by', $user->id)
            ->whereNull('deleted_at')
            ->whereRaw('transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)')
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN transaction_type='INCOME' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN transaction_type='EXPENSE' THEN amount ELSE 0 END) as expense")
            )
            ->groupBy(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"))
            ->get();

        // ── Budget Progress (current month) ────────────────────────────────
        $budgetProgress = DB::table('budgets as b')
            ->join('categories as c', 'c.id', '=', 'b.category_id')
            ->leftJoin('transactions as t', function ($join) use ($user) {
                $join->on('t.category_id', '=', 'b.category_id')
                    ->where('t.created_by', '=', $user->id)
                    ->where('t.transaction_type', '=', 'EXPENSE')
                    ->whereNull('t.deleted_at');
            })
            ->where('b.created_by', $user->id)
            ->where('b.budget_month', (int) date('m'))
            ->where('b.budget_year', (int) date('Y'))
            ->whereNull('b.deleted_at')
            ->select(
                'c.category_name',
                'b.budget_amount',
                DB::raw('COALESCE(SUM(t.amount),0) as spent'),
                DB::raw('b.budget_amount - COALESCE(SUM(t.amount),0) as remaining')
            )
            ->groupBy('c.category_name', 'b.budget_amount')
            ->get()
            ->map(function ($row) {
                $pct = $row->budget_amount > 0
                    ? ($row->spent / $row->budget_amount) * 100
                    : 0;
                $row->percentage = round($pct, 2);
                $row->status     = $this->getBudgetStatus($pct);
                return $row;
            });

        // ── Budget Utilization (for chart) ──────────────────────────────────
        $budgetUtilization = $budgetProgress->map(fn($row) => [
            'category'       => $row->category_name,
            'budget'         => $row->budget_amount,
            'spent'          => $row->spent,
            'percentage'     => $row->percentage,
            'status'         => $row->status,
        ])->values();

        // ── Recent Transactions ─────────────────────────────────────────────
        $recentTransactions = DB::table('transactions as t')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.created_by', $user->id)
            ->whereNull('t.deleted_at')
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.transaction_type',
                't.description',
                't.payment_mode',
                'c.category_name'
            )
            ->orderByDesc('t.transaction_date')
            ->limit(5)
            ->get();

        $data = [
            'financial_overview' => [
                'total_income'       => $totalIncome,
                'total_expense'      => $totalExpense,
                'net_savings'        => $netSavings,
                'net_balance'        => $netSavings,
                'savings_percentage' => $savingsPercentage,
            ],
            'top_spending_categories' => $topSpendingCategories,
            'spending_breakdown'      => $spendingBreakdown,
            'monthly_chart'           => $monthlyData,
            'budget_progress'         => $budgetProgress,
            'budget_utilization'      => $budgetUtilization,
            'recent_transactions'     => $recentTransactions,
        ];

        // Cache for 5 minutes
        Redis::setex($cacheKey, 300, json_encode($data));

        return response()->json([
            'source' => 'database',
            'data'   => $data,
        ]);
    }

    private function getBudgetStatus(float $percentage): string
    {
        if ($percentage > 100) return 'Exceeded';
        if ($percentage >= 70) return 'Warning';
        return 'Safe';
    }
}
