<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
 
class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard Data
    |--------------------------------------------------------------------------
    */
 
    public function index()
    {
        $user = JWTAuth::parseToken()
            ->authenticate();
 
        $month = date('m');
        $year  = date('Y');
 
        /*
        |--------------------------------------------------------------------------
        | Financial Overview
        |--------------------------------------------------------------------------
        */
 
        $totalIncome = DB::table('transactions')
            ->where('created_by', $user->id)
            ->where('transaction_type', 'INCOME')
            // ->whereMonth(
            //     'transaction_date',
            //     $month
            // )
            // ->whereYear(
            //     'transaction_date',
            //     $year
            // )
            ->sum('amount');
 
        $totalExpense = DB::table('transactions')
            ->where('created_by', $user->id)
            ->where('transaction_type', 'EXPENSE')
            // ->whereMonth(
            //     'transaction_date',
            //     $month
            // )
            // ->whereYear(
            //     'transaction_date',
            //     $year
            // )
            ->sum('amount');
 
        /*
        |--------------------------------------------------------------------------
        | Spending Breakdown
        |--------------------------------------------------------------------------
        */
 
        $spendingBreakdown = DB::table('transactions as t')
            ->join(
                'categories as c',
                'c.id',
                '=',
                't.category_id'
            )
            ->where(
                't.created_by',
                $user->id
            )
            ->where(
                't.transaction_type',
                'EXPENSE'
            )
            // ->whereMonth(
            //     't.transaction_date',
            //     $month
            // )
            // ->whereYear(
            //     't.transaction_date',
            //     $year
            // )
            ->select(
                'c.category_name',
                DB::raw(
                    'SUM(t.amount) as amount'
                )
            )
            ->groupBy(
                'c.category_name'
            )
            ->get();
 
        /*
        |--------------------------------------------------------------------------
        | Budget Progress
        |--------------------------------------------------------------------------
        */
 
        $budgetProgress = DB::table('budgets as b')
            ->join(
                'categories as c',
                'c.id',
                '=',
                'b.category_id'
            )
            ->leftJoin(
                'transactions as t',
                function ($join) use (
                    $user,
                    $month,
                    $year
                ) {
 
                    $join->on(
                        't.category_id',
                        '=',
                        'b.category_id'
                    )
                    ->where(
                        't.created_by',
                        '=',
                        $user->id
                    )
                    ->where(
                        't.transaction_type',
                        '=',
                        'EXPENSE'
                    )
                    // ->whereMonth(
                    //     't.transaction_date',
                    //     '=',
                    //     $month
                    // )
                    // ->whereYear(
                    //     't.transaction_date',
                    //     '=',
                    //     $year
                    // )
                    ;
                }
            )
            ->where(
                'b.created_by',
                $user->id
            )
            ->where(
                'b.budget_month',
                $month
            )
            ->where(
                'b.budget_year',
                $year
            )
            ->select(
                'c.category_name',
                'b.budget_amount',
                DB::raw(
                    'COALESCE(SUM(t.amount),0) as spent'
                ),
                DB::raw(
                    'b.budget_amount -
                     COALESCE(SUM(t.amount),0)
                     as remaining'
                )
            )
            ->groupBy(
                'c.category_name',
                'b.budget_amount'
            )
            ->get();
 
        /*
        |--------------------------------------------------------------------------
        | Recent Transactions
        |--------------------------------------------------------------------------
        */
 
        $recentTransactions = DB::table('transactions as t')
            ->join(
                'categories as c',
                'c.id',
                '=',
                't.category_id'
            )
            ->where(
                't.created_by',
                $user->id
            )
            ->select(
                't.id',
                't.transaction_date',
                't.amount',
                't.transaction_type',
                't.description',
                'c.category_name'
            )
            ->orderBy(
                't.transaction_date',
                'DESC'
            )
            ->limit(5)
            ->get();
 
        return response()->json([
 
            'financial_overview' => [
 
                'total_income' => $totalIncome,
 
                'total_expense' => $totalExpense,
 
                'net_balance' =>
                    $totalIncome - $totalExpense
            ],
 
            'spending_breakdown' =>
                $spendingBreakdown,
 
            'budget_progress' =>
                $budgetProgress,
 
            'recent_transactions' =>
                $recentTransactions
        ]);
    }
}