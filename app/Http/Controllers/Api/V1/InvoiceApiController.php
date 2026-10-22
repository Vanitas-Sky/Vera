<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Services\DeducibilityMatrixService;
use Carbon\Carbon;

class InvoiceApiController extends Controller
{
    protected DeducibilityMatrixService $matrixService;

    public function __construct(DeducibilityMatrixService $matrixService)
    {
        $this->matrixService = $matrixService;
    }

    public function index(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Registra tu empresa primero.'], 404);
        }

        $search = $request->input('search');
        $status = $request->input('status', 'activas');
        $type = $request->input('type', 'todas');
        $period = $request->input('period', now()->format('Y-m'));

        $query = Invoice::where('company_id', $company->id);

        // Filtro de Estatus
        if ($status === 'canceladas') {
            $query->where('is_canceled', true);
        } elseif ($status === 'activas') {
            $query->where('is_canceled', false);
        }

        // Filtro de Tipo (I = Ingreso, E = Egreso)
        if ($type === 'I' || $type === 'E') {
            $query->where('type', $type);
        }

        // Filtro por Fecha (Mes o Año)
        if (!empty($period)) {
            $year = substr($period, 0, 4);
            $query->whereYear('issue_date', $year);

            if (strlen($period) > 4) {
                $month = substr($period, 5, 2);
                $query->whereMonth('issue_date', $month);
            }
        }

