<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Models\FixedExpense;
use App\Services\DeducibilityMatrixService;

class AiConsultantApiController extends Controller
{
    protected DeducibilityMatrixService $matrixService;

    public function __construct(DeducibilityMatrixService $matrixService)
    {
        $this->matrixService = $matrixService;
    }

    public function ask(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required|string|max:1000',
                'history'  => 'nullable|array'
            ]);

            $user = $request->user();
            $company = $user->companies()->first();

            if (!$company) {
                return response()->json(['success' => false, 'error' => 'No hay una empresa asociada a tu cuenta.'], 404);
            }

            $rawQuestion = $request->input('question');
            $userQuestion = strtolower($rawQuestion);
            $hoy = Carbon::now();
            $isAuditor = $user->isAuditor();

            // 1. EXTRACTOR DE MESES
            $meses = [
                'enero' => 1,
                'febrero' => 2,
                'marzo' => 3,
                'abril' => 4,
                'mayo' => 5,
                'junio' => 6,
                'julio' => 7,
                'agosto' => 8,
                'septiembre' => 9,
                'octubre' => 10,
                'noviembre' => 11,
                'diciembre' => 12
            ];
            $mesFiltro = null;
            $nombreMesFiltro = null;
            foreach ($meses as $nombre => $num) {
                if (str_contains($userQuestion, $nombre)) {
                    $mesFiltro = $num;
                    $nombreMesFiltro = ucfirst($nombre);
                    break;
                }
            }

            // 2. DETECTOR DE UUID O FOLIO
            $uuidDetectado = null;
            if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $rawQuestion, $matches)) {
                $uuidDetectado = $matches[0];
            } elseif (preg_match('/[0-9a-fA-F]{8,}/', $rawQuestion, $matchesShort)) {
                $uuidDetectado = $matchesShort[0];
            }

            // 3. PROMPT BASE ADAPTADO A PANTALLAS MÓVILES
            $giro = $company->industry ?? 'Restaurantes y Alimentos';
            $taxRegime = $company->tax_regime_code ?? '601';
            $roleLabel = $isAuditor ? "Auditor Externo (Fiscalizador)" : "Directivo / Administrador";

            $systemPrompt = "Eres Vera AI, el consultor fiscal, contable, laboral y financiero directivo de Vera ERP.\n";
            $systemPrompt .= "Estás asesorando a un {$roleLabel} de '{$company->legal_name}' (RFC: {$company->rfc}, Régimen: {$taxRegime}).\n";
            $systemPrompt .= "- Fecha actual: " . $hoy->format('d/m/Y') . "\n";
            $systemPrompt .= "- Giro Oficial: {$giro}\n";
            $systemPrompt .= "- Moneda Obligatoria: PESOS MEXICANOS (MXN). Nunca uses USD.\n";
            if ($nombreMesFiltro) {
                $systemPrompt .= "- Filtro Temporal Detectado: Mes de {$nombreMesFiltro}\n";
            }

            $systemPrompt .= "\nREGLAS DE SALIDA MÓVIL (PANTALLA PEQUEÑA):
