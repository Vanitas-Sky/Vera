<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Models\FixedExpense;
use App\Models\BankTransaction;
use App\Services\AiFiscalDeducibilityService;

class AiConsultantController extends Controller
{
    public function index()
    {
        $company = Auth::user()->companies()->first();
        return view('ai.consultant', compact('company'));
    }

    public function ask(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required|string|max:1000',
                'history' => 'nullable|array'
            ]);

            $company = Auth::user()->companies()->first();

            if (!$company) {
                return response()->json(['success' => false, 'error' => 'No hay una empresa asociada a tu cuenta.'], 404);
            }

            $rawQuestion = $request->input('question');
            $userQuestion = strtolower($rawQuestion);
            $hoy = Carbon::now();

            // =====================================================================
            // 1. EXTRACTOR DINÁMICO DE MESES Y RANGOS
            // =====================================================================
            $meses = [
                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
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

            // =====================================================================
            // 2. DETECTOR DE UUID O FOLIO PARA DICTAMEN UNITARIO
            // =====================================================================
            $uuidDetectado = null;
            if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $rawQuestion, $matches)) {
                $uuidDetectado = $matches[0];
            } elseif (preg_match('/[0-9a-fA-F]{8,}/', $rawQuestion, $matchesShort)) {
                $uuidDetectado = $matchesShort[0];
            }

            // =====================================================================
            // 3. CONFIGURACIÓN DEL CONTEXTO BASE DEL AGENTE
            // =====================================================================
            $giro = $company->industry ?? 'Restaurantes y Alimentos';
            $systemPrompt = "Eres Vera AI, el consultor fiscal, contable, laboral y de finanzas corporativas senior de Vera ERP.\n";
            $systemPrompt .= "Estás asesorando a la dirección de '{$company->legal_name}' (RFC: {$company->rfc}).\n";
            $systemPrompt .= "- Fecha actual de operación del sistema: " . $hoy->format('d/m/Y') . "\n";
            $systemPrompt .= "- Giro Comercial Oficial / Actividad Económica: {$giro}\n";
            $systemPrompt .= "- Moneda Oficial Obligatoria: PESOS MEXICANOS (MXN). Nunca uses USD ni hables de dólares.\n";
            if ($nombreMesFiltro) {
                $systemPrompt .= "- Filtro Temporal Detectado: Mes de {$nombreMesFiltro}\n";
            }
            $systemPrompt .= "\n--- BASE DE CONOCIMIENTO EXTRAÍDA EN TIEMPO REAL ---\n";

            $contexto = "";
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

                    $contexto .= "\n[EXPEDIENTE DE FACTURA SOLICITADA PARA DICTAMEN FISCAL UNITARIO]:\n";
                    $contexto .= "- UUID Oficial: {$facturaUnitaria->uuid}\n";
                    $contexto .= "- Tipo de Comprobante: {$tipoFac}\n";
                    $contexto .= "- Emisor (Proveedor): " . $this->safeString($facturaUnitaria->issuer_name) . " (RFC: {$facturaUnitaria->issuer_rfc})\n";
                    $contexto .= "- Receptor: " . $this->safeString($facturaUnitaria->receiver_name) . " (RFC: {$facturaUnitaria->receiver_rfc})\n";
                    $contexto .= "- Fecha de Emisión: {$fEmision}\n";
                    $contexto .= "- Importe Total: $" . number_format($facturaUnitaria->total, 2) . " MXN (IVA: $" . number_format($facturaUnitaria->iva ?? 0, 2) . ")\n";
                    $contexto .= "- Uso de CFDI registrado: " . $this->safeString($facturaUnitaria->cfdi_use) . "\n";
                    $contexto .= "- Conceptos / Partidas Compradas (items): {$itemsDecoded}\n";
                    $contexto .= "- Giro del Negocio registrado: {$giro}\n";

                    $datosExtraidos = true;
                } else {
                    $contexto .= "\n[AVISO]: Se buscó la factura con UUID/Folio '{$uuidDetectado}', pero no existe en la base de datos de la empresa.\n";
                    $datosExtraidos = true;
                }
            }

            // =====================================================================
            // ENRUTADOR 1: AUDITORÍA DE DEDUCIBILIDAD CON PRE-CÁLCULO MATEMÁTICO EN PHP
            // =====================================================================
            $esConsultaDeducibilidad = preg_match('/deducible|deducibilidad|giro|indispensable|auditar|auditoria|riesgo fiscal|actividad economica|no vaya/i', $userQuestion);

            if (!$uuidDetectado && $esConsultaDeducibilidad) {

                $gastosQuery = Invoice::where('company_id', $company->id)
                    ->where('is_canceled', 0)
                    ->where(function ($q) use ($company) {
                        $q->where('type', 'E')
                            ->orWhere('receiver_rfc', $company->rfc);
                    })
                    ->where('issuer_rfc', '!=', $company->rfc);

                $esHistorico = preg_match('/todo|todas|historico|anual|global|completo/i', $userQuestion);
                $periodoAuditoriaTxt = "";

                if ($mesFiltro) {
                    $gastosQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $mesFiltro);
                    $periodoAuditoriaTxt = "Mes de {$nombreMesFiltro} {$hoy->year}";
                } elseif (!$esHistorico) {
                    $gastosQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $hoy->month);
                    $periodoAuditoriaTxt = "Mes en curso (" . $hoy->format('m/Y') . ")";
                } else {
                    $periodoAuditoriaTxt = "Histórico Completo";
                }

                $gastosParaAuditar = $gastosQuery->orderBy('issue_date', 'desc')->take(25)->get();

                // CÁLCULO MATEMÁTICO CONTROLADO EN PHP
                $matrixService = app(\App\Services\DeducibilityMatrixService::class);
                $taxRegime = $company->tax_regime_code ?? '601';

                $cVerde = 0; $mVerde = 0.0;
                $cAmarillo = 0; $mAmarillo = 0.0;
                $cRojo = 0; $mRojo = 0.0;
                $detalleLineas = [];

                foreach ($gastosParaAuditar as $g) {
                    $items = is_array($g->items) ? $g->items : (json_decode($g->items, true) ?? []);
                    $peorStatus = 'VERDE';
                    $motivoPeor = 'Gasto operativo indispensable alineado a la actividad.';

                    foreach ($items as $it) {
                        $clave = $it['clave_prod_serv'] ?? $it['ClaveProdServ'] ?? $it['sat_code'] ?? '00000000';
                        $eval = $matrixService->evaluateConcept($clave, $taxRegime);
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
                        $cRojo++; $mRojo += (float) $g->total;
                    } elseif ($peorStatus === 'AMARILLO') {
                        $cAmarillo++; $mAmarillo += (float) $g->total;
                    } else {
                        $cVerde++; $mVerde += (float) $g->total;
                    }

                    $fGasto = Carbon::parse($g->issue_date)->format('d/m/Y');
                    $itemsTxt = $this->formatItems($g->items);
                    $detalleLineas[] = "  * UUID: {$g->uuid} | Fecha: {$fGasto} | Proveedor: {$g->issuer_name} ({$g->issuer_rfc}) | Total: $" . number_format($g->total, 2) . " MXN | Semáforo: {$peorStatus} | Partidas: {$itemsTxt} | Justificación: {$motivoPeor}";
                }

                $totalAuditoria = $mVerde + $mAmarillo + $mRojo;
                $cantTotal = $cVerde + $cAmarillo + $cRojo;

                $contexto .= "\n[AUDITORÍA FISCAL DE DEDUCIBILIDAD DE EGRESOS - CÁLCULOS EXACTOS DE VERAFISCAL]:\n";
                $contexto .= "- Período Evaluado: {$periodoAuditoriaTxt}\n";
                $contexto .= "- Giro Comercial Oficial: {$giro}\n";
                $contexto .= "- TOTAL EGRESOS AUDITADOS: {$cantTotal} comprobantes | Importe Total: $" . number_format($totalAuditoria, 2) . " MXN\n";
                $contexto .= "- CÁLCULOS MATEMÁTICOS OBLIGATORIOS PARA TU TABLA RESUMEN:\n";
                $contexto .= "  * 🟢 DEDUCIBLES (VERDE): {$cVerde} facturas | Total: $" . number_format($mVerde, 2) . " MXN\n";
                $contexto .= "  * 🟡 RIESGO FISCAL (AMARILLO): {$cAmarillo} facturas | Total: $" . number_format($mAmarillo, 2) . " MXN\n";
                $contexto .= "  * 🔴 NO DEDUCIBLES (ROJO): {$cRojo} facturas | Total: $" . number_format($mRojo, 2) . " MXN\n";
                $contexto .= "- EXPEDIENTE DE COMPROBANTES AUDITADOS:\n" . implode("\n", $detalleLineas) . "\n";

                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 2: GASTOS FIJOS OPERATIVOS (OpEx / RENTAS / CONTRATOS)
            // =====================================================================
            if (preg_match('/renta|servicio|internet|fijo|contrato|opex|luz|telefono|seguro|arrendamiento|meses|deber|pagado/i', $userQuestion)) {
                $gastosFijos = FixedExpense::where('company_id', $company->id)->get();

                $contexto .= "\n[MÓDULO DE GASTOS FIJOS OPERATIVOS (OpEx)]\n";
                $contexto .= "- Compromiso mensual recurrente: $" . number_format($gastosFijos->where('is_active', true)->sum('monthly_amount'), 2) . " MXN\n";
                $contexto .= "- CONTRATOS Y ANÁLISIS DE TIEMPO (Calculado a hoy " . $hoy->format('d/m/Y') . "):\n";

                foreach ($gastosFijos as $gf) {
                    $descGf = $this->safeString($gf->description);
                    $fInicio = $gf->contract_start_date ? Carbon::parse($gf->contract_start_date) : null;

                    $detalleTiempo = "Sin fecha de inicio registrada.";
                    if ($fInicio) {
                        $mesesDeberian = $fInicio->diffInMonths($hoy);
                        if ($hoy->greaterThanOrEqualTo($fInicio)) {
                            $mesesDeberian = max(1, $mesesDeberian + 1);
                        }
                        $totalDeberiaPagado = $mesesDeberian * $gf->monthly_amount;
                        $detalleTiempo = "Inició el {$fInicio->format('d/m/Y')}. A la fecha actual han transcurrido {$mesesDeberian} meses. Se debieron haber pagado {$mesesDeberian} mensualidades acumulando $" . number_format($totalDeberiaPagado, 2) . " MXN.";
                    }

                    $facturasProv = Invoice::where('company_id', $company->id)
                        ->where('issuer_name', 'like', "%{$gf->provider_name}%")
                        ->count();

                    $contexto .= "  * [{$gf->category}] Proveedor: {$gf->provider_name} | Mensualidad: $" . number_format($gf->monthly_amount, 2) . " MXN | Día límite de cobro: {$gf->due_day} de cada mes.\n";
                    $contexto .= "    - Cálculo de períodos: {$detalleTiempo}\n";
                    $contexto .= "    - Facturas registradas en el sistema de este proveedor: {$facturasProv} comprobantes.\n";
                    $contexto .= "    - Descripción del servicio: {$descGf}\n";
                }
                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 3: NÓMINA, SEGURIDAD SOCIAL Y EXPEDIENTES LABORALES
            // =====================================================================
            $pideNomina = preg_match('/empleado|nomina|sueldo|salario|isr|imss|retencion|deduccion|cuota|obrera|patronal|contrat|alta/i', $userQuestion);
            $todosEmpleados = Employee::where('company_id', $company->id)->get();
            $empleadosCoincidentes = collect();

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

            if ($pideNomina || $empleadosCoincidentes->isNotEmpty()) {
                $contexto .= "\n[MÓDULO DE RECURSOS HUMANOS Y NÓMINA]\n";

                $nominaQuery = PayrollPeriod::where('company_id', $company->id);
                if ($mesFiltro) {
                    $nominaQuery->where(function ($q) use ($mesFiltro, $nombreMesFiltro) {
                        $q->where('period_name', 'like', "%{$nombreMesFiltro}%")
                            ->orWhereMonth('start_date', $mesFiltro)
                            ->orWhereMonth('end_date', $mesFiltro);
                    });
                }
                $periodoNomina = $nominaQuery->orderBy('id', 'desc')->first();

                if ($periodoNomina) {
                    $contexto .= "- PERÍODO: {$periodoNomina->period_name}\n";
                    $contexto .= "  * Sueldos Brutos: $" . number_format($periodoNomina->total_gross, 2) . " | ISR: $" . number_format($periodoNomina->total_isr_retention, 2) . " | IMSS Obrero: $" . number_format($periodoNomina->total_imss_employee, 2) . " | IMSS Patronal: $" . number_format($periodoNomina->total_imss_employer ?? 0, 2) . " | Neto: $" . number_format($periodoNomina->total_net, 2) . " MXN\n";

                    $detallesQuery = PayrollDetail::where('payroll_period_id', $periodoNomina->id);
                    if ($empleadosCoincidentes->isNotEmpty()) {
                        $detallesQuery->whereIn('employee_id', $empleadosCoincidentes->pluck('id'));
                    }
                    $detalles = $detallesQuery->get();

                    if ($detalles->isNotEmpty()) {
                        $contexto .= "- RECIBOS DEL PERÍODO:\n";
                        foreach ($detalles as $det) {
                            $emp = $todosEmpleados->firstWhere('id', $det->employee_id) ?? Employee::find($det->employee_id);
                            $nombre = $emp ? $emp->full_name : "Empleado #{$det->employee_id}";
                            $rfc = $emp ? $emp->rfc : "N/D";
                            $puesto = $emp ? $emp->position : "General";
                            $periodicidad = $emp ? ($emp->periodicity ?? 'Quincenal') : 'Quincenal';

                            $otrasDeducciones = number_format($det->total_custom_deductions ?? 0, 2);
                            $desgloseOtras = $this->safeString($det->custom_deductions_breakdown);

                            $contexto .= "  * {$nombre} (RFC: {$rfc} | Puesto: {$puesto} | Periodicidad: {$periodicidad}):\n";
                            $contexto .= "    - Bruto: $" . number_format($det->gross_salary, 2) . " | ISR: $" . number_format($det->isr_retention, 2) . " | IMSS: $" . number_format($det->imss_employee, 2) . " | Otras: $" . $otrasDeducciones . " (" . $desgloseOtras . ") | Neto: $" . number_format($det->net_salary, 2) . " MXN\n";
                        }
                    }
                }

                if ($empleadosCoincidentes->isNotEmpty()) {
                    $contexto .= "- EXPEDIENTES LABORALES DETALLADOS:\n";
                    foreach ($empleadosCoincidentes as $empC) {
                        $fAlta = $empC->hire_date ? Carbon::parse($empC->hire_date)->format('d/m/Y') : ($empC->created_at ? $empC->created_at->format('d/m/Y') : 'Fecha no especificada');
                        $contexto .= "  * Nombre: {$empC->full_name} | RFC: {$empC->rfc} | CURP: {$empC->curp} | Puesto: {$empC->position} | Salario Base: $" . number_format($empC->base_salary, 2) . " | Periodicidad: {$empC->periodicity} | Fecha de Contratación/Alta: {$fAlta} | Estatus: " . ($empC->is_active ? 'Activo' : 'Inactivo') . "\n";
                    }
                }

                $datosExtraidos = true;
            }

            // =====================================================================
            // ENRUTADOR 4: FACTURACIÓN GENERAL Y BALANCE (POR MES POR DEFECTO)
            // =====================================================================
            $pideFacturacion = preg_match('/factura|ingreso|cobro|venta|egreso|compra|sat|cfdi|gasto|balance|utilidad|flujo/i', $userQuestion);

            $stopWords = ['para', 'como', 'sobre', 'dime', 'factura', 'facturas', 'cuanto', 'quien', 'donde', 'tiene', 'tienen', 'esta', 'este', 'estas', 'estos', 'folio', 'monto', 'total', 'gasto', 'gastos', 'ingreso', 'ingresos', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre', 'resumen', 'dame', 'muestra', 'nuestra', 'nuestro', 'empresa', 'alguna', 'guardada', 'guardadas'];
            $palabrasPregunta = preg_split('/\s+/', preg_replace('/[^\w\s]/u', '', $userQuestion));
            $terminosBusqueda = [];
            foreach ($palabrasPregunta as $pal) {
                if (strlen($pal) >= 4 && !in_array($pal, $stopWords)) {
                    $terminosBusqueda[] = $pal;
                }
            }

            if (!$esConsultaDeducibilidad && ($pideFacturacion || !empty($terminosBusqueda))) {

                $invoicesQuery = Invoice::where('company_id', $company->id)->where('is_canceled', 0);
                $esHistorico = preg_match('/todo|todas|historico|anual|global|completo/i', $userQuestion);
                $periodoBalanceTxt = "";

                // Delimitación por mes actual si no se pidió otro
                if ($mesFiltro) {
                    $invoicesQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $mesFiltro);
                    $periodoBalanceTxt = "Mes de {$nombreMesFiltro} {$hoy->year}";
                } elseif (!$esHistorico) {
                    $invoicesQuery->whereYear('issue_date', $hoy->year)->whereMonth('issue_date', $hoy->month);
                    $periodoBalanceTxt = "Mes en curso (" . $hoy->format('m/Y') . ")";
                } else {
                    $periodoBalanceTxt = "Histórico Completo";
                }

                $facturasEspecificas = collect();
                if (!empty($terminosBusqueda)) {
                    $specQuery = (clone $invoicesQuery);
                    $specQuery->where(function ($q) use ($terminosBusqueda) {
                        foreach ($terminosBusqueda as $term) {
                            $q->orWhere('issuer_name', 'like', "%{$term}%")
                                ->orWhere('receiver_name', 'like', "%{$term}%")
                                ->orWhere('items', 'like', "%{$term}%")
                                ->orWhere('issuer_rfc', 'like', "%{$term}%")
                                ->orWhere('receiver_rfc', 'like', "%{$term}%")
                                ->orWhere('uuid', 'like', "%{$term}%");
                        }
                    });
                    $facturasEspecificas = $specQuery->take(8)->get();
                }

                $totalIngresos = (clone $invoicesQuery)->where('type', 'I')->sum('total');
                $totalEgresos = (clone $invoicesQuery)->where('type', 'E')->sum('total');
                $margenOperativo = $totalIngresos - $totalEgresos;

                $contexto .= "\n[MÓDULO DE FACTURACIÓN Y BALANCE FINANCIERO - PERÍODO: {$periodoBalanceTxt}]:\n";
                $contexto .= "- Ventas / Ingresos Facturados: $" . number_format($totalIngresos, 2) . " MXN\n";
                $contexto .= "- Gastos / Egresos Facturados: $" . number_format($totalEgresos, 2) . " MXN\n";
                $contexto .= "- Margen Operativo Preliminar: $" . number_format($margenOperativo, 2) . " MXN\n";

                $listaParaMostrar = $facturasEspecificas->isNotEmpty() ? $facturasEspecificas : (clone $invoicesQuery)->orderBy('issue_date', 'desc')->take(6)->get();

                if ($listaParaMostrar->isNotEmpty()) {
                    $contexto .= "- LISTADO DE COMPROBANTES DEL PERÍODO:\n";
                    foreach ($listaParaMostrar as $fac) {
                        $tipo = $fac->type === 'I' ? 'INGRESO' : 'GASTO/EGRESO';
                        $fecha = Carbon::parse($fac->issue_date)->format('d/m/Y');
                        $descSegura = $this->formatItems($fac->items);
                        $usoSeguro = $this->safeString($fac->cfdi_use);
                        $emisor = $this->safeString($fac->issuer_name);
                        $receptor = $this->safeString($fac->receiver_name);

                        $contexto .= "  * [{$tipo}] UUID: {$fac->uuid} | Fecha: {$fecha} | Emisor: {$emisor} ({$fac->issuer_rfc}) | Receptor: {$receptor} | Total: $" . number_format($fac->total, 2) . " MXN | Conceptos: {$descSegura} | Uso CFDI: {$usoSeguro}\n";
                    }
                }
                $datosExtraidos = true;
            }

            // =====================================================================
            // 4. ENSAMBLAR EL PROMPT FINAL
            // =====================================================================
            if ($datosExtraidos) {
                $systemPrompt .= $contexto;
            } else {
                $systemPrompt .= "Responde con tu conocimiento fiscal y corporativo general mexicano.\n";
            }

            $systemPrompt .= "\n-------------------------------------------------------\n";
            $systemPrompt .= "REGLAS CRÍTICAS:\n";
            $systemPrompt .= "1. En auditorías de deducibilidad, utiliza OBLIGATORIAMENTE los totales de la sección 'CÁLCULOS MATEMÁTICOS OBLIGATORIOS PARA TU TABLA RESUMEN'. NO recalcules ni modifiques las cantidades ni los montos.\n";
            $systemPrompt .= "2. Respeta los semáforos: 🟢 Deducible (Verde), 🟡 Riesgo Fiscal (Amarillo - requiere acreditar materialidad bajo Art. 69-B CFF) y 🔴 No Deducible (Rojo - Art. 28 LISR).\n";
            $systemPrompt .= "3. Moneda: Exclusivamente PESOS MEXICANOS (MXN). JAMÁS uses USD.\n";
            $systemPrompt .= "4. Si el usuario consulta sobre contratos o internet (OpEx), explica claramente cuántos meses han transcurrido a la fecha actual y cuánto dinero se debió haber cubierto.\n";
            $systemPrompt .= "5. Si el usuario hace preguntas de seguimiento ('¿y cuándo se contrató?', '¿cuánto gana él?'), usa el historial para saber a qué empleado se refiere.\n";
            $systemPrompt .= "6. Formato Markdown ejecutivo con tablas y viñetas limpias.";

            // =====================================================================
            // 5. CONSTRUCCIÓN DE MEMORIA CON TODA LA SESIÓN ABIERTA
            // =====================================================================
            $messages = [];
            $history = $request->input('history', []);

            if (is_array($history) && !empty($history)) {
                $historySlice = array_slice($history, -30);
                foreach ($historySlice as $item) {
                    if (isset($item['role']) && isset($item['content']) && in_array($item['role'], ['user', 'assistant'])) {
                        $messages[] = [
                            'role' => $item['role'],
                            'content' => (string) $item['content']
                        ];
                    }
                }
            }

            $messages[] = [
                'role' => 'user',
                'content' => $rawQuestion
            ];

            // LLAMADA A ANTHROPIC CLAUDE
            $payload = [
                'model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
                'max_tokens' => 1500,
                'temperature' => 0.1,
                'system' => $systemPrompt,
                'messages' => $messages
            ];

            $response = Http::timeout(45)->withHeaders([
                'x-api-key' => env('CLAUDE_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['content'][0]['text'] ?? 'No se pudo generar respuesta.';
                return response()->json(['success' => true, 'answer' => $answer]);
            }

            $errorMsg = $response->json()['error']['message'] ?? 'Error desconocido de API';
            return response()->json(['success' => false, 'error' => "Claude API ({$response->status()}): {$errorMsg}"], 500);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Excepción en Servidor: ' . $e->getMessage() . ' en línea ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Decodifica y formatea el campo 'items' de forma segura.
     */
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
            $matrixService = app(\App\Services\DeducibilityMatrixService::class);
            $lines = [];

            foreach ($items as $it) {
                if (is_array($it)) {
                    $desc = $it['description'] ?? $it['descripcion'] ?? $it['name'] ?? $it['concepto'] ?? json_encode($it, JSON_UNESCAPED_UNICODE);
                    $cant = $it['quantity'] ?? $it['cantidad'] ?? '';
                    $precio = isset($it['unit_price']) ? ('$' . number_format($it['unit_price'], 2)) : (isset($it['valor_unitario']) ? ('$' . number_format($it['valor_unitario'], 2)) : '');
                    $clave = $it['sat_code'] ?? $it['clave_prod_serv'] ?? $it['clave_sat'] ?? $it['ClaveProdServ'] ?? '00000000';

                    // Obtener o resolver estatus del sistema
                    $status = $it['status'] ?? null;
                    $reason = $it['reason'] ?? null;

                    if (!$status && class_exists(\App\Services\DeducibilityMatrixService::class)) {
                        $eval = $matrixService->evaluateConcept($clave, '601');
                        $status = $eval['status'];
                        $reason = $eval['reason'];
                    }

                    $part = (string) $desc;
                    if ($cant) $part .= " (Cant: {$cant})";
                    if ($precio) $part .= " (P.U.: {$precio})";
                    if ($clave) $part .= " [Clave SAT: {$clave}]";
                    if ($status) $part .= " [Semáforo Sistema: {$status}]";
                    if ($reason) $part .= " [Motivo: {$reason}]";

                    $lines[] = $part;
                } else {
                    $lines[] = (string) $it;
                }
            }
            return implode('; ', $lines);
        }

        return (string) $items;
    }

    /**
     * Helper para garantizar que cualquier dato sea string seguro.
     */
    private function safeString($value): string
    {
        if (is_null($value)) return 'N/D';
        if (is_string($value)) return $value;
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string) $value;
    }

    public function dashboardSummary(Request $request)
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return response()->json(['success' => false, 'error' => 'No hay empresa asociada.'], 404);
        }

        // 1. Sincronizar con el periodo seleccionado en el panel (o el mes actual por defecto)
        $period = $request->input('period', now()->format('Y-m'));
        $year = substr($period, 0, 4);
        $month = strlen($period) > 4 ? substr($period, 5, 2) : now()->month;

        // 2. Facturación filtrada exactamente por el mismo mes del panel
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

        // Nómina del periodo seleccionado
        $nominaMes = PayrollPeriod::where('company_id', $company->id)
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('start_date', $year)->whereMonth('start_date', $month)
                    ->orWhereYear('end_date', $year)->whereMonth('end_date', $month);
            })
            ->orderBy('id', 'desc')
            ->first() ?? PayrollPeriod::where('company_id', $company->id)->orderBy('id', 'desc')->first();

        $costoNomina = $nominaMes ? (float) $nominaMes->total_gross : 0.0;
        $utilidadPreliminar = $ingresosMes - $egresosMes - $costoNomina;

        // 3. Prompt con moneda MXN explícita y coherencia con las tarjetas
        $prompt = "Eres Vera AI. Redacta un resumen ejecutivo de máximo 3 viñetas cortas analizando la salud financiera de '{$company->legal_name}' para el período {$month}/{$year}.\n\n";
        $prompt .= "--- DATOS EXACTOS EN TIEMPO REAL (TODOS EN PESOS MEXICANOS MXN) ---\n";
        $prompt .= "- Giro registrado: " . ($company->industry ?? 'Restaurantes y Alimentos') . "\n";
        $prompt .= "- Ventas facturadas (Ingresos): $" . number_format($ingresosMes, 2) . " MXN\n";
        $prompt .= "- Gastos facturados (Egresos): $" . number_format($egresosMes, 2) . " MXN\n";
        $prompt .= "- Costo operativo de nómina: $" . number_format($costoNomina, 2) . " MXN (" . ($nominaMes ? $nominaMes->period_name : 'Sin nómina') . ")\n";
        $prompt .= "- Utilidad neta preliminar del período: $" . number_format($utilidadPreliminar, 2) . " MXN\n";
        $prompt .= "- Empleados activos en plantilla: {$empleadosActivos}\n";
        $prompt .= "-----------------------------------------------------------------\n\n";
        $prompt .= "REGLAS CRÍTICAS:\n";
        $prompt .= "1. La moneda es obligatoriamente PESOS MEXICANOS (MXN). JAMÁS uses USD ni hables de dólares.\n";
        $prompt .= "2. Si la utilidad neta preliminar es positiva, reconoce la solvencia y salud operativa del negocio.\n";
        $prompt .= "3. Tono ejecutivo de nivel directivo. Máximo 3 viñetas breves. NO saludes ni te despidas.";

        $payload = [
            "model" => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
            "max_tokens" => 400,
            "temperature" => 0.1,
            "system" => "Eres un director de finanzas corporativas (CFO) mexicano.",
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ]
        ];

        try {
            $response = Http::timeout(30)->withHeaders([
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
