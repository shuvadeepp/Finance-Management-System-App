<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redis;

class CategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Redis Cache Key — defined once, used everywhere
    |--------------------------------------------------------------------------
    */

    // ✅ CHANGE 1: Extracted into a constant so all methods
    //    use the same key string. Before, only index() used
    //    it — now store/update/destroy need it too.
    private const CACHE_KEY = 'categories_all';

    /*
    |--------------------------------------------------------------------------
    | Category List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        // ✅ CHANGE 2: Using the constant instead of hardcoded string
        $cachedData = Redis::get(self::CACHE_KEY);
        // dd($cachedData);
        if ($cachedData !== null) {
            return response()->json([
                'source' => 'redis',
                'data'   => json_decode($cachedData, true)
            ]);
        }

        // $categories = Category::orderBy('category_name', 'DECS')->get();
        $categories = Category::get();

        Redis::setex(
            self::CACHE_KEY,
            3600,
            $categories->toJson()
        );

        return response()->json([
            'source' => 'database',
            'data'   => $categories
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Category
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_name' => ['required', 'max:100'],
                'category_type' => ['required', 'in:INCOME,EXPENSE']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $categoryExists = Category::where('category_name', $request->category_name)
            ->where('category_type', $request->category_type)
            ->exists();

        if ($categoryExists) {
            return response()->json([
                'message' => 'Category already exists'
            ], 422);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $category = Category::create([
            'category_name' => $request->category_name,
            'category_type' => $request->category_type,
            'is_default'    => 0,
            'created_by'    => $user->id
        ]);

        // ✅ CHANGE 3: Delete the Redis cache after creating a new category.
        //    Why? The cached list is now outdated — it's missing this new record.
        //    Next GET request will fetch fresh data from DB and re-cache it.
        Redis::del(self::CACHE_KEY);

        return response()->json([
            'message' => 'Category Created Successfully',
            'data'    => $category
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit / Fetch Single Category
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category Not Found'
            ], 404);
        }

        // No Redis change needed here — show() fetches ONE category by ID,
        // not the full list, so it doesn't interact with 'categories_all' cache.
        return response()->json($category);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Category
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category Not Found'
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'category_name' => ['required', 'max:100'],
                'category_type' => ['required', 'in:INCOME,EXPENSE']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $duplicate = Category::where('category_name', $request->category_name)
            ->where('category_type', $request->category_type)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Category already exists'
            ], 422);
        }

        $category->update([
            'category_name' => $request->category_name,
            'category_type' => $request->category_type
        ]);

        // ✅ CHANGE 4: Delete cache after update.
        //    Why? The cached list still has the OLD name/type.
        //    Without this, browser would see stale data for up to 1 hour.
        //    This is EXACTLY the problem you were solving manually with DEL.
        Redis::del(self::CACHE_KEY);

        return response()->json([
            'message' => 'Category Updated Successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Category
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Category Not Found'
            ], 404);
        }

        if ($category->is_default == 1) {
            return response()->json([
                'message' => 'Default Category Cannot Be Deleted'
            ], 422);
        }

        $category->delete();

        // ✅ CHANGE 5: Delete cache after deleting a category.
        //    Why? The cached list still contains the deleted record.
        //    Browser would show a ghost category until cache expires.
        Redis::del(self::CACHE_KEY);

        return response()->json([
            'message' => 'Category Deleted Successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Default Categories
    |--------------------------------------------------------------------------
    */

    public function defaultCategories()
    {
        // No Redis change here — this is a separate filtered query.
        // If you want to cache this too later, use a separate key
        // like 'categories_default' and invalidate it the same way.
        return Category::where('is_default', 1)
            ->orderBy('category_name', 'ASC')
            ->get();
    }
}