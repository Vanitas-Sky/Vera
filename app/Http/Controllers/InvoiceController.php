<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\XmlParserService;
use Illuminate\Http\Request;
use App\Services\DeducibilityMatrixService;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $company = Auth::user()->companies()->first();

        // Traemos las facturas ordenadas por fecha de emisión
        $invoices = Invoice::where('company_id', $company->id)
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function store(Request $request, XmlParserService $xmlParser)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml|max:2048', // Máximo 2MB
        ]);

        $company = Auth::user()->companies()->first();

        // Seguro 1: Evitar que el sistema truene si la empresa aún no ha configurado su RFC
        if (empty($company->rfc)) {
            return redirect()->back()->with('error', 'Acción denegada: Debes configurar el RFC de tu empresa en tu perfil antes de subir facturas.');
        }

        try {
            // Leemos el contenido crudo del archivo físico
            $xmlContent = file_get_contents($request->file('xml_file')->getRealPath());

            // Usamos tu servicio para extraer los datos
            $parsedData = $xmlParser->parse($xmlContent);

            // --- LÓGICA CORE: IDENTIFICACIÓN DE INGRESO / EGRESO ---
            $rfcEmisor = strtoupper(trim($parsedData['issuer_rfc']));
            $rfcReceptor = strtoupper(trim($parsedData['receiver_rfc']));
            $miRfc = strtoupper(trim($company->rfc));

            if ($rfcEmisor === $miRfc) {
                // Nosotros la emitimos = INGRESO
                // (Mantenemos el tipo I o lo forzamos por seguridad)
                $parsedData['type'] = 'I';
                $mensajeExito = 'Ingreso (Venta) registrado correctamente.';
            } elseif ($rfcReceptor === $miRfc) {
                // A nosotros nos la cobraron = EGRESO
                // Aquí sobrescribimos la "I" que viene en el XML del proveedor y la convertimos en "E"
                $parsedData['type'] = 'E';
                $mensajeExito = 'Egreso (Compra) registrado correctamente.';
            } else {
                // Seguro 2: El XML es de otra persona/empresa
                return redirect()->back()->with('error', "Auditoría fallida: Esta factura pertenece a {$rfcEmisor} y {$rfcReceptor}. No coincide con el RFC de tu empresa ({$miRfc}).");
            }
            // -------------------------------------------------------

            // Regla de Negocio: Evitar duplicados por Folio Fiscal (UUID)
            $exists = Invoice::where('uuid', $parsedData['uuid'])->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Esta factura ya fue subida y procesada anteriormente.');
            }

            // Inyectamos el ID de la empresa y guardamos
            $parsedData['company_id'] = $company->id;
            Invoice::create($parsedData);

            return redirect()->route('invoices.index')->with('success', $mensajeExito);
        } catch (\Exception $e) {
            // Si el servicio detecta que no es del SAT o algo falla, lo atrapamos
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
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
}
