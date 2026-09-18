<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;

class AiConsultantApiController extends Controller
{
    /**
     * Chat interactivo con Vera AI (Claude)
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000'
        ]);

        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $userQuestion = strtolower($request->input('question'));

        // 1. INICIALIZAR EL PROMPT DEL SISTEMA
        $systemPrompt = "Eres Vera AI, el consultor financiero y de Recursos Humanos experto de Vera ERP.\n";
        $systemPrompt .= "Estás hablando con un directivo de la empresa '{$company->legal_name}' (RFC: {$company->rfc}).\n\n";
        $systemPrompt .= "\nReglas para formato Móvil:
                        - Responde pensando en una pantalla pequeña de smartphone.
                        - NUNCA uses tablas con barras verticales (| Campo | Valor |).
                        - Usa listas con viñetas claras (• o -), negritas (**Texto**) y saltos de línea ordenados.
                        - Usa emojis ejecutivos estándar (📊, 💰, 👤, 📅, 📄).";

        $contexto = "--- DATOS EXTRAÍDOS DE LA BÓVEDA PARA ESTA CONSULTA ---\n";
        $datosExtraidos = false;

        // =====================================================================
        // ENRUTADOR 1: FACTURACIÓN E INGRESOS
        // =====================================================================
        if (preg_match('/factura|ingreso|cobro|venta|sat|gasto/i', $userQuestion)) {
            $totalFacturado = Invoice::where('company_id', $company->id)->where('type', 'I')->where('is_canceled', false)->sum('total');
            $facturasRecientes = Invoice::where('company_id', $company->id)->orderBy('issue_date', 'desc')->take(3)->get();

            $contexto .= "\n[MÓDULO DE FACTURACIÓN]\n";
            $contexto .= "- Total histórico ingresado: $" . number_format($totalFacturado, 2) . "\n";
            $contexto .= "- Últimos movimientos emitidos/recibidos:\n";
            foreach ($facturasRecientes as $fac) {
                $cliente = $fac->receiver_name ?: ($fac->issuer_name ?: 'Público general');
                $contexto .= "  * UUID: {$fac->uuid} | Contraparte: {$cliente} | Monto: $" . number_format($fac->total, 2) . "\n";
            }
            $datosExtraidos = true;
        }

        // =====================================================================
        // ENRUTADOR 2: NÓMINA Y EMPLEADOS
        // =====================================================================
        $expedientesRelevantes = "";

        if (preg_match('/empleado|nomina|pago|salario|isr|imss|retencion|personal/i', $userQuestion)) {
            $empleadosActivos = Employee::where('company_id', $company->id)->where('is_active', true)->count();
            $ultimaNomina = PayrollPeriod::where('company_id', $company->id)->orderBy('id', 'desc')->first();

            $contexto .= "\n[MÓDULO DE RECURSOS HUMANOS]\n";
            $contexto .= "- Plantilla activa: {$empleadosActivos} empleados.\n";

            if ($ultimaNomina) {
                $contexto .= "- Última nómina procesada ({$ultimaNomina->period_name}): Total bruto $" . number_format($ultimaNomina->total_gross, 2) . "\n";
            }

            // Búsqueda inteligente de empleados mencionados
            $empleados = Employee::where('company_id', $company->id)->where('is_active', true)->get();
            foreach ($empleados as $emp) {
                $primerNombre = strtolower(explode(' ', $emp->full_name)[0]);

                if (str_contains($userQuestion, $primerNombre) || str_contains($userQuestion, strtolower($emp->full_name))) {
                    $expedientesRelevantes .= "- [EXPEDIENTE] Nombre: {$emp->full_name} | Puesto: {$emp->position} | Salario Base: $" . number_format($emp->base_salary, 2) . " | RFC: {$emp->rfc}\n";

                    $ultimoRecibo = PayrollDetail::where('employee_id', $emp->id)->orderBy('id', 'desc')->first();
                    if ($ultimoRecibo) {
                        $bruto = number_format($ultimoRecibo->gross_salary ?? 0, 2);
                        $imss = number_format($ultimoRecibo->imss_employee ?? 0, 2);
                        $isr = number_format($ultimoRecibo->isr_retention ?? 0, 2);
                        $neto = number_format($ultimoRecibo->net_salary ?? 0, 2);

                        $expedientesRelevantes .= "  * [ÚLTIMO RECIBO]: Bruto: $" . $bruto . " | IMSS: $" . $imss . " | ISR Retenido: $" . $isr . " | Neto: $" . $neto . "\n";
                    }
                }
            }
            $datosExtraidos = true;
        }

        if ($datosExtraidos) {
            $systemPrompt .= $contexto;
        }

        if (!empty($expedientesRelevantes)) {
            $systemPrompt .= "\n--- DATOS CONFIDENCIALES DEL EMPLEADO CONSULTADO ---\n" . $expedientesRelevantes;
        }

        $systemPrompt .= "\nReglas: Responde de forma ejecutiva, concisa y profesional. Usa formato Markdown limpio sin rodeos. Si hay cálculos, muéstralos claros.";

        // 2. LLAMADA A ANTHROPIC CLAUDE
        $apiKey = env('CLAUDE_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'La API Key de Claude no está configurada en el servidor.'
            ], 500);
        }

        $payload = [
            "model" => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
            "max_tokens" => 1024,
            "temperature" => 0.2,
            "system" => $systemPrompt,
            "messages" => [
                [
                    "role" => "user",
                    "content" => $request->input('question')
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post("https://api.anthropic.com/v1/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['content'][0]['text'] ?? 'No se pudo generar respuesta.';

                return response()->json([
                    'success' => true,
                    'answer' => $answer,
                    'timestamp' => now()->format('H:i')
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Respuesta inesperada de Claude API: ' . $response->status()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error de conexión con el servicio de IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumen Ejecutivo para el Dashboard Móvil
     */
    public function dashboardSummary(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'Empresa no encontrada.'], 404);
        }

        $empleadosActivos = Employee::where('company_id', $company->id)->where('is_active', true)->count();

        $ingresosMes = Invoice::where('company_id', $company->id)
            ->where('type', 'I')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->sum('total');

        $egresosMes = Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->sum('total');

        $facturacionReal = $ingresosMes - $egresosMes;

        $ultimaNomina = PayrollPeriod::where('company_id', $company->id)->orderBy('id', 'desc')->first();

        $prompt = "Eres Vera AI. Redacta un resumen ejecutivo de exactamente 2 o 3 viñetas cortas sobre el estado fiscal y operativo de '{$company->legal_name}'.\n";
        $prompt .= "- Empleados activos: {$empleadosActivos}\n";
        $prompt .= "- Facturación neta del mes: $" . number_format($facturacionReal, 2) . "\n";
        if ($ultimaNomina) {
            $prompt .= "- Última nómina: $" . number_format($ultimaNomina->total_gross, 2) . "\n";
        }
        $prompt .= "Reglas: Tono directivo y optimista. Usa viñetas con emojis (📈, 👥, 💰). Sin introducciones ni saludos.";

        $apiKey = env('CLAUDE_API_KEY');
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'Sin API Key'], 500);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(20)->post("https://api.anthropic.com/v1/messages", [
                "model" => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
                "max_tokens" => 300,
                "temperature" => 0.3,
                "messages" => [["role" => "user", "content" => $prompt]]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'summary' => $data['content'][0]['text'] ?? ''
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Error de respuesta'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
