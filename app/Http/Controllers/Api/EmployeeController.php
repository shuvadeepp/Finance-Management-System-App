<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index()
    {
        return Employee::all();
    }

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