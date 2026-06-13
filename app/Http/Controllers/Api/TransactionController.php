<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TransactionController extends Controller
{
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $transactions = Transaction::where('created_by', $user->id)
            ->get()
            ->values();  // ✅ re-indexes to [0, 1, 2, ...]
        
        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'transaction_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'transaction_type' => 'required|in:INCOME,EXPENSE',
                'category_id' => 'required|integer'
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $transaction = Transaction::create([

            'transaction_date' => $request->transaction_date,

            'amount' => $request->amount,

            'transaction_type' => $request->transaction_type,

            'description' => $request->description,

            'category_id' => $request->category_id,

            'created_by' => $user->id
        ]);

        return response()->json([
            'message' => 'Transaction Created Successfully',
            'data' => $transaction
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {

            return response()->json([
                'message' => 'Transaction Not Found'
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'transaction_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'transaction_type' => 'required|in:INCOME,EXPENSE',
                'category_id' => 'required|integer'
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction->update([

            'transaction_date' => $request->transaction_date,

            'amount' => $request->amount,

            'transaction_type' => $request->transaction_type,

            'description' => $request->description,

            'category_id' => $request->category_id
        ]);

        return response()->json([
            'message' => 'Transaction Updated Successfully'
        ]);
    }

    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {

            return response()->json([
                'message' => 'Transaction Not Found'
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction Deleted Successfully'
        ]);
    }

    public function show($id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {

            return response()->json([
                'message' => 'Transaction Not Found'
            ], 404);
        }

        return $transaction;
    }
}