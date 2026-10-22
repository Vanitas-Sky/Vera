<?php

use Illuminate\Support\Facades\Route;

// Controladores Base y Autenticación
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetFlowController;

// Controladores del Sistema ERP
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiConsultantController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\FixedExpenseController;
use App\Http\Controllers\ConciliationController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\AuditorController;

// Middlewares
use App\Http\Middleware\EnsureUserHasCompany;

/*
|--------------------------------------------------------------------------
| Web Routes - Vera ERP
|--------------------------------------------------------------------------
*/

// Página de bienvenida / Landing
Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// 1. RECUPERACIÓN DE CONTRASEÑA EN 2 PASOS (SOLO USUARIOS NO AUTENTICADOS)
// =========================================================================
Route::middleware('guest')->group(function () {
    // Vista unificada (Paso 1: Solicitud de código / Paso 2: Validación y cambio)
    Route::get('/recuperar-contrasena', [PasswordResetFlowController::class, 'show'])->name('password.recovery.show');
    Route::post('/recuperar-contrasena/enviar-codigo', [PasswordResetFlowController::class, 'sendCode'])->name('password.recovery.send');
    Route::post('/recuperar-contrasena/actualizar', [PasswordResetFlowController::class, 'resetPassword'])->name('password.recovery.update');
    Route::get('/recuperar-contrasena/reiniciar', [PasswordResetFlowController::class, 'restart'])->name('password.recovery.restart');
});

// =========================================================================
// 2. ONBOARDING (Requiere autenticación, pero aún sin empresa asociada)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// =========================================================================
// 3. SISTEMA VERA ERP (Requiere Login + Verificado + Empresa Configurada)
// =========================================================================
Route::middleware(['auth', 'verified', EnsureUserHasCompany::class])->group(function () {

    // ---------------------------------------------------------------------
    // A) RUTAS MUTATIVAS Y DE CREACIÓN (BLOQUEADAS AL AUDITOR EXTERNO)
    // Se declaran antes de las rutas dinámicas /{id} para evitar colisiones
    // ---------------------------------------------------------------------
    Route::middleware('prevent.auditor')->group(function () {

        // Facturación Simulada (PAC)
        Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');

        // Bóveda de Facturas (Carga y Cancelación)
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        // Directorio de Empleados (Gestión y Deducciones)
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('/employees/{employee}/deductions', [EmployeeDeductionController::class, 'store'])->name('employees.deductions.store');
        Route::delete('/employees/{employee}/deductions/{deduction}', [EmployeeDeductionController::class, 'destroy'])->name('employees.deductions.destroy');

        // Nóminas (Generación, Cancelación y Envío Masivo)
        Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
        Route::delete('/payrolls/{id}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
        Route::post('/payrolls/{id}/send-emails', [PayrollController::class, 'sendMassiveEmails'])->name('payrolls.send_emails');

        // OpEx (Gastos Fijos)
        Route::get('/opex/create', [FixedExpenseController::class, 'create'])->name('opex.create');
        Route::post('/opex', [FixedExpenseController::class, 'store'])->name('opex.store');
        Route::get('/opex/{id}/edit', [FixedExpenseController::class, 'edit'])->name('opex.edit');
        Route::put('/opex/{id}', [FixedExpenseController::class, 'update'])->name('opex.update');
        Route::patch('/opex/{id}/toggle', [FixedExpenseController::class, 'toggleStatus'])->name('opex.toggle');

        // Conciliación Bancaria
        Route::post('/conciliations/preview', [ConciliationController::class, 'preview'])->name('conciliations.preview');
        Route::post('/conciliations/import', [ConciliationController::class, 'import'])->name('conciliations.import');

        // Perfil Fiscal de la Empresa
        Route::put('/company/profile', [CompanyProfileController::class, 'update'])->name('company.update');

        // Gestión de Auditores
        Route::get('/company/auditors', [AuditorController::class, 'index'])->name('auditors.index');
        Route::post('/company/auditors', [AuditorController::class, 'store'])->name('auditors.store');
        Route::delete('/company/auditors/{id}', [AuditorController::class, 'destroy'])->name('auditors.destroy');
    });

    // ---------------------------------------------------------------------
    // B) RUTAS DE LECTURA Y AUDITORÍA (PERMITIDAS A TODOS LOS ROLES)
    // ---------------------------------------------------------------------

    // Tablero Principal y Consultor Vera AI
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/consultant', [AiConsultantController::class, 'index'])->name('ai.consultant');
    Route::post('/consultant/ask', [AiConsultantController::class, 'ask'])->name('ai.ask');
    Route::get('/ai/dashboard-summary', [AiConsultantController::class, 'dashboardSummary'])->name('ai.dashboard.summary');

    // Facturación Simulada (Comprobantes y Descargas)
    Route::get('/billing/{id}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{id}/xml', [BillingController::class, 'downloadXml'])->name('billing.xml');
    Route::get('/billing/{id}/pdf', [BillingController::class, 'downloadPdf'])->name('billing.pdf');
    Route::get('/billing/{id}/download-zip', [BillingController::class, 'downloadZip'])->name('billing.zip');

    // Bóveda de Facturas
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Empleados y Retenciones
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/deductions', [EmployeeDeductionController::class, 'index'])->name('employees.deductions.index');

    // Nóminas y Recibos PDF
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::get('/payrolls/{id}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::get('/payrolls/receipt/{id}/pdf', [PayrollController::class, 'downloadReceiptPdf'])->name('payrolls.receipt.pdf');

    // OpEx / Gastos Fijos
    Route::get('/opex', [FixedExpenseController::class, 'index'])->name('opex.index');

    // Conciliación Bancaria y Perfil Fiscal
    Route::get('/conciliations', [ConciliationController::class, 'index'])->name('conciliations.index');
    Route::get('/company/profile', [CompanyProfileController::class, 'edit'])->name('company.profile');
});

// =========================================================================
// 4. PERFIL DE USUARIO
// =========================================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas de autenticación base de Laravel Breeze (Login, Registro, etc.)
require __DIR__ . '/auth.php';