<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PacSandboxService;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BillingController extends Controller
{
    public function create()
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Debes registrar una empresa antes de facturar.');
        }

        // Catálogos Oficiales del SAT (Simplificados para el MVP)
        $usosCfdi = [
            'G01' => 'G01 - Adquisición de mercancías',
            'G03' => 'G03 - Gastos en general',
            'P01' => 'P01 - Por definir',
            'S01' => 'S01 - Sin efectos fiscales'
        ];

        $metodosPago = [
            'PUE' => 'PUE - Pago en una sola exhibición',
            'PPD' => 'PPD - Pago en parcialidades o diferido'
        ];

        $formasPago = [
            '01' => '01 - Efectivo',
            '03' => '03 - Transferencia electrónica de fondos',
            '04' => '04 - Tarjeta de crédito',
            '28' => '28 - Tarjeta de débito',
            '99' => '99 - Por definir'
        ];

        $regimenes = [
            '601' => '601 - General de Ley Personas Morales',
            '605' => '605 - Sueldos y Salarios',
            '612' => '612 - Personas Físicas con Actividades Empresariales',
            '626' => '626 - Régimen Simplificado de Confianza (RESICO)'
        ];

        return view('billing.create', compact('company', 'usosCfdi', 'metodosPago', 'formasPago', 'regimenes'));
    }

    public function store(Request $request, PacSandboxService $pacService)
    {
        $company = Auth::user()->companies()->first();

        // 1. Validación estricta
        $validated = $request->validate([
            'receptor_rfc' => ['required', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/i'],
            'receptor_nombre' => 'required|string|max:255',
            'receptor_cp' => 'required|digits:5',
            'receptor_regimen' => 'required|string|size:3',
            'uso_cfdi' => 'required|string|size:3',
            'metodo_pago' => 'required|string|size:3',
            'forma_pago' => 'required|string|size:2',
            'moneda' => 'required|string|size:3',
            'clave_prod_serv' => 'required|digits:8',
            'cantidad' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:1000',
            'valor_unitario' => 'required|numeric|min:0.01',
            'aplica_iva' => 'required|boolean',
        ]);

        // 2. Cálculos
        $subtotal = $validated['cantidad'] * $validated['valor_unitario'];
        $iva = $validated['aplica_iva'] ? $subtotal * 0.16 : 0;
        $total = $subtotal + $iva;

        // 3. Preparar Payload (Lo que enviaríamos al PAC)
        $payload = [
            // (Estructura simplificada para el Mock)
            "Receptor" => ["Rfc" => strtoupper($validated['receptor_rfc'])],
            "Conceptos" => [
                [
                    "Importe" => $subtotal,
                    "Impuestos" => $validated['aplica_iva'] ? [
                        "Traslados" => [["Importe" => $iva]]
                    ] : null
                ]
            ]
        ];

        // 4. Llamar al Web Service Simulado
        $respuestaPac = $pacService->simularTimbrado($payload);

        if ($respuestaPac['success']) {
            // 5. Guardar la factura en nuestra base de datos (Bóveda)
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'uuid' => $respuestaPac['data']['uuid'],
                'type' => 'I',
                'issue_date' => now(),

                // Emisor (Tú) - Asumimos datos de tu tabla Company
                'issuer_rfc' => $company->rfc,
                'issuer_name' => $company->legal_name,
                'issuer_regimen' => $company->tax_regime ?? '601',
                'issuer_cp' => $company->zip_code ?? '29950',

                // Receptor (El Cliente) - Vienen del $validated
                'receiver_rfc' => strtoupper($validated['receptor_rfc']),
                'receiver_name' => strtoupper($validated['receptor_nombre']),
                'receiver_regimen' => $validated['receptor_regimen'],
                'receiver_cp' => $validated['receptor_cp'],

                // Datos de Pago y CFDI - Vienen del $validated
                'uso_cfdi' => $validated['uso_cfdi'],
                'metodo_pago' => $validated['metodo_pago'],
                'forma_pago' => $validated['forma_pago'],
                'moneda' => $validated['moneda'],

                // Totales
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,

                // Conceptos (JSON)
                'items' => [[
                    'clave_prod_serv' => $validated['clave_prod_serv'],
                    'cantidad' => $validated['cantidad'],
                    'unidad' => 'Pieza',
                    'descripcion' => $validated['descripcion'],
                    'valor_unitario' => $validated['valor_unitario'],
                    'importe' => $subtotal
                ]]
            ]);

            return redirect()->route('billing.show', $invoice->id)
                ->with('success', 'Factura timbrada exitosamente.');
        }

        return back()->with('error', 'Error del PAC: ' . $respuestaPac['message']);
    }

    public function show($id)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        return view('billing.show', compact('invoice'));
    }

    public function downloadXml($id)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        // Generamos el XML CFDI 4.0 con todos los atributos obligatorios
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlString .= '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" ';
        $xmlString .= 'Fecha="' . $invoice->issue_date->format('Y-m-d\TH:i:s') . '" ';
        $xmlString .= 'FormaPago="' . ($invoice->forma_pago ?? '01') . '" ';
        $xmlString .= 'SubTotal="' . number_format($invoice->subtotal, 2, '.', '') . '" ';
        $xmlString .= 'Moneda="' . ($invoice->moneda ?? 'MXN') . '" ';
        $xmlString .= 'Total="' . number_format($invoice->total, 2, '.', '') . '" ';
        $xmlString .= 'TipoDeComprobante="I" ';
        $xmlString .= 'MetodoPago="' . ($invoice->metodo_pago ?? 'PUE') . '" ';
        $xmlString .= 'LugarExpedicion="' . ($invoice->issuer_cp ?? '29950') . '">';

        // Nodo Emisor
        $xmlString .= '<cfdi:Emisor Rfc="' . $invoice->issuer_rfc . '" Nombre="' . htmlspecialchars($invoice->issuer_name) . '" RegimenFiscal="' . ($invoice->issuer_regimen ?? '601') . '"/>';

        // Nodo Receptor
        $xmlString .= '<cfdi:Receptor Rfc="' . $invoice->receiver_rfc . '" Nombre="' . htmlspecialchars($invoice->receiver_name) . '" DomicilioFiscalReceptor="' . ($invoice->receiver_cp ?? '29950') . '" RegimenFiscalReceptor="' . ($invoice->receiver_regimen ?? '605') . '" UsoCFDI="' . ($invoice->uso_cfdi ?? 'G03') . '"/>';

        // Nodos Conceptos
        $xmlString .= '<cfdi:Conceptos>';
        foreach ($invoice->items as $item) {
            $xmlString .= '<cfdi:Concepto ClaveProdServ="' . ($item['clave_prod_serv'] ?? '80111600') . '" Cantidad="' . ($item['cantidad'] ?? 1) . '" ClaveUnidad="H87" Descripcion="' . htmlspecialchars($item['descripcion'] ?? '') . '" ValorUnitario="' . number_format($item['valor_unitario'] ?? 0, 2, '.', '') . '" Importe="' . number_format($item['importe'] ?? 0, 2, '.', '') . '">';

            // Impuestos del concepto (Traslado de IVA)
            $xmlString .= '<cfdi:Impuestos><cfdi:Traslados><cfdi:Traslado Base="' . number_format($item['importe'] ?? 0, 2, '.', '') . '" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="' . number_format(($item['importe'] ?? 0) * 0.16, 2, '.', '') . '"/></cfdi:Traslados></cfdi:Impuestos>';

            $xmlString .= '</cfdi:Concepto>';
        }
        $xmlString .= '</cfdi:Conceptos>';

        // Nodo Impuestos Globales
        $xmlString .= '<cfdi:Impuestos TotalImpuestosTrasladados="' . number_format($invoice->iva, 2, '.', '') . '">';
        $xmlString .= '<cfdi:Traslados><cfdi:Traslado Base="' . number_format($invoice->subtotal, 2, '.', '') . '" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="' . number_format($invoice->iva, 2, '.', '') . '"/></cfdi:Traslados>';
        $xmlString .= '</cfdi:Impuestos>';

        // Complemento (Timbre del PAC)
        $xmlString .= '<cfdi:Complemento>';
        $xmlString .= '<tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="1.1" UUID="' . $invoice->uuid . '" FechaTimbrado="' . $invoice->issue_date->format('Y-m-d\TH:i:s') . '" RfcProvCertif="SAT970701NN3" SelloCFD="q8H7fO..." NoCertificadoSAT="00001000000505142236" SelloSAT="bN5mQ8..."/>';
        $xmlString .= '</cfdi:Complemento>';

        $xmlString .= '</cfdi:Comprobante>';

        // Forzamos al navegador a descargar el archivo
        return response($xmlString, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="Factura_' . $invoice->uuid . '.xml"',
        ]);
    }

    public function downloadPdf($id)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        // 1. Construir la URL oficial de validación del SAT
        // El SAT exige: UUID, RFC Emisor, RFC Receptor, Total y los últimos 8 caracteres del sello
        $selloSimulado = "t8xZ9a=="; // Sello falso para el mock
        $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id={$invoice->uuid}&re={$invoice->issuer_rfc}&rr={$invoice->receiver_rfc}&tt={$invoice->total}&fe={$selloSimulado}";

        // 2. Generar el QR en formato SVG y codificarlo en Base64 para que DomPDF no falle
        $qrCode = base64_encode(QrCode::format('svg')->size(130)->margin(0)->generate($urlSat));

        // 3. Pasamos la factura y el QR a la vista
        $pdf = Pdf::loadView('billing.pdf', compact('invoice', 'qrCode'));

        return $pdf->download('Factura_' . $invoice->uuid . '.pdf');
    }

    public function downloadZip($id)
    {
        $company = Auth::user()->companies()->first();
        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        $zipFileName = 'Factura_' . $invoice->uuid . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

            // 1. Generar el contenido del XML
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
            $xmlString .= '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" ';
            $xmlString .= 'Fecha="' . $invoice->issue_date->format('Y-m-d\TH:i:s') . '" ';
            $xmlString .= 'FormaPago="' . ($invoice->forma_pago ?? '01') . '" ';
            $xmlString .= 'SubTotal="' . number_format($invoice->subtotal, 2, '.', '') . '" ';
            $xmlString .= 'Moneda="' . ($invoice->moneda ?? 'MXN') . '" ';
            $xmlString .= 'Total="' . number_format($invoice->total, 2, '.', '') . '" ';
            $xmlString .= 'TipoDeComprobante="I" ';
            $xmlString .= 'MetodoPago="' . ($invoice->metodo_pago ?? 'PUE') . '" ';
            $xmlString .= 'LugarExpedicion="' . ($invoice->issuer_cp ?? '29950') . '">';
            $xmlString .= '<cfdi:Emisor Rfc="' . $invoice->issuer_rfc . '" Nombre="' . htmlspecialchars($invoice->issuer_name) . '" RegimenFiscal="' . ($invoice->issuer_regimen ?? '601') . '"/>';
            $xmlString .= '<cfdi:Receptor Rfc="' . $invoice->receiver_rfc . '" Nombre="' . htmlspecialchars($invoice->receiver_name) . '" DomicilioFiscalReceptor="' . ($invoice->receiver_cp ?? '29950') . '" RegimenFiscalReceptor="' . ($invoice->receiver_regimen ?? '605') . '" UsoCFDI="' . ($invoice->uso_cfdi ?? 'G03') . '"/>';
            $xmlString .= '<cfdi:Conceptos>';
            foreach ($invoice->items as $item) {
                $xmlString .= '<cfdi:Concepto ClaveProdServ="' . ($item['clave_prod_serv'] ?? '80111600') . '" Cantidad="' . ($item['cantidad'] ?? 1) . '" ClaveUnidad="H87" Descripcion="' . htmlspecialchars($item['descripcion'] ?? '') . '" ValorUnitario="' . number_format($item['valor_unitario'] ?? 0, 2, '.', '') . '" Importe="' . number_format($item['importe'] ?? 0, 2, '.', '') . '"/>';
            }
            $xmlString .= '</cfdi:Conceptos></cfdi:Comprobante>';

            // 2. Generar el PDF en binario usando DomPDF
            $selloSimulado = "t8xZ9a==";
            $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id={$invoice->uuid}&re={$invoice->issuer_rfc}&rr={$invoice->receiver_rfc}&tt={$invoice->total}&fe={$selloSimulado}";
            $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(130)->margin(0)->generate($urlSat));

            $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.pdf', compact('invoice', 'qrCode'))->output();

            // 3. Agregar ambos archivos al ZIP
            $zip->addFromString('Factura_' . $invoice->uuid . '.xml', $xmlString);
            $zip->addFromString('Factura_' . $invoice->uuid . '.pdf', $pdfContent);

            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
