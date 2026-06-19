<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/employees",
     *     tags={"Employees"},
     *     summary="List all employees",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of employees")
     * )
     */
    public function index()
    {
        return Employee::all();
    }

    /**
     * @OA\Post(
     *     path="/employees",
     *     tags={"Employees"},
     *     summary="Create a new employee",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","department_id"},
     *             @OA\Property(property="name", type="string", example="Jane Smith"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="department_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Employee created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:employees,email',
            'department_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $employee = Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'department_id' => $request->department_id,
            'created_by' => $user->id
        ]);

        return response()->json($employee);
    }

    /**
     * @OA\Put(
     *     path="/employees/{id}",
     *     tags={"Employees"},
     *     summary="Update an employee",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","department_id"},
     *             @OA\Property(property="name", type="string", example="Jane Smith"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="department_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Employee Updated"),
     *     @OA\Response(response=404, description="Employee Not Found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {

            return response()->json([
                'message' => 'Employee Not Found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:employees,email,' . $id,
            'department_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'department_id' => $request->department_id
        ]);

        return response()->json([
            'message' => 'Employee Updated'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/employees/{id}",
     *     tags={"Employees"},
     *     summary="Delete an employee",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Employee Deleted"),
     *     @OA\Response(response=404, description="Employee Not Found")
     * )
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {

            return response()->json([
                'message' => 'Employee Not Found'
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'message' => 'Employee Deleted'
        ]);
    }
}