        // Búsqueda por contraparte, RFC o UUID
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('issuer_name', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                    ->orWhere('issuer_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_rfc', 'LIKE', "%{$search}%")
                    ->orWhere('uuid', 'LIKE', "%{$search}%");
            });
        }

        // Totales calculados
        $totalAmount = (float) (clone $query)->sum('total');
        $totalIva = (float) (clone $query)->sum('iva');

        $taxRegime = $company->tax_regime_code ?? '601';

        // Listado de facturas formateadas con cálculo idéntico al web
        $invoices = $query->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($inv) use ($taxRegime) {
                $counterpart = ($inv->type === 'I')
                    ? ($inv->receiver_name ?: 'Público en general')
                    : ($inv->issuer_name ?: 'Proveedor no identificado');

                $counterpartRfc = ($inv->type === 'I') ? $inv->receiver_rfc : $inv->issuer_rfc;

                // Evaluación del Semáforo Fiscal a partir de los items
                $deducibilityStatus = null;

                if ($inv->type === 'E') {
                    $deducibilityStatus = $this->resolveInvoiceStatus($inv, $taxRegime);
                }

                return [
                    'id'                  => $inv->id,
                    'uuid'                => $inv->uuid,
                    'partner_name'        => $counterpart,
                    'partner_rfc'         => $counterpartRfc,
                    'type'                => $inv->type,
                    'total'               => (float) $inv->total,
                    'iva'                 => (float) $inv->iva,
                    'subtotal'            => (float) $inv->subtotal,
                    'issue_date'          => Carbon::parse($inv->issue_date)->format('d/m/Y'),
                    'is_canceled'         => (bool) $inv->is_canceled,
                    'deducibility_status' => $deducibilityStatus, // "VERDE", "AMARILLO", "ROJO"
                ];
            });

        return response()->json([
            'total_amount' => $totalAmount,
            'total_iva'    => $totalIva,
            'count'        => $invoices->count(),
            'invoices'     => $invoices
        ]);
    }

    public function show(Request $request, $id)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);
        $taxRegime = $company->tax_regime_code ?? '601';

        $counterpart = ($invoice->type === 'I')
            ? ($invoice->receiver_name ?: 'Público en general')
            : ($invoice->issuer_name ?: 'Proveedor no identificado');

        $counterpartRfc = ($invoice->type === 'I') ? $invoice->receiver_rfc : $invoice->issuer_rfc;

        $rawItems = is_array($invoice->items) ? $invoice->items : (json_decode($invoice->items, true) ?? []);

        $facturaPeorStatus = 'VERDE';
        $mainReason = 'Gasto operativo indispensable alineado a la actividad empresarial (Art. 27 Fracc. I LISR).';

        // Evaluar cada partida usando el servicio del ERP
        $formattedItems = collect($rawItems)->map(function ($it) use ($invoice, $taxRegime, &$facturaPeorStatus, &$mainReason) {
            $clave = $it['clave_sat'] ?? $it['clave_prod_serv'] ?? $it['ClaveProdServ'] ?? $it['sat_code'] ?? '00000000';

            if ($invoice->type === 'E') {
                // Si el item ya traía estatus guardado lo usamos, de lo contrario evaluamos con la matriz
                if (isset($it['status']) && isset($it['reason'])) {
                    $itemStatus = strtoupper(trim($it['status']));
                    $itemReason = $it['reason'];
                } else {
                    $eval = $this->matrixService->evaluateConcept($clave, $taxRegime);
                    $itemStatus = strtoupper(trim($eval['status'] ?? 'VERDE'));
                    $itemReason = $eval['reason'] ?? '';
                }

                // Ponderación del peor estatus de la factura completa
                if (str_contains($itemStatus, 'ROJO') || str_contains($itemStatus, 'NO')) {
                    $facturaPeorStatus = 'ROJO';
                    $mainReason = $itemReason;
                } elseif ((str_contains($itemStatus, 'AMARILLO') || str_contains($itemStatus, 'RIESGO')) && $facturaPeorStatus !== 'ROJO') {
                    $facturaPeorStatus = 'AMARILLO';
                    $mainReason = $itemReason;
                }
            } else {
                $itemStatus = 'VERDE';
                $itemReason = 'Ingreso facturado por la empresa.';
            }

            return [
                'description'         => $it['descripcion'] ?? $it['description'] ?? 'Sin descripción',
                'cantidad'            => (float) ($it['cantidad'] ?? $it['quantity'] ?? 1),
                'valor_unitario'      => (float) ($it['valor_unitario'] ?? $it['unit_value'] ?? 0),
                'importe'             => (float) ($it['importe'] ?? $it['amount'] ?? 0),
                'clave_prod_serv'     => $clave,
                'deducibility_status' => $itemStatus,
                'deducibility_reason' => $itemReason,
            ];
        })->values();

        if ($invoice->type === 'I') {
            $facturaPeorStatus = null;
            $mainReason = null;
        }

        return response()->json([
            'id'                  => $invoice->id,
            'uuid'                => $invoice->uuid,
            'partner_name'        => $counterpart,
            'partner_rfc'         => $counterpartRfc,
            'type'                => $invoice->type,
            'total'               => (float) $invoice->total,
            'subtotal'            => (float) $invoice->subtotal,
            'iva'                 => (float) $invoice->iva,
            'issue_date'          => Carbon::parse($invoice->issue_date)->format('d/m/Y H:i'),
            'payment_method'      => $invoice->payment_method ?? 'PUE',
            'payment_form'        => $invoice->payment_form ?? '03',
            'deducibility_status' => $facturaPeorStatus,
            'deducibility_reason' => $mainReason,
            'items'               => $formattedItems
        ]);
    }

    /**
     * Resuelve el peor estatus de deducibilidad para un comprobante de egreso.
     */
    private function resolveInvoiceStatus(Invoice $inv, string $taxRegime): string
    {
        $rawItems = is_array($inv->items) ? $inv->items : (json_decode($inv->items, true) ?? []);

        if (empty($rawItems)) {
            return 'VERDE';
        }

        $facturaPeorStatus = 'VERDE';

        foreach ($rawItems as $item) {
            if (isset($item['status'])) {
                $statusClean = strtoupper(trim($item['status']));
            } else {
                $clave = $item['clave_sat'] ?? $item['clave_prod_serv'] ?? $item['ClaveProdServ'] ?? $item['sat_code'] ?? '00000000';
                $eval = $this->matrixService->evaluateConcept($clave, $taxRegime);
                $statusClean = strtoupper(trim($eval['status'] ?? 'VERDE'));
            }

            if (str_contains($statusClean, 'ROJO') || str_contains($statusClean, 'NO')) {
                return 'ROJO';
            }

            if (str_contains($statusClean, 'AMARILLO') || str_contains($statusClean, 'RIESGO')) {
                $facturaPeorStatus = 'AMARILLO';
            }
        }

        return $facturaPeorStatus;
    }
}