<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::all();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|max:255',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $project = Project::create([
            'project_name' => $request->project_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'created_by' => $user->id
        ]);

        return response()->json($project);
    }


    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {

            return response()->json([
                'message' => 'Project Not Found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|max:255',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $project->update([
            'project_name' => $request->project_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        return response()->json([
            'message' => 'Project Updated'
        ]);
    }

    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {

            return response()->json([
                'message' => 'Project Not Found'
            ], 404);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project Deleted'
        ]);
    }
}