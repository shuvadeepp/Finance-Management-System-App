<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redis;

class CategoryController extends Controller
{
    private const CACHE_KEY = 'categories_all';
    private const CACHE_TTL = 3600;

    public function index()
    {
        $cachedData = Redis::get(self::CACHE_KEY);

        if ($cachedData !== null) {
            Log::info('Category cache HIT');
            return response()->json([
                'source' => 'redis',
                'data'   => json_decode($cachedData, true),
            ]);
        }

        Log::info('Category cache MISS');

        $categories = Category::whereNull('deleted_at')->get();

        Redis::setex(self::CACHE_KEY, self::CACHE_TTL, $categories->toJson());

        return response()->json([
            'source' => 'database',
            'data'   => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => ['required', 'max:100'],
            'category_type' => ['required', 'in:INCOME,EXPENSE'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $categoryExists = Category::where('category_name', $request->category_name)
            ->where('category_type', $request->category_type)
            ->whereNull('deleted_at')
            ->exists();

        if ($categoryExists) {
            return response()->json(['message' => 'Category already exists'], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $category = Category::create([
            'category_name' => $request->category_name,
            'category_type' => $request->category_type,
            'is_default'    => 0,
            'created_by'    => $user->id,
        ]);

        Redis::del(self::CACHE_KEY);

        return response()->json([
            'message' => 'Category Created Successfully',
            'data'    => $category,
        ]);
    }

    public function show(int $id)
    {
        $category = Category::whereNull('deleted_at')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category Not Found'], 404);
        }

        return response()->json($category);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::whereNull('deleted_at')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category Not Found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_name' => ['required', 'max:100'],
            'category_type' => ['required', 'in:INCOME,EXPENSE'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $duplicate = Category::where('category_name', $request->category_name)
            ->where('category_type', $request->category_type)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'Category already exists'], 422);
        }

        $category->update([
            'category_name' => $request->category_name,
            'category_type' => $request->category_type,
        ]);

        Redis::del(self::CACHE_KEY);

        return response()->json(['message' => 'Category Updated Successfully']);
    }

    public function destroy(int $id)
    {
        $category = Category::whereNull('deleted_at')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category Not Found'], 404);
        }

        if ($category->is_default == 1) {
            return response()->json(['message' => 'Default Category Cannot Be Deleted'], 422);
        }

        $category->delete();

        Redis::del(self::CACHE_KEY);

        return response()->json(['message' => 'Category Deleted Successfully']);
    }

    public function defaultCategories()
    {
        return Category::where('is_default', 1)
            ->whereNull('deleted_at')
            ->orderBy('category_name', 'ASC')
            ->get();
    }
}
