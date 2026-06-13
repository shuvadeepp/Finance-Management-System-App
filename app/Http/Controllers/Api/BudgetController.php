<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        // echo 999;exit;
        $user = JWTAuth::parseToken()->authenticate();

        // dd($user->id);

        return DB::table('budgets as b')

            ->join(
                'categories as c',
                'c.id',
                '=',
                'b.category_id'
            )

            ->select(
                'b.*',
                'c.category_name'
            )

            ->where('b.created_by', $user->id)  // ✅ filter by logged-in user

            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([

            'category_id' => 'required',

            'budget_month' => 'required|integer|min:1|max:12',

            'budget_year' => 'required|integer',

            'budget_amount' => 'required|numeric|min:1'
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        Budget::create([

            'category_id' => $request->category_id,

            'budget_month' => $request->budget_month,

            'budget_year' => $request->budget_year,

            'budget_amount' => $request->budget_amount,

            'created_by' => $user->id
        ]);

        return response()->json([
            'message' => 'Budget Saved Successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::find($id);

        if (!$budget) {

            return response()->json([
                'message' => 'Budget Not Found'
            ], 404);
        }

        $request->validate([

            'category_id' => 'required',

            'budget_month' => 'required',

            'budget_year' => 'required',

            'budget_amount' => 'required|numeric|min:1'
        ]);

        $budget->update([

            'category_id' => $request->category_id,

            'budget_month' => $request->budget_month,

            'budget_year' => $request->budget_year,

            'budget_amount' => $request->budget_amount
        ]);

        return response()->json([
            'message' => 'Budget Updated Successfully'
        ]);
    }

    public function destroy($id)
    {
        $budget = Budget::find($id);

        if (!$budget) {

            return response()->json([
                'message' => 'Budget Not Found'
            ], 404);
        }

        $budget->delete();

        return response()->json([
            'message' => 'Budget Deleted Successfully'
        ]);
    }

    public function budgetTracking()
    {
        $month = date('m');

        $year = date('Y');

        $user = JWTAuth::parseToken()->authenticate();

        return DB::table('budgets as b')

            ->join(
                'categories as c',
                'c.id',
                '=',
                'b.category_id'
            )

            ->leftJoin(
                'transactions as t',
                't.category_id',
                '=',
                'b.category_id'
            )

          /*   ->where('b.budget_month', $month)

            ->where('b.budget_year', $year) */

            ->select(

                'c.category_name',

                'b.budget_amount',

                DB::raw("
                    SUM(
                        CASE
                        WHEN t.transaction_type='EXPENSE'
                        THEN t.amount
                        ELSE 0
                        END
                    ) as spent_amount
                ")
            )

            ->where('b.created_by', $user->id)  // ✅ filter by logged-in user

            ->groupBy(
                'c.category_name',
                'b.budget_amount'
            )
            

            ->get();
    }

    public function overview()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $totalBudget = Budget::sum('budget_amount');

        $totalExpense = DB::table('transactions')
            ->where('transaction_type', 'EXPENSE')
            ->where('created_by', $user->id)

            ->sum('amount');

        return response()->json([

            'total_budget' => $totalBudget,

            'total_expense' => $totalExpense,

            'remaining' => $totalBudget - $totalExpense
        ]);
    }

    
}