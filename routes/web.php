<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FixedExpenseController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ConciliationController;
use App\Http\Controllers\AiConsultantController;
use App\Http\Middleware\EnsureUserHasCompany;

Route::get('/', function () {
    return view('welcome');
});

// 1. Rutas de Onboarding (Requieren login y correo verificado, pero NO empresa)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// 2. Rutas del Sistema Vera AI (Requieren Login + Verificado + EMPRESA CONFIGURADA)
// Aquí inyectamos EnsureUserHasCompany::class para blindar el sistema
Route::middleware(['auth', 'verified', EnsureUserHasCompany::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo Vera AI
    Route::get('/consultant', [AiConsultantController::class, 'index'])->name('ai.consultant');
    Route::post('/consultant/ask', [AiConsultantController::class, 'ask'])->name('ai.ask');
    Route::get('/ai/dashboard-summary', [AiConsultantController::class, 'dashboardSummary'])->name('ai.dashboard.summary');

    // Módulo de Empleados
    Route::resource('employees', EmployeeController::class);
    // Panel de Deducciones Personalizadas
    Route::get('/employees/{employee}/deductions', [App\Http\Controllers\EmployeeDeductionController::class, 'index'])->name('employees.deductions.index');
    Route::post('/employees/{employee}/deductions', [App\Http\Controllers\EmployeeDeductionController::class, 'store'])->name('employees.deductions.store');
    Route::delete('/employees/{employee}/deductions/{deduction}', [App\Http\Controllers\EmployeeDeductionController::class, 'destroy'])->name('employees.deductions.destroy');

    // Módulo de Nómina
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::get('/payrolls/{id}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::delete('/payrolls/{id}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
    Route::get('/payrolls/receipt/{id}/pdf', [PayrollController::class, 'downloadReceiptPdf'])->name('payrolls.receipt.pdf');
    Route::post('/payrolls/{id}/send-emails', [PayrollController::class, 'sendMassiveEmails'])->name('payrolls.send_emails');

    // Módulo de Bóveda de Facturas (XML) - Limpio de redundancias
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

    // Módulo de Perfil Fiscal de la Empresa
    Route::get('/company/profile', [CompanyProfileController::class, 'edit'])->name('company.profile');
    Route::put('/company/profile', [CompanyProfileController::class, 'update'])->name('company.update');

    // Módulo de OpEx (Gastos Fijos)
    Route::get('/opex', [FixedExpenseController::class, 'index'])->name('opex.index');
    Route::get('/opex/create', [FixedExpenseController::class, 'create'])->name('opex.create');
    Route::post('/opex', [FixedExpenseController::class, 'store'])->name('opex.store');
    Route::get('/opex/{id}/edit', [FixedExpenseController::class, 'edit'])->name('opex.edit');
    Route::put('/opex/{id}', [FixedExpenseController::class, 'update'])->name('opex.update');
    Route::patch('/opex/{id}/toggle', [FixedExpenseController::class, 'toggleStatus'])->name('opex.toggle');

    // Módulo de Facturación Simulada (PAC)
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{id}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{id}/xml', [BillingController::class, 'downloadXml'])->name('billing.xml');
    Route::get('/billing/{id}/pdf', [BillingController::class, 'downloadPdf'])->name('billing.pdf');
    Route::get('/billing/{id}/download-zip', [BillingController::class, 'downloadZip'])->name('billing.zip');

    // Módulo de Conciliación Bancaria
    Route::get('/conciliations', [ConciliationController::class, 'index'])->name('conciliations.index');
    Route::post('/conciliations/preview', [ConciliationController::class, 'preview'])->name('conciliations.preview');
    Route::post('/conciliations/import', [ConciliationController::class, 'import'])->name('conciliations.import');
});

// 3. Rutas de Perfil de Usuario (Solo requieren auth para que el usuario pueda corregir su correo si se equivocó)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
