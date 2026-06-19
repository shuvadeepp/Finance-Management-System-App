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

    /**
     * @OA\Get(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="List all categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Categories list",
     *         @OA\JsonContent(
     *             @OA\Property(property="source", type="string", example="database"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/categories",
     *     tags={"Categories"},
     *     summary="Create a new category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name","category_type"},
     *             @OA\Property(property="category_name", type="string", example="Groceries"),
     *             @OA\Property(property="category_type", type="string", enum={"INCOME","EXPENSE"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category Created Successfully"),
     *     @OA\Response(response=422, description="Validation error or category already exists")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get a category by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category Not Found")
     * )
     */
    public function show(int $id)
    {
        $category = Category::whereNull('deleted_at')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category Not Found'], 404);
        }

        return response()->json($category);
    }

    /**
     * @OA\Put(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update a category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name","category_type"},
     *             @OA\Property(property="category_name", type="string", example="Groceries"),
     *             @OA\Property(property="category_type", type="string", enum={"INCOME","EXPENSE"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category Updated Successfully"),
     *     @OA\Response(response=404, description="Category Not Found"),
     *     @OA\Response(response=422, description="Validation error or duplicate")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete a category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Category Deleted Successfully"),
     *     @OA\Response(response=404, description="Category Not Found"),
     *     @OA\Response(response=422, description="Cannot delete default category")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/categories/default",
     *     tags={"Categories"},
     *     summary="List default categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Default categories list")
     * )
     */
    public function defaultCategories()
    {
        return Category::where('is_default', 1)
            ->whereNull('deleted_at')
            ->orderBy('category_name', 'ASC')
            ->get();
    }
}