- Responde de forma directa, ágil y ejecutiva sin saludos vacíos.
- NUNCA uses tablas markdown con barras verticales (| campo | valor |).
- Usa listas con viñetas limpias (• o -), negritas (**Texto clave**) y saltos de línea legibles.
- Emojis sobrios permitidos (📊, 🟢, 🟡, 🔴, 💰, 👤, 🏢).\n";

            $contexto = "\n--- BASE DE CONOCIMIENTO EXTRAÍDA EN TIEMPO REAL ---\n";
            $datosExtraidos = false;

            // =====================================================================
            // ENRUTADOR 0: DICTAMEN UNITARIO POR UUID / FOLIO
            // =====================================================================
            if ($uuidDetectado) {
                $facturaUnitaria = Invoice::where('company_id', $company->id)
                    ->where('uuid', 'like', "%{$uuidDetectado}%")
                    ->first();

                if ($facturaUnitaria) {
                    $tipoFac = $facturaUnitaria->type === 'I' ? 'INGRESO' : 'GASTO/EGRESO';
                    $itemsDecoded = $this->formatItems($facturaUnitaria->items);
                    $fEmision = Carbon::parse($facturaUnitaria->issue_date)->format('d/m/Y');

                    $contexto .= "\n[EXPEDIENTE DE COMPROBANTE CONSULTADO]:\n";
                    $contexto .= "• Folio Fiscal / UUID: {$facturaUnitaria->uuid}\n";
                    $contexto .= "• Tipo: {$tipoFac} | Fecha: {$fEmision}\n";
                    $contexto .= "• Emisor: " . $this->safeString($facturaUnitaria->issuer_name) . " ({$facturaUnitaria->issuer_rfc})\n";
                    $contexto .= "• Receptor: " . $this->safeString($facturaUnitaria->receiver_name) . " ({$facturaUnitaria->receiver_rfc})\n";
                    $contexto .= "• Total: $" . number_format($facturaUnitaria->total, 2) . " MXN (IVA: $" . number_format($facturaUnitaria->iva ?? 0, 2) . ")\n";
                    $contexto .= "• Uso CFDI: " . $this->safeString($facturaUnitaria->cfdi_use) . "\n";
                    $contexto .= "• Conceptos: {$itemsDecoded}\n";
                    $datosExtraidos = true;
                }
            }

            // =====================================================================
            // ENRUTADOR 1: AUDITORÍA DE DEDUCIBILIDAD Y SEMÁFORO FISCAL
            // =====================================================================
            $esConsultaDeducibilidad = preg_match('/deducible|deducibilidad|giro|indispensable|auditar|auditoria|riesgo fiscal|actividad economica|no vaya|semaforo/i', $userQuestion);

            if (!$uuidDetectado && $esConsultaDeducibilidad) {
                $gastosQuery = Invoice::where('company_id', $company->id)
                    ->where('is_canceled', 0)
                    ->where(function ($q) use ($company) {
                        $q->where('type', 'E')->orWhere('receiver_rfc', $company->rfc);
                    })
                    ->where('issuer_rfc', '!=', $company->rfc);

                if ($mesFiltro) {
                    $gastosQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $mesFiltro);
                    $periodoAuditoriaTxt = "Mes de {$nombreMesFiltro} {$hoy->year}";
                } elseif (!preg_match('/todo|todas|historico|anual|global|completo/i', $userQuestion)) {
                    $gastosQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $hoy->month);
                    $periodoAuditoriaTxt = "Mes en curso (" . $hoy->format('m/Y') . ")";
                } else {
                    $periodoAuditoriaTxt = "Histórico Completo";
                }

                $gastosParaAuditar = $gastosQuery->orderBy('issue_date', 'desc')->take(25)->get();

                $cVerde = 0;
                $mVerde = 0.0;
                $cAmarillo = 0;
                $mAmarillo = 0.0;
                $cRojo = 0;
                $mRojo = 0.0;
                $detalleLineas = [];

                foreach ($gastosParaAuditar as $g) {
                    $items = is_array($g->items) ? $g->items : (json_decode($g->items, true) ?? []);
                    $peorStatus = 'VERDE';
                    $motivoPeor = 'Gasto operativo indispensable alineado a la actividad.';

                    foreach ($items as $it) {
                        $clave = $it['clave_prod_serv'] ?? $it['ClaveProdServ'] ?? $it['sat_code'] ?? '00000000';
                        $eval = $this->matrixService->evaluateConcept($clave, $taxRegime);
                        $st = strtoupper(trim($eval['status'] ?? 'VERDE'));

                        if (str_contains($st, 'ROJO')) {
                            $peorStatus = 'ROJO';
                            $motivoPeor = $eval['reason'];
                            break;
                        } elseif (str_contains($st, 'AMARILLO')) {
                            $peorStatus = 'AMARILLO';
                            $motivoPeor = $eval['reason'];
                        }
                    }

                    if ($peorStatus === 'ROJO') {
                        $cRojo++;
                        $mRojo += (float) $g->total;
                    } elseif ($peorStatus === 'AMARILLO') {
                        $cAmarillo++;
                        $mAmarillo += (float) $g->total;
                    } else {
                        $cVerde++;
                        $mVerde += (float) $g->total;
                    }

                    $detalleLineas[] = "• " . ($peorStatus === 'ROJO' ? '🔴' : ($peorStatus === 'AMARILLO' ? '🟡' : '🟢')) . " {$g->issuer_name}: $" . number_format($g->total, 2) . " MXN - {$motivoPeor}";
                }

                $contexto .= "\n[AUDITORÍA DE DEDUCIBILIDAD DE EGRESOS - {$periodoAuditoriaTxt}]:\n";
                $contexto .= "• Total Egresos Auditados: " . ($cVerde + $cAmarillo + $cRojo) . " comprobantes ($" . number_format($mVerde + $mAmarillo + $mRojo, 2) . " MXN)\n";
                $contexto .= "• 🟢 Deducibles: {$cVerde} comprobantes ($" . number_format($mVerde, 2) . " MXN)\n";
                $contexto .= "• 🟡 Riesgo Fiscal (Art. 69-B CFF): {$cAmarillo} comprobantes ($" . number_format($mAmarillo, 2) . " MXN)\n";
                $contexto .= "• 🔴 No Deducibles (Art. 28 LISR): {$cRojo} comprobantes ($" . number_format($mRojo, 2) . " MXN)\n";
                $contexto .= "• Detalle por Comprobante:\n" . implode("\n", array_slice($detalleLineas, 0, 8)) . "\n";
                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 2: GASTOS FIJOS OPERATIVOS (OpEx / RENTAS / CONTRATOS)
            // =====================================================================
            if (preg_match('/renta|servicio|internet|fijo|contrato|opex|luz|telefono|seguro|arrendamiento|meses|deber|pagado/i', $userQuestion)) {
                $gastosFijos = FixedExpense::where('company_id', $company->id)->get();

                $contexto .= "\n[GASTOS FIJOS / OPEX]:\n";
                $contexto .= "• Compromiso mensual activo: $" . number_format($gastosFijos->where('is_active', true)->sum('monthly_amount'), 2) . " MXN\n";
                $contexto .= "• Contratos:\n";

                foreach ($gastosFijos as $gf) {
                    $fInicio = $gf->contract_start_date ? Carbon::parse($gf->contract_start_date) : null;
                    $calcTxt = "Sin fecha de inicio";
                    if ($fInicio) {
                        $mesesTranscurridos = max(1, $fInicio->diffInMonths($hoy) + ($hoy->greaterThanOrEqualTo($fInicio) ? 1 : 0));
                        $calcTxt = "Activo desde {$fInicio->format('d/m/Y')} ({$mesesTranscurridos} meses transcurridos, $" . number_format($mesesTranscurridos * $gf->monthly_amount, 2) . " MXN acumulados)";
                    }
                    $contexto .= "  - {$gf->provider_name} ({$gf->category}): $" . number_format($gf->monthly_amount, 2) . " MXN (Día {$gf->due_day}) | {$calcTxt}\n";
                }
                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 3: NÓMINA, SUELDOS Y EXPEDIENTES LABORALES
            // =====================================================================
            $pideNomina = preg_match('/empleado|nomina|sueldo|salario|isr|imss|retencion|deduccion|cuota|obrera|patronal|contrat|alta|plantilla|gana|percepcion|invertido|inversion/i', $userQuestion);
            $todosEmpleados = Employee::where('company_id', $company->id)->get();
            $empleadosCoincidentes = collect();

            // 1. Detección por partes de nombre o RFC en la pregunta actual
            foreach ($todosEmpleados as $emp) {
                $partesNombre = explode(' ', strtolower($emp->full_name));
                $coincide = false;
                foreach ($partesNombre as $parte) {
                    if (strlen($parte) >= 4 && str_contains($userQuestion, $parte)) {
                        $coincide = true;
                        break;
                    }
                }
                if ($coincide || (!empty($emp->rfc) && str_contains($userQuestion, strtolower($emp->rfc)))) {
                    $empleadosCoincidentes->push($emp);
                }
            }

            // 2. Si no hubo coincidencia directa por nombre pero hay historial, buscar en los últimos mensajes
            if ($empleadosCoincidentes->isEmpty() && !empty($history)) {
                $textoHistorial = strtolower(json_encode(array_slice($history, -4)));
                foreach ($todosEmpleados as $emp) {
                    $partesNombre = explode(' ', strtolower($emp->full_name));
                    foreach ($partesNombre as $parte) {
                        if (strlen($parte) >= 4 && str_contains($textoHistorial, $parte)) {
                            $empleadosCoincidentes->push($emp);
                            break;
                        }
                    }
                }
            }

            if ($pideNomina || $empleadosCoincidentes->isNotEmpty()) {
                $contexto .= "\n[MÓDULO DE RECURSOS HUMANOS Y NÓMINA]:\n";

                // Nómina más reciente
                $periodoNomina = PayrollPeriod::where('company_id', $company->id)->orderBy('id', 'desc')->first();

                if ($periodoNomina) {
                    $contexto .= "• Último periodo registrado ({$periodoNomina->period_name}): Total Bruto $" . number_format($periodoNomina->total_gross, 2) . " MXN | Total Neto: $" . number_format($periodoNomina->total_net, 2) . " MXN\n";

                    $detalles = PayrollDetail::where('payroll_period_id', $periodoNomina->id)->get();
                    if ($detalles->isNotEmpty()) {
                        $contexto .= "• Recibos del último periodo:\n";
                        foreach ($detalles as $det) {
                            $emp = $todosEmpleados->firstWhere('id', $det->employee_id);
                            $nombre = $emp ? $emp->full_name : "Empleado #{$det->employee_id}";
                            $contexto .= "  - {$nombre}: Bruto $" . number_format($det->gross_salary, 2) . " | ISR -$" . number_format($det->isr_retention, 2) . " | IMSS -$" . number_format($det->imss_employee, 2) . " | Neto $" . number_format($det->net_salary, 2) . " MXN\n";
                        }
                    }
                }

                // Expedientes completos con FECHA DE CONTRATACIÓN y CÁLCULO DE ANTIGÜEDAD
                $contexto .= "• EXPEDIENTES LABORALES Y CÁLCULO DE INVERSIÓN ACUMULADA:\n";
                $listaAAnalizar = $empleadosCoincidentes->isNotEmpty() ? $empleadosCoincidentes : $todosEmpleados;

                foreach ($listaAAnalizar as $empC) {
                    $fAlta = $empC->hire_date ? Carbon::parse($empC->hire_date) : ($empC->created_at ? Carbon::parse($empC->created_at) : null);
                    $detalleAntiguedad = "Fecha de contratación no registrada.";

                    if ($fAlta) {
                        $mesesCompletos = $fAlta->diffInMonths($hoy);
                        $diasRestantes = $fAlta->copy()->addMonths($mesesCompletos)->diffInDays($hoy);
                        $mesesDecimales = round($mesesCompletos + ($diasRestantes / 30.4), 1);
                        $semanas = round($fAlta->diffInDays($hoy) / 7);

                        $inversionBrutaAcumulada = $mesesDecimales * ($empC->base_salary ?? 0);

                        $detalleAntiguedad = "Contratado el {$fAlta->format('d/m/Y')}. Al día de hoy ({$hoy->format('d/m/Y')}) han transcurrido {$mesesCompletos} meses y {$diasRestantes} días (~{$mesesDecimales} meses, ~{$semanas} semanas). Inversión total en salario bruto acumulado: $" . number_format($inversionBrutaAcumulada, 2) . " MXN.";
                    }

                    $contexto .= "  - **{$empC->full_name}** | Puesto: {$empC->position} | Salario Base Mensual: $" . number_format($empC->base_salary, 2) . " MXN | RFC: {$empC->rfc} | Estatus: " . ($empC->is_active ? 'Activo' : 'Inactivo') . "\n";
                    $contexto .= "    * Antigüedad y Retribución: {$detalleAntiguedad}\n";
                }

                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 4: FACTURACIÓN GENERAL Y BALANCE
            // =====================================================================
            $pideFacturacion = preg_match('/factura|ingreso|cobro|venta|egreso|compra|sat|cfdi|gasto|balance|utilidad|flujo/i', $userQuestion);

            if (!$esConsultaDeducibilidad && $pideFacturacion) {
                $invoicesQuery = Invoice::where('company_id', $company->id)->where('is_canceled', 0);

                if ($mesFiltro) {
                    $invoicesQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $mesFiltro);
                    $periodoBalanceTxt = "Mes de {$nombreMesFiltro} {$hoy->year}";
                } elseif (!preg_match('/todo|todas|historico|anual|global|completo/i', $userQuestion)) {
                    $invoicesQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $hoy->month);
                    $periodoBalanceTxt = "Mes en curso (" . $hoy->format('m/Y') . ")";
                } else {
                    $periodoBalanceTxt = "Histórico Completo";
                }

                $totalIngresos = (clone $invoicesQuery)->where('type', 'I')->sum('total');
                $totalEgresos = (clone $invoicesQuery)->where('type', 'E')->sum('total');
                $margenOperativo = $totalIngresos - $totalEgresos;

                $contexto .= "\n[FACTURACIÓN Y BALANCE - {$periodoBalanceTxt}]:\n";
                $contexto .= "• Ingresos / Ventas: $" . number_format($totalIngresos, 2) . " MXN\n";
                $contexto .= "• Egresos / Compras: $" . number_format($totalEgresos, 2) . " MXN\n";
                $contexto .= "• Margen Operativo: $" . number_format($margenOperativo, 2) . " MXN\n";
                $datosExtraidos = true;
            }

            if ($datosExtraidos) {
                $systemPrompt .= $contexto;
            }

            // 4. HISTORIAL CONVERSACIONAL (MULTI-TURN CHAT)
            $messages = [];
            $history = $request->input('history', []);

            if (is_array($history) && !empty($history)) {
                $historySlice = array_slice($history, -10);
                foreach ($historySlice as $item) {
                    if (isset($item['role']) && isset($item['content']) && in_array($item['role'], ['user', 'assistant'])) {
                        $messages[] = [
                            'role'    => $item['role'],
                            'content' => (string) $item['content']
                        ];
                    }
                }
            }

            $messages[] = [
                'role'    => 'user',
                'content' => $rawQuestion
            ];

            // 5. LLAMADA A ANTHROPIC CLAUDE
            $apiKey = env('CLAUDE_API_KEY');
            if (!$apiKey) {
                return response()->json(['success' => false, 'error' => 'API Key de Claude no configurada.'], 500);
            }

            $payload = [
                'model'       => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
                'max_tokens'  => 1024,
                'temperature' => 0.1,
                'system'      => $systemPrompt,
                'messages'    => $messages
            ];

            $response = Http::timeout(35)->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['content'][0]['text'] ?? 'No se pudo generar una respuesta.';

                return response()->json([
                    'success'   => true,
                    'answer'    => $answer,
                    'timestamp' => now()->format('H:i')
                ]);
            }

            $errorMsg = $response->json()['error']['message'] ?? 'Error desconocido de API';
            return response()->json(['success' => false, 'error' => "Claude API ({$response->status()}): {$errorMsg}"], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error en servidor: ' . $e->getMessage() . ' en línea ' . $e->getLine()
            ], 500);
        }
    }

    public function dashboardSummary(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'No hay empresa asociada.'], 404);
        }

        $period = $request->input('period', now()->format('Y-m'));
        $year = substr($period, 0, 4);
        $month = strlen($period) > 4 ? substr($period, 5, 2) : now()->month;

        $ingresosMes = Invoice::where('company_id', $company->id)
            ->where('type', 'I')
            ->where('is_canceled', 0)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->sum('total');

        $egresosMes = Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', 0)
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->sum('total');

        $empleadosActivos = Employee::where('company_id', $company->id)->where('is_active', true)->count();

        $nominaMes = PayrollPeriod::where('company_id', $company->id)
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('start_date', $year)->whereMonth('start_date', $month)
                    ->orWhereYear('end_date', $year)->whereMonth('end_date', $month);
            })
            ->orderBy('id', 'desc')
            ->first();

        $costoNomina = $nominaMes ? (float) $nominaMes->total_gross : 0.0;
        $utilidadPreliminar = $ingresosMes - $egresosMes - $costoNomina;

        $prompt = "Eres Vera AI. Redacta un resumen ejecutivo de exactamente 3 viñetas breves analizando la salud de '{$company->legal_name}' para {$month}/{$year}.\n";
        $prompt .= "• Ventas: $" . number_format($ingresosMes, 2) . " MXN | Gastos: $" . number_format($egresosMes, 2) . " MXN\n";
        $prompt .= "• Nómina: $" . number_format($costoNomina, 2) . " MXN (" . ($nominaMes ? $nominaMes->period_name : 'Sin nómina') . ")\n";
        $prompt .= "• Utilidad preliminar: $" . number_format($utilidadPreliminar, 2) . " MXN | Plantilla: {$empleadosActivos} empleados\n";
        $prompt .= "Reglas: Moneda MXN. Tono directivo sobrio. Exactamente 3 viñetas con emojis (📈, 👥, 💰). Sin introducciones ni saludos.";

        $apiKey = env('CLAUDE_API_KEY');
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'Sin API Key'], 500);
        }

        try {
            $response = Http::timeout(20)->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post("https://api.anthropic.com/v1/messages", [
                "model"       => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
                "max_tokens"  => 300,
                "temperature" => 0.1,
                "messages"    => [["role" => "user", "content" => $prompt]]
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

    private function formatItems($items): string
    {
        if (empty($items)) return 'Sin conceptos detallados';
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            } else {
                return $items;
            }
        }

        if (is_array($items)) {
            $lines = [];
            foreach ($items as $it) {
                if (is_array($it)) {
                    $desc = $it['description'] ?? $it['descripcion'] ?? $it['name'] ?? 'Concepto';
                    $cant = $it['quantity'] ?? $it['cantidad'] ?? '';
                    $precio = isset($it['unit_price']) ? ('$' . number_format($it['unit_price'], 2)) : '';
                    $lines[] = "{$desc}" . ($cant ? " (Cant: {$cant})" : '') . ($precio ? " (P.U.: {$precio})" : '');
                }
            }
            return implode('; ', $lines);
        }
        return (string) $items;
    }

    private function safeString($value): string
    {
        if (is_null($value)) return 'N/D';
        if (is_string($value)) return $value;
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string) $value;
    }
}
