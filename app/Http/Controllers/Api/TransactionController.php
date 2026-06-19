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
    /**
     * @OA\Get(
     *     path="/transactions",
     *     tags={"Transactions"},
     *     summary="List transactions for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date", example="2024-01-01")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date", example="2024-12-31")),
     *     @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="transaction_type", in="query", required=false, @OA\Schema(type="string", enum={"INCOME","EXPENSE"})),
     *     @OA\Response(response=200, description="List of transactions")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/transactions",
     *     tags={"Transactions"},
     *     summary="Create a new transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_date","amount","transaction_type","category_id"},
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-06-01"),
     *             @OA\Property(property="amount", type="number", format="float", example=500.00),
     *             @OA\Property(property="transaction_type", type="string", enum={"INCOME","EXPENSE"}),
     *             @OA\Property(property="category_id", type="integer", example=2),
     *             @OA\Property(property="description", type="string", example="Monthly salary"),
     *             @OA\Property(property="payment_mode", type="string", enum={"CASH","CARD","UPI","NET_BANKING","OTHER"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transaction Created Successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Update a transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_date","amount","transaction_type","category_id"},
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-06-01"),
     *             @OA\Property(property="amount", type="number", format="float", example=500.00),
     *             @OA\Property(property="transaction_type", type="string", enum={"INCOME","EXPENSE"}),
     *             @OA\Property(property="category_id", type="integer", example=2),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="payment_mode", type="string", enum={"CASH","CARD","UPI","NET_BANKING","OTHER"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transaction Updated Successfully"),
     *     @OA\Response(response=404, description="Transaction Not Found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Delete a transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Transaction Deleted Successfully"),
     *     @OA\Response(response=404, description="Transaction Not Found")
     * )
     */
    public function destroy(int $id)
    {
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction Deleted Successfully']);
    }

    /**
     * @OA\Get(
     *     path="/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Get a transaction by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Transaction details"),
     *     @OA\Response(response=404, description="Transaction Not Found")
     * )
     */
    public function show(int $id)
    {
        $transaction = Transaction::with('category')->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction Not Found'], 404);
        }

        return response()->json($transaction);
    }
}
