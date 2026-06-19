<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;
use App\Models\AuditLog;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();

        return DB::table('budgets as b')
            ->join('categories as c', 'c.id', '=', 'b.category_id')
            ->select('b.*', 'c.category_name')
            ->where('b.created_by', $user->id)
            ->whereNull('b.deleted_at')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'   => 'required|integer',
            'budget_month'  => 'required|integer|min:1|max:12',
            'budget_year'   => 'required|integer|min:2000',
            'budget_amount' => 'required|numeric|min:1',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        Budget::create([
            'category_id'   => $request->category_id,
            'budget_month'  => $request->budget_month,
            'budget_year'   => $request->budget_year,
            'budget_amount' => $request->budget_amount,
            'created_by'    => $user->id,
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'BUDGET_CREATED',
            'description' => 'Budget set for category ' . $request->category_id . ' - Amount: ' . $request->budget_amount,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json(['message' => 'Budget Saved Successfully']);
    }

    public function update(Request $request, int $id)
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return response()->json(['message' => 'Budget Not Found'], 404);
        }

        $request->validate([
            'category_id'   => 'required|integer',
            'budget_month'  => 'required|integer|min:1|max:12',
            'budget_year'   => 'required|integer|min:2000',
            'budget_amount' => 'required|numeric|min:1',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $budget->update([
            'category_id'   => $request->category_id,
            'budget_month'  => $request->budget_month,
            'budget_year'   => $request->budget_year,
            'budget_amount' => $request->budget_amount,
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'BUDGET_UPDATED',
            'description' => 'Budget ID ' . $id . ' updated to amount: ' . $request->budget_amount,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json(['message' => 'Budget Updated Successfully']);
    }

    public function destroy(int $id)
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return response()->json(['message' => 'Budget Not Found'], 404);
        }

        $budget->delete();

        return response()->json(['message' => 'Budget Deleted Successfully']);
    }

    public function budgetTracking()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $rows = DB::table('budgets as b')
            ->join('categories as c', 'c.id', '=', 'b.category_id')
            ->leftJoin('transactions as t', function ($join) use ($user) {
                $join->on('t.category_id', '=', 'b.category_id')
                    ->where('t.created_by', '=', $user->id)
                    ->where('t.transaction_type', '=', 'EXPENSE')
                    ->whereNull('t.deleted_at');
            })
            ->where('b.created_by', $user->id)
            ->whereNull('b.deleted_at')
            ->select(
                'c.category_name',
                'b.budget_amount',
                DB::raw("SUM(CASE WHEN t.transaction_type='EXPENSE' THEN t.amount ELSE 0 END) as spent_amount")
            )
            ->groupBy('c.category_name', 'b.budget_amount')
            ->get();

        // Add status to each row
        $rows = $rows->map(function ($row) {
            $percentage = $row->budget_amount > 0
                ? ($row->spent_amount / $row->budget_amount) * 100
                : 0;

            $row->percentage = round($percentage, 2);
            $row->status     = $this->getBudgetStatus($percentage);

            return $row;
        });

        return response()->json($rows);
    }

    public function overview()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $totalBudget = Budget::where('created_by', $user->id)->sum('budget_amount');

        $totalExpense = DB::table('transactions')
            ->where('transaction_type', 'EXPENSE')
            ->where('created_by', $user->id)
            ->whereNull('deleted_at')
            ->sum('amount');

        $remaining  = $totalBudget - $totalExpense;
        $percentage = $totalBudget > 0 ? ($totalExpense / $totalBudget) * 100 : 0;

        return response()->json([
            'budget'    => $totalBudget,
            'spent'     => $totalExpense,
            'remaining' => $remaining,
            'status'    => $this->getBudgetStatus($percentage),
        ]);
    }

    private function getBudgetStatus(float $percentage): string
    {
        if ($percentage > 100) return 'Exceeded';
        if ($percentage >= 70) return 'Warning';
        return 'Safe';
    }
}
