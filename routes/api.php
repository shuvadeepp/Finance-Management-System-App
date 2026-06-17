<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DashboardController;

// Route::post('/register', [AuthController::class, 'register']);
Route::middleware('throttle:3,1')->group(function () {
    
    Route::post('/register', [AuthController::class, 'register']);

});
// Route::post('/login', [AuthController::class, 'login']);
Route::middleware('throttle:5,1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

});

Route::post('/forgot-password', [AuthController::class,'forgotPassword']);

Route::post('/verify-otp', [AuthController::class,'verifyOtp']);

Route::post('/reset-password', [AuthController::class,'resetPassword']);


Route::middleware(['jwt.role:ADMIN,MANAGER'])->group(function () {

    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']); 
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']); 
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
});

Route::middleware(['jwt.role:MANAGER'])->group(function () {

    Route::post('/assign-project', [AssignmentController::class, 'assign']);
});

Route::middleware(['jwt.role:ADMIN,MANAGER,EMPLOYEE,GIT USER'])->group(function () {

    Route::get('/assigned-projects', [AssignmentController::class, 'assignedProjects']);
});
Route::middleware(['jwt.role:ADMIN,MANAGER,EMPLOYEE,GIT USER',
    'throttle:60,1'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/default', [CategoryController::class, 'defaultCategories']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update'] );
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

Route::middleware(['jwt.role:ADMIN,MANAGER,EMPLOYEE,GIT USER',
    'throttle:60,1'])->group(function () {

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
});

Route::middleware(['jwt.role:ADMIN,MANAGER,EMPLOYEE,GIT USER',
    'throttle:60,1'])->group(function () {
    Route::get('/budgets', [BudgetController::class,'index']);
    Route::post('/budgets', [BudgetController::class,'store']);
    Route::put('/budgets/{id}', [BudgetController::class,'update']);
    Route::delete('/budgets/{id}', [BudgetController::class,'destroy']);
    Route::get('/budget-tracking', [BudgetController::class,'budgetTracking']);
    Route::get('/budget-overview', [BudgetController::class,'overview']);
});


Route::middleware(['jwt.role:ADMIN,MANAGER,EMPLOYEE,GIT USER',
    'throttle:60,1'])->group(function () {
    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );

});


Route::get('/auth/github/redirect', [AuthController::class, 'githubRedirect']);
Route::get('/auth/github/callback', [AuthController::class, 'githubCallback']);