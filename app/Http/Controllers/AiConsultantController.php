<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class AiConsultantController extends Controller
{
    public function index()
    {
        $company = Auth::user()->companies()->first();
        return view('ai.consultant', compact('company'));
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000'
        ]);

        $company = Auth::user()->companies()->first();
        // Usamos strtolower para que el regex detecte palabras clave sin importar mayúsculas
        $userQuestion = strtolower($request->input('question'));

        // 1. INICIALIZAR EL PROMPT
        $systemPrompt = "Eres Vera AI, el consultor financiero y de Recursos Humanos experto del sistema Vera ERP. ";
        $systemPrompt .= "Estás hablando con un directivo de la empresa '{$company->legal_name}' (RFC: {$company->rfc}).\n\n";

        $contexto = "--- DATOS EXTRAÍDOS DE LA BÓVEDA PARA ESTA CONSULTA ---\n";
        $datosExtraidos = false; // Bandera para saber si encontramos algo

        // =====================================================================
        // ENRUTADOR 1: FACTURACIÓN E INGRESOS
        // =====================================================================
        if (preg_match('/factura|ingreso|cobro|venta|sat/i', $userQuestion)) {
            $totalFacturado = \App\Models\Invoice::where('company_id', $company->id)->sum('total');
            $facturasRecientes = \App\Models\Invoice::where('company_id', $company->id)->orderBy('issue_date', 'desc')->take(3)->get();

            $contexto .= "\n[MÓDULO DE FACTURACIÓN]\n";
            $contexto .= "- Total histórico facturado: $" . number_format($totalFacturado, 2) . "\n";
            $contexto .= "- Últimas facturas emitidas:\n";
            foreach ($facturasRecientes as $fac) {
                $contexto .= "  * Folio: {$fac->uuid} | Cliente: {$fac->receiver_name} | Monto: $" . number_format($fac->total, 2) . "\n";
            }
            $datosExtraidos = true;
        }

        // =====================================================================
        // ENRUTADOR 2: NÓMINA Y EMPLEADOS
        // =====================================================================
        $expedientesRelevantes = "";

        if (preg_match('/empleado|nomina|pago|salario|isr|imss|retencion|abner/i', $userQuestion)) {
            $empleadosActivos = \App\Models\Employee::where('company_id', $company->id)->where('is_active', true)->count();
            $ultimaNomina = \App\Models\PayrollPeriod::where('company_id', $company->id)->orderBy('id', 'desc')->first();

            $contexto .= "\n[MÓDULO DE RECURSOS HUMANOS]\n";
            $contexto .= "- Plantilla activa: {$empleadosActivos} empleados.\n";

            if ($ultimaNomina) {
                $contexto .= "- Última nómina procesada ({$ultimaNomina->period_name}): Total bruto pagado $" . number_format($ultimaNomina->total_gross, 2) . "\n";
            }

            // Búsqueda Inteligente de Expedientes y Recibos
            $empleados = \App\Models\Employee::where('company_id', $company->id)->where('is_active', true)->get();
            foreach ($empleados as $emp) {
                $primerNombre = strtolower(explode(' ', $emp->full_name)[0]);

                if (str_contains($userQuestion, $primerNombre) || str_contains($userQuestion, strtolower($emp->full_name))) {

                    // 1. Inyectamos los datos maestros del empleado
                    $expedientesRelevantes .= "- [EXPEDIENTE AUTORIZADO] Nombre: {$emp->full_name} | Puesto: {$emp->position} | Salario Base: $" . number_format($emp->base_salary, 2) . " | RFC: {$emp->rfc}\n";

                    // 2. Inyectamos el detalle de su última nómina real (RAG Profundo)
                    // Buscamos el último registro en payroll_details de este empleado
                    $ultimoRecibo = \App\Models\PayrollDetail::where('employee_id', $emp->id)->orderBy('id', 'desc')->first();

                    if ($ultimoRecibo) {
                        // ATENCIÓN: Cambia 'gross_pay', 'total_deductions', 'isr_retention', 'net_pay' por tus columnas reales
                        $bruto = number_format($ultimoRecibo->gross_salary ?? 0, 2);
                        $imss = number_format($ultimoRecibo->imss_employee ?? 0, 2);
                        $isr = number_format($ultimoRecibo->isr_retention ?? 0, 2);
                        $neto = number_format($ultimoRecibo->net_salary ?? 0, 2);

                        $expedientesRelevantes .= "  * [DETALLE ÚLTIMA NÓMINA]: Bruto: $" . $bruto . " | IMSS: $" . $imss . " | ISR Retenido: $" . $isr . " | Neto Pagado: $" . $neto . "\n";
                    } else {
                        $expedientesRelevantes .= "  * [DETALLE ÚLTIMA NÓMINA]: Este empleado aún no tiene recibos de nómina procesados en el sistema.\n";
                    }
                }
            }
            $datosExtraidos = true;
        }

        // =====================================================================
        // ENSAMBLAR EL PROMPT FINAL
        // =====================================================================
        if ($datosExtraidos) {
            $systemPrompt .= $contexto;
        } else {
            $systemPrompt .= "No se activó ningún módulo específico para esta consulta. Responde con tu conocimiento general.\n";
        }

        if (!empty($expedientesRelevantes)) {
            $systemPrompt .= "\n--- DATOS CONFIDENCIALES SOLICITADOS POR EL DIRECTIVO ---\n";
            $systemPrompt .= $expedientesRelevantes;
        }

        $systemPrompt .= "\n-------------------------------------------------------\n";
        $systemPrompt .= "Misión: Responde a la pregunta del usuario. Si hay cálculos, hazlos paso a paso. Sé exacto, usa formato Markdown. Si no tienes datos suficientes en el contexto de arriba, indícalo claramente.";

        // =====================================================================
        // ENVÍO A CLAUDE
        // =====================================================================
        $payload = [
            "model" => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'), // O haiku si prefieres
            "max_tokens" => 1024,
            "temperature" => 0.1,
            "system" => $systemPrompt,
            "messages" => [
                [
                    "role" => "user",
                    "content" => $userQuestion
                ]
            ]
        ];

        $apiKey = env('CLAUDE_API_KEY');
        $url = "https://api.anthropic.com/v1/messages";

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['content'][0]['text'] ?? 'No pude generar una respuesta.';
                return response()->json(['success' => true, 'answer' => $answer]);
            }

            return response()->json(['success' => false, 'error' => 'Error de Claude API'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function dashboardSummary(Request $request)
    {
        $company = Auth::user()->companies()->first();

        // 1. Extraer Métricas Globales Rápidas (Sin cargar expedientes pesados)
        $empleadosActivos = \App\Models\Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
        
        $ingresosMes = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'I') // Filtrar estrictamente por Ingresos
            ->where('is_canceled', 0) // <--- SINTAXIS CORRECTA
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->sum('total');

        // (Opcional) Si manejas notas de crédito, puedes restarlas para sacar la facturación neta
        $egresosMes = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'E') // Notas de crédito / Devoluciones
            ->where('is_canceled', 0) // <--- SINTAXIS CORRECTA
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->sum('total');
            
        $facturacionReal = $ingresosMes - $egresosMes;
            
        $ultimaNomina = \App\Models\PayrollPeriod::where('company_id', $company->id)
            ->orderBy('id', 'desc')
            ->first();

        // 2. Construir el Prompt Directivo
        $prompt = "Eres Vera AI. Redacta un resumen ejecutivo de máximo 3 viñetas cortas sobre la salud actual de la empresa '{$company->legal_name}'.\n\n";
        $prompt .= "--- DATOS EN TIEMPO REAL ---\n";
        $prompt .= "- Empleados activos en plantilla: {$empleadosActivos}\n";
        $prompt .= "- Total facturado este mes (Ingresos netos): $" . number_format($facturacionReal, 2) . "\n";
        
        if ($ultimaNomina) {
            $prompt .= "- Última nómina ({$ultimaNomina->period_name}): $" . number_format($ultimaNomina->total_gross, 2) . " brutos.\n";
        }

        $prompt .= "----------------------------\n\n";
        $prompt .= "Reglas: Usa un tono analítico, optimista pero muy profesional y directo. Usa formato Markdown con emojis ejecutivos (📈, 👥, 💰). NO saludes ni te despidas, ve directo a las 3 viñetas.";

        // 3. Petición a Claude
        $payload = [
            "model" => env('CLAUDE_MODEL', 'claude-4-5-sonnet-2025-0929'), // Haiku o Sonnet
            "max_tokens" => 400,
            "temperature" => 0.4,
            "system" => "Eres un analista de datos de nivel directivo.",
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => env('CLAUDE_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post("https://api.anthropic.com/v1/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json(['success' => true, 'summary' => $data['content'][0]['text']]);
            }
            return response()->json(['success' => false, 'error' => 'API Error']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Connection Error']);
        }
    }
}
