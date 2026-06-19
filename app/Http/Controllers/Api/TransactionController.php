<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $query = Transaction::with('category')
            ->where('created_by', $user->id);

        // Filter: date range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Filter: category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter: transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', strtoupper($request->transaction_type));
        }

        $transactions = $query->orderBy('transaction_date', 'DESC')->get()->values();

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date'  => 'required|date',
            'amount'            => 'required|numeric|min:0.01',
            'transaction_type'  => 'required|in:INCOME,EXPENSE',
            'category_id'       => 'required|integer',
            'payment_mode'      => 'nullable|in:CASH,CARD,UPI,NET_BANKING,OTHER',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $transaction = Transaction::create([
            'transaction_date' => $request->transaction_date,
            'amount'           => $request->amount,
            'transaction_type' => $request->transaction_type,
            'description'      => $request->description,
            'payment_mode'     => $request->payment_mode ?? 'CASH',
            'category_id'      => $request->category_id,
            'created_by'       => $user->id,
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'TRANSACTION_CREATED',
            'description' => 'Transaction created: ' . $request->transaction_type . ' of ' . $request->amount,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Transaction Created Successfully',
            'data'    => $transaction,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_type' => 'required|in:INCOME,EXPENSE',
            'category_id'      => 'required|integer',
            'payment_mode'     => 'nullable|in:CASH,CARD,UPI,NET_BANKING,OTHER',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $transaction->update([
            'transaction_date' => $request->transaction_date,
            'amount'           => $request->amount,
            'transaction_type' => $request->transaction_type,
            'description'      => $request->description,
            'payment_mode'     => $request->payment_mode ?? $transaction->payment_mode,
            'category_id'      => $request->category_id,
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'TRANSACTION_UPDATED',
            'description' => 'Transaction ID ' . $id . ' updated',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json(['message' => 'Transaction Updated Successfully']);
    }

    public function destroy(int $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction Deleted Successfully']);
    }

    public function show(int $id)
    {
        $transaction = Transaction::with('category')->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        return response()->json($transaction);
    }
}
