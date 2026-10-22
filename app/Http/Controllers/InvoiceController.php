<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\XmlParserService;
use App\Services\DeducibilityMatrixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Registra tu empresa primero.');
        }

        $search = $request->input('search');
        $status = $request->input('status', 'activas');
        $type = $request->input('type', 'todas');

        // Filtro de Periodo (Por defecto el mes actual en formato YYYY-MM)
        $period = $request->input('period', now()->format('Y-m'));

        $query = Invoice::where('company_id', $company->id);

        // Filtro de Estatus
        if ($status === 'canceladas') {
            $query->where('is_canceled', true);
        } elseif ($status === 'activas') {
            $query->where('is_canceled', false);
        }

        // Filtro de Tipo
        if ($type === 'I' || $type === 'E') {
            $query->where('type', $type);
        }

        // Filtro por Fecha
        if (!empty($period)) {
            $year = substr($period, 0, 4);
            $query->whereYear('issue_date', $year);

            if (strlen($period) > 4) {
                $month = substr($period, 5, 2);
                $query->whereMonth('issue_date', $month);
            }
        }

        // Búsqueda Profunda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('issuer_name', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                    ->orWhere('issuer_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('uuid', 'LIKE', "%{$search}%")
                    ->orWhere('items', 'LIKE', "%{$search}%")
                    ->orWhere('issue_date', 'LIKE', "%{$search}%");
            });
        }

        $totalAmount = (clone $query)->sum('total');
        $totalIva = (clone $query)->sum('iva');

        $invoices = $query->orderBy('issue_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact(
            'invoices',
            'search',
            'status',
            'type',
            'period',
            'totalAmount',
            'totalIva'
        ));
    }

    public function store(Request $request, XmlParserService $parser, DeducibilityMatrixService $matrixService)
    {
        $company = Auth::user()->companies()->first();

        $request->validate([
            'xml_file' => 'required|file|mimes:xml,zip,application/zip,application/x-zip-compressed|max:10240',
        ]);

        $file = $request->file('xml_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $taxRegime = $company->tax_regime_code ?? '601';

        // Estructura del Resumen Flash del Lote
        $summary = [
            'total_files' => 0,
            'success_count' => 0,
            'duplicate_count' => 0,
            'error_count' => 0,
            'verde_count' => 0,
            'verde_amount' => 0.0,
            'amarillo_count' => 0,
            'amarillo_amount' => 0.0,
            'rojo_count' => 0,
            'rojo_amount' => 0.0,
            'natural_summary' => '',
        ];

        // ==========================================
        // CASO A: INGESTA POR LOTES (ZIP)
        // ==========================================
        if ($extension === 'zip') {
            $zip = new ZipArchive();

            if ($zip->open($file->getRealPath()) === TRUE) {
                $summary['total_files'] = $zip->numFiles;

                $extractPath = storage_path('app/temp_zip_' . uniqid());
                $zip->extractTo($extractPath);
                $zip->close();

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'xml') {
                        try {
                            $xmlContent = file_get_contents($fileInfo->getRealPath());
                            $data = $parser->parse($xmlContent);

                            // 1. Validar RFC
                            if ($data['issuer_rfc'] !== $company->rfc && $data['receiver_rfc'] !== $company->rfc) {
                                $summary['error_count']++;
                                continue;
                            }

                            // 2. Determinar Tipo
                            $data['type'] = ($data['issuer_rfc'] === $company->rfc) ? 'I' : 'E';

                            // 3. Evitar duplicados por UUID
                            $exists = Invoice::where('company_id', $company->id)
                                ->where('uuid', $data['uuid'])
                                ->exists();

                            if ($exists) {
                                $summary['duplicate_count']++;
                                continue;
                            }

                            // 4. Evaluar y Sellar Deducibilidad en cada Partida (items)
                            $items = $data['items'] ?? [];
                            $evaluatedItems = [];
                            $facturaPeorStatus = 'VERDE';

                            foreach ($items as $item) {
                                $clave = $item['clave_prod_serv'] ?? $item['ClaveProdServ'] ?? $item['sat_code'] ?? '00000000';

                                if ($data['type'] === 'E') {
                                    $eval = $matrixService->evaluateConcept($clave, $taxRegime);

                                    $statusClean = strtoupper(trim($eval['status'] ?? 'VERDE'));
                                    $item['status'] = $statusClean;
                                    $item['reason'] = $eval['reason'] ?? '';

                                    // Ponderación de riesgo
                                    if (str_contains($statusClean, 'ROJO') || str_contains($statusClean, 'NO')) {
                                        $facturaPeorStatus = 'ROJO';
                                    } elseif ((str_contains($statusClean, 'AMARILLO') || str_contains($statusClean, 'RIESGO')) && $facturaPeorStatus !== 'ROJO') {
                                        $facturaPeorStatus = 'AMARILLO';
                                    }
                                } else {
                                    $item['status'] = 'VERDE';
                                    $item['reason'] = 'Ingreso facturado por la empresa.';
                                }

                                $item['clave_sat'] = $clave;
                                $evaluatedItems[] = $item;
                            }

                            $data['items'] = $evaluatedItems;

                            // 5. Guardar Factura
                            Invoice::create(array_merge($data, ['company_id' => $company->id]));
                            $summary['success_count']++;

                            // Sumar al contador del resumen
                            if ($data['type'] === 'E') {
                                if ($facturaPeorStatus === 'ROJO') {
                                    $summary['rojo_count']++;
                                    $summary['rojo_amount'] += (float) $data['total'];
                                } elseif ($facturaPeorStatus === 'AMARILLO') {
                                    $summary['amarillo_count']++;
                                    $summary['amarillo_amount'] += (float) $data['total'];
                                } else {
                                    $summary['verde_count']++;
                                    $summary['verde_amount'] += (float) $data['total'];
                                }
                            }

                        } catch (\Exception $e) {
                            $summary['error_count']++;
                        }
                    }
                }

                File::deleteDirectory($extractPath);

                // Generar la narrativa en lenguaje natural
                $summary['natural_summary'] = $this->generateBatchNarrative($summary, $company);

                return redirect()->route('invoices.index')
                    ->with('import_summary', $summary)
                    ->with('success', "Procesamiento de lote finalizado: {$summary['success_count']} facturas integradas.");
            }

            return redirect()->back()->with('error', 'No se pudo descomprimir el archivo ZIP.');
        }

        // ==========================================
        // CASO B: ARCHIVO XML INDIVIDUAL
        // ==========================================
        try {
            $xmlContent = file_get_contents($file->getRealPath());
            $data = $parser->parse($xmlContent);

            if ($data['issuer_rfc'] !== $company->rfc && $data['receiver_rfc'] !== $company->rfc) {
                return redirect()->back()->with('error', 'El RFC de esta factura no corresponde a tu empresa registrada.');
            }

            $data['type'] = ($data['issuer_rfc'] === $company->rfc) ? 'I' : 'E';

            $exists = Invoice::where('company_id', $company->id)
                ->where('uuid', $data['uuid'])
                ->exists();

            if ($exists) {
                return redirect()->back()->with('error', 'Esta factura ya se encuentra registrada en tu bóveda.');
            }

            $items = $data['items'] ?? [];
            $evaluatedItems = [];
            $facturaPeorStatus = 'VERDE';

            foreach ($items as $item) {
                $clave = $item['clave_prod_serv'] ?? $item['ClaveProdServ'] ?? $item['sat_code'] ?? '00000000';

                if ($data['type'] === 'E') {
                    $eval = $matrixService->evaluateConcept($clave, $taxRegime);

                    $statusClean = strtoupper(trim($eval['status'] ?? 'VERDE'));
                    $item['status'] = $statusClean;
                    $item['reason'] = $eval['reason'] ?? '';

                    if (str_contains($statusClean, 'ROJO') || str_contains($statusClean, 'NO')) {
                        $facturaPeorStatus = 'ROJO';
                    } elseif ((str_contains($statusClean, 'AMARILLO') || str_contains($statusClean, 'RIESGO')) && $facturaPeorStatus !== 'ROJO') {
                        $facturaPeorStatus = 'AMARILLO';
                    }
                } else {
                    $item['status'] = 'VERDE';
                    $item['reason'] = 'Ingreso facturado por la empresa.';
                }

                $item['clave_sat'] = $clave;
                $evaluatedItems[] = $item;
            }

            $data['items'] = $evaluatedItems;

            Invoice::create(array_merge($data, ['company_id' => $company->id]));

            $summary['total_files'] = 1;
            $summary['success_count'] = 1;

            if ($data['type'] === 'E') {
                if ($facturaPeorStatus === 'ROJO') {
                    $summary['rojo_count'] = 1;
                    $summary['rojo_amount'] = (float) $data['total'];
                } elseif ($facturaPeorStatus === 'AMARILLO') {
                    $summary['amarillo_count'] = 1;
                    $summary['amarillo_amount'] = (float) $data['total'];
                } else {
                    $summary['verde_count'] = 1;
                    $summary['verde_amount'] = (float) $data['total'];
                }
            }

            // Generar la narrativa en lenguaje natural
            $summary['natural_summary'] = $this->generateBatchNarrative($summary, $company);

            return redirect()->route('invoices.index')
                ->with('import_summary', $summary)
                ->with('success', 'Factura integrada y evaluada fiscalmente con éxito.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar el XML: ' . $e->getMessage());
        }
    }

    public function show($id, DeducibilityMatrixService $matrixService)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);
        $taxRegime = $company->tax_regime_code ?? '601';

        $items = $invoice->items ?? [];
        $evaluatedItems = [];

        foreach ($items as $item) {
            if (isset($item['status']) && isset($item['reason'])) {
                $evaluatedItems[] = $item;
            } else {
                $clave = $item['clave_prod_serv'] ?? $item['ClaveProdServ'] ?? $item['sat_code'] ?? '00000000';
                $evaluation = $matrixService->evaluateConcept($clave, $taxRegime);

                $item['status'] = strtoupper(trim($evaluation['status'] ?? 'VERDE'));
                $item['reason'] = $evaluation['reason'] ?? '';
                $item['clave_sat'] = $clave;
                $evaluatedItems[] = $item;
            }
        }

        return view('invoices.show', compact('invoice', 'evaluatedItems'));
    }

    public function cancel($id)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        $invoice->update(['is_canceled' => true]);

        return redirect()->back()->with('success', 'Factura cancelada correctamente. Ya no afectará tus métricas fiscales.');
    }

    /**
     * Generador de Dictamen Ejecutivo en Lenguaje Natural
     */
    private function generateBatchNarrative(array $summary, $company): string
    {
        $totalGastos = $summary['verde_amount'] + $summary['amarillo_amount'] + $summary['rojo_amount'];
        $totalComprobantes = $summary['verde_count'] + $summary['amarillo_count'] + $summary['rojo_count'];

        if ($totalComprobantes === 0) {
            return "Se procesaron {$summary['success_count']} comprobantes sin movimientos de egresos sujetos a evaluación de deducibilidad.";
        }

        $pctVerde = $totalGastos > 0 ? round(($summary['verde_amount'] / $totalGastos) * 100, 1) : 0;
        $montoTotalFormateado = '$' . number_format($totalGastos, 2) . ' MXN';

        // Escenario 1: 100% deducible
        if ($summary['rojo_count'] === 0 && $summary['amarillo_count'] === 0) {
            return "Dictamen Favorable: El 100% de los comprobantes ingresados ({$montoTotalFormateado}) corresponde a gastos operativos indispensables alineados al giro de la empresa, sin contingencias ni riesgos detectados ante el SAT.";
        }

        // Escenario 2: Lote Mixto con contingencias
        $narrativa = "Se procesaron {$totalComprobantes} facturas de gastos por un total de {$montoTotalFormateado}. ";
        $narrativa .= "El {$pctVerde}% del importe ($" . number_format($summary['verde_amount'], 2) . " MXN) cumple con estricta indispensabilidad operativa (Art. 27 LISR). ";

        if ($summary['amarillo_count'] > 0) {
            $narrativa .= "Se detectaron {$summary['amarillo_count']} facturas con riesgo fiscal ($" . number_format($summary['amarillo_amount'], 2) . " MXN) que requieren contratos, entregables o bitácoras para acreditar materialidad (Art. 69-B CFF). ";
        }

        if ($summary['rojo_count'] > 0) {
            $narrativa .= "Se identificaron {$summary['rojo_count']} comprobantes no deducibles ($" . number_format($summary['rojo_amount'], 2) . " MXN) por conceptos restringidos expresamente bajo el Art. 28 de la LISR.";
        }

        return $narrativa;
    }
}