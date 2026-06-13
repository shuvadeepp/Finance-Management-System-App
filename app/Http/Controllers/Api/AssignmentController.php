<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProject;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AssignmentController extends Controller
{
    public function assign(Request $request)
    {
        EmployeeProject::create([
            'employee_id' => $request->employee_id,
            'project_id' => $request->project_id,
            'assigned_date' => now()
        ]);

        return response()->json([
            'message' => 'Project Assigned'
        ]);
    }

    /* public function assignedProjects()
    {
        return DB::table('employee_projects')
            ->join('employees', 'employees.id', '=', 'employee_projects.employee_id')
            ->join('projects', 'projects.id', '=', 'employee_projects.project_id')
            ->select(
                'employees.name',
                'projects.project_name',
                'employee_projects.assigned_date'
            )
            ->get();
    } */

        public function assignedProjects()
        {
            $user = JWTAuth::parseToken()->authenticate();

            // ADMIN & MANAGER SEE ALL

            if (
                $user->role == 'ADMIN' ||
                $user->role == 'MANAGER'
            ) {

                return DB::table('employee_projects')

                    ->join(
                        'employees',
                        'employees.id',
                        '=',
                        'employee_projects.employee_id'
                    )

                    ->join(
                        'projects',
                        'projects.id',
                        '=',
                        'employee_projects.project_id'
                    )

                    ->select(
                        'employees.name',
                        'projects.project_name',
                        'employee_projects.assigned_date'
                    )

                    ->get();
            }

            // EMPLOYEE SEE ONLY OWN PROJECTS

            return DB::table('employee_projects')

                ->join(
                    'employees',
                    'employees.id',
                    '=',
                    'employee_projects.employee_id'
                )

                ->join(
                    'projects',
                    'projects.id',
                    '=',
                    'employee_projects.project_id'
                )

                ->where(
                    'employees.name',
                    $user->username
                )

                ->select(
                    'employees.name',
                    'projects.project_name',
                    'employee_projects.assigned_date'
                )

                ->get();
        }
}