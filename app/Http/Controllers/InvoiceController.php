<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\XmlParserService;
use Illuminate\Http\Request;
use App\Services\DeducibilityMatrixService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Registra tu empresa primero.');
        }

        $search = $request->input('search');
        $status = $request->input('status', 'activas');
        $type = $request->input('type', 'todas'); // Nuevo filtro

        $query = \App\Models\Invoice::where('company_id', $company->id);

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

        // Búsqueda Profunda (Live Search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('issuer_name', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                    ->orWhere('issuer_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('uuid', 'LIKE', "%{$search}%")
                    ->orWhere('items', 'LIKE', "%{$search}%") // Busca en los conceptos
                    ->orWhere('issue_date', 'LIKE', "%{$search}%");
            });
        }

        // Paginación con persistencia de URL
        $invoices = $query->orderBy('issue_date', 'desc')
            ->paginate(10)
            ->withQueryString(); // Esto ancla los filtros a la paginación

        return view('invoices.index', compact('invoices', 'search', 'status', 'type'));
    }
    public function store(Request $request, \App\Services\XmlParserService $parser)
    {
        $company = Auth::user()->companies()->first();

        $request->validate([
            'xml_file' => 'required|file|mimes:xml,zip,application/zip,application/x-zip-compressed|max:10240',
        ]);

        $file = $request->file('xml_file');
        $extension = strtolower($file->getClientOriginalExtension());

        // ==========================================
        // CASO A: INGESTA POR LOTES (ZIP)
        // ==========================================
        if ($extension === 'zip') {
            $zip = new \ZipArchive();

            if ($zip->open($file->getRealPath()) === TRUE) {
                $totalFiles = $zip->numFiles;
                $successCount = 0;
                $duplicateCount = 0;
                $errorCount = 0;

                $extractPath = storage_path('app/temp_zip_' . uniqid());
                $zip->extractTo($extractPath);
                $zip->close();

                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS));

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'xml') {
                        try {
                            $xmlContent = file_get_contents($fileInfo->getRealPath());
                            $data = $parser->parse($xmlContent);

                            // 1. Validar si la factura pertenece a la empresa
                            if ($data['issuer_rfc'] !== $company->rfc && $data['receiver_rfc'] !== $company->rfc) {
                                $errorCount++;
                                continue;
                            }

                            // 2. Lógica relativa: ¿Es Ingreso o Egreso para mi empresa?
                            if ($data['issuer_rfc'] === $company->rfc) {
                                $data['type'] = 'I'; // La emití = Venta
                            } else {
                                $data['type'] = 'E'; // La recibí = Gasto
                            }

                            // 3. Evitar duplicados por UUID
                            $exists = \App\Models\Invoice::where('company_id', $company->id)
                                ->where('uuid', $data['uuid'])
                                ->exists();

                            if ($exists) {
                                $duplicateCount++;
                                continue;
                            }

                            // 4. Guardar
                            \App\Models\Invoice::create(array_merge($data, ['company_id' => $company->id]));
                            $successCount++;
                        } catch (\Exception $e) {
                            $errorCount++;
                        }
                    }
                }

                \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

                $message = "Lote procesado. Exitosas: {$successCount} | Duplicadas (ignoradas): {$duplicateCount} | Errores / Ajenas: {$errorCount}.";
                return redirect()->route('invoices.index')->with('success', $message);
            }

            return redirect()->back()->with('error', 'No se pudo descomprimir el archivo ZIP. Puede estar dañado.');
        }

        // ==========================================
        // CASO B: ARCHIVO XML INDIVIDUAL
        // ==========================================
        try {
            $xmlContent = file_get_contents($file->getRealPath());
            $data = $parser->parse($xmlContent);

            // 1. Validar si pertenece a la empresa
            if ($data['issuer_rfc'] !== $company->rfc && $data['receiver_rfc'] !== $company->rfc) {
                return redirect()->back()->with('error', 'El RFC de esta factura no corresponde a tu empresa registrada.');
            }

            // 2. Lógica relativa: ¿Es Ingreso o Egreso para mi empresa?
            if ($data['issuer_rfc'] === $company->rfc) {
                $data['type'] = 'I'; // La emití = Venta
            } else {
                $data['type'] = 'E'; // La recibí = Gasto
            }

            // 3. Evitar duplicados
            $exists = \App\Models\Invoice::where('company_id', $company->id)
                ->where('uuid', $data['uuid'])
                ->exists();

            if ($exists) {
                return redirect()->back()->with('error', 'Esta factura ya se encuentra registrada en tu bóveda.');
            }

            // 4. Guardar
            \App\Models\Invoice::create(array_merge($data, ['company_id' => $company->id]));

            $tipoMensaje = $data['type'] === 'I' ? 'de Ingreso' : 'de Egreso (Gasto)';
            return redirect()->route('invoices.index')->with('success', "Factura {$tipoMensaje} integrada correctamente.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar el XML: ' . $e->getMessage());
        }
    }

    public function show($id, DeducibilityMatrixService $matrixService)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        $taxRegime = $company->tax_regime_code ?? '601'; // Default si no ha configurado su perfil

        $items = $invoice->items ?? [];
        $evaluatedItems = [];

        foreach ($items as $item) {
            // Extraemos la clave (si es una factura vieja sin clave, pasamos '00000000')
            $clave = $item['clave_prod_serv'] ?? '00000000';

            // Evaluamos con el Semáforo Inteligente
            $evaluation = $matrixService->evaluateConcept($clave, $taxRegime);

            // Inyectamos el resultado en el arreglo para la vista
            $item['status'] = $evaluation['status'];
            $item['reason'] = $evaluation['reason'];
            $item['clave_sat'] = $clave;

            $evaluatedItems[] = $item;
        }

        return view('invoices.show', compact('invoice', 'evaluatedItems'));
    }

    public function cancel($id)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        $invoice->update(['is_canceled' => true]);

        return redirect()->back()->with('success', 'Factura cancelada correctamente. Ya no afectará tus métricas fiscales.');
    }
}
