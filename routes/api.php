<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserCompanyController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\PayrollApiController;
use App\Http\Controllers\Api\V1\EmployeeApiController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ... rutas anteriores ...
    Route::get('/employees', [EmployeeApiController::class, 'index']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ... rutas anteriores ...
    Route::get('/payrolls', [PayrollApiController::class, 'index']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); // Esta ya la tenías
    
    // Nueva ruta para el resumen del dashboard
    experimental: Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
});

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Modulo 1: Usuarios, Empresas y Accesos
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('user-companies', UserCompanyController::class);
});
