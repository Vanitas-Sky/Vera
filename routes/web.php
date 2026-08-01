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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Rutas de Onboarding (Solo requieren estar logueado)
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// Rutas protegidas (Requieren Login + Empresa Configurada)
Route::middleware(['auth', 'verified', EnsureUserHasCompany::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
    Route::get('/ai/dashboard-summary', [App\Http\Controllers\AiConsultantController::class, 'dashboardSummary'])->name('ai.dashboard.summary');

    // CRUD completo de Empleados
    Route::resource('employees', EmployeeController::class);

    // Rutas de Nómina
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::get('/payrolls/{id}', [PayrollController::class, 'show'])->name('payrolls.show');
    Route::delete('/payrolls/{id}', [\App\Http\Controllers\PayrollController::class, 'destroy'])->name('payrolls.destroy');
    Route::get('/payrolls/receipt/{id}/pdf', [\App\Http\Controllers\PayrollController::class, 'downloadReceiptPdf'])->name('payrolls.receipt.pdf');

    // Rutas de Facturas (XML)
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{id}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');

    // Rutas de Perfil Fiscal de la Empresa
    Route::get('/company/profile', [CompanyProfileController::class, 'edit'])->name('company.profile');
    Route::put('/company/profile', [CompanyProfileController::class, 'update'])->name('company.update');

    // Rutas de OpEx (Gastos Fijos)
    Route::get('/opex', [FixedExpenseController::class, 'index'])->name('opex.index');
    Route::get('/opex/create', [FixedExpenseController::class, 'create'])->name('opex.create');
    Route::post('/opex', [FixedExpenseController::class, 'store'])->name('opex.store');

    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');

    // Nuevas rutas para la visualización y descarga
    Route::get('/billing/{id}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{id}/xml', [BillingController::class, 'downloadXml'])->name('billing.xml');
    Route::get('/billing/{id}/pdf', [BillingController::class, 'downloadPdf'])->name('billing.pdf');
    Route::get('/billing/{id}/download-zip', [BillingController::class, 'downloadZip'])->name('billing.zip');

    // Módulo de Conciliación Bancaria
    Route::get('/conciliations', [\App\Http\Controllers\ConciliationController::class, 'index'])->name('conciliations.index');
    Route::post('/conciliations/preview', [\App\Http\Controllers\ConciliationController::class, 'preview'])->name('conciliations.preview');
    Route::post('/conciliations/import', [\App\Http\Controllers\ConciliationController::class, 'import'])->name('conciliations.import');

    // Módulo Vera AI
    Route::get('/consultant', [AiConsultantController::class, 'index'])->name('ai.consultant');
    Route::post('/consultant/ask', [App\Http\Controllers\AiConsultantController::class, 'ask'])->name('ai.ask');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
