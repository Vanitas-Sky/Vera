<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controladores API V1
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\AiConsultantApiController;
use App\Http\Controllers\Api\V1\InvoiceApiController;
use App\Http\Controllers\Api\V1\PayrollApiController;
use App\Http\Controllers\Api\V1\EmployeeApiController;
use App\Http\Controllers\Api\V1\FixedExpenseApiController;
use App\Http\Controllers\Api\V1\CompanyApiController;

// Controladores Base / Recursos
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserCompanyController;

/*
|--------------------------------------------------------------------------
| Rutas API V1 (Vera Mobile & Integraciones)
|--------------------------------------------------------------------------
| Todas las peticiones resuelven bajo el prefijo: /api/v1/...
*/

Route::prefix('v1')->group(function () {

    // =====================================================================
    // 1. RUTAS PÚBLICAS (AUTENTICACIÓN Y RECUPERACIÓN)
    // =====================================================================
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // =====================================================================
    // 2. RUTAS PROTEGIDAS (REQUIEREN TOKEN SANCTUM)
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Sesión de Usuario
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [AuthController::class, 'logout']);

        // Dashboard Principal
        Route::get('/dashboard/summary', [DashboardApiController::class, 'summary']);

        // Consultor Vera AI
        Route::post('/ai/ask', [AiConsultantApiController::class, 'ask']);
        Route::get('/ai/summary', [AiConsultantApiController::class, 'dashboardSummary']);

        // Bóveda de Facturas (CFDI)
        Route::get('/invoices', [InvoiceApiController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceApiController::class, 'show']);

        // Nóminas y Recibos PDF
        Route::get('/payrolls', [PayrollApiController::class, 'index']);
        Route::get('/payrolls/{id}/details', [PayrollApiController::class, 'show']);
        Route::get('/payrolls/{periodId}/employees/{employeeId}/pdf', [PayrollApiController::class, 'downloadReceiptPdf']);

        // Directorio de Empleados
        Route::get('/employees', [EmployeeApiController::class, 'index']);

        // Perfil Fiscal de la Empresa
        Route::get('/company/profile', [CompanyApiController::class, 'profile']);

        // OpEx (Gastos Fijos): Lectura (Administradores y Auditores)
        Route::get('/fixed-expenses', [FixedExpenseApiController::class, 'index']);

        // =================================================================
        // 3. MUTACIONES DE OPEX (BLOQUEADAS AL AUDITOR EXTERNO)
        // =================================================================
        Route::middleware('prevent.auditor')->group(function () {
            Route::post('/fixed-expenses', [FixedExpenseApiController::class, 'store']);
            Route::put('/fixed-expenses/{id}', [FixedExpenseApiController::class, 'update']);
            Route::patch('/fixed-expenses/{id}/toggle', [FixedExpenseApiController::class, 'toggle']);
        });

        // Recursos Administrativos
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('user-companies', UserCompanyController::class);
    });
});

// Rutas de compatibilidad en la raíz (/api/login, /api/forgot-password, /api/reset-password)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);