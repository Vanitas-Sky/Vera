<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceApiController extends Controller
{
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

        // Listado de facturas formateadas
        $invoices = $query->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($inv) use ($company) {
                // Si la emití yo (I), la contraparte es el cliente. Si la recibí (E), es el proveedor.
                $counterpart = ($inv->type === 'I')
                    ? ($inv->receiver_name ?: 'Público en general')
                    : ($inv->issuer_name ?: 'Proveedor no identificado');

                $counterpartRfc = ($inv->type === 'I') ? $inv->receiver_rfc : $inv->issuer_rfc;

                return [
                    'id' => $inv->id,
                    'uuid' => $inv->uuid,
                    'partner_name' => $counterpart,
                    'partner_rfc' => $counterpartRfc,
                    'type' => $inv->type,
                    'total' => (float) $inv->total,
                    'iva' => (float) $inv->iva,
                    'subtotal' => (float) $inv->subtotal,
                    'issue_date' => Carbon::parse($inv->issue_date)->format('d/m/Y'),
                    'is_canceled' => (bool) $inv->is_canceled
                ];
            });

        return response()->json([
            'total_amount' => $totalAmount,
            'total_iva' => $totalIva,
            'count' => $invoices->count(),
            'invoices' => $invoices
        ]);
    }

    public function show(Request $request, $id)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $invoice = Invoice::where('company_id', $company->id)->findOrFail($id);

        $counterpart = ($invoice->type === 'I')
            ? ($invoice->receiver_name ?: 'Público en general')
            : ($invoice->issuer_name ?: 'Proveedor no identificado');

        $counterpartRfc = ($invoice->type === 'I') ? $invoice->receiver_rfc : $invoice->issuer_rfc;

        $rawItems = is_array($invoice->items) ? $invoice->items : (json_decode($invoice->items, true) ?? []);

        $formattedItems = collect($rawItems)->map(function ($it) {
            return [
                'description' => $it['descripcion'] ?? $it['description'] ?? 'Sin descripción',
                'cantidad' => (float) ($it['cantidad'] ?? $it['quantity'] ?? 1),
                'valor_unitario' => (float) ($it['valor_unitario'] ?? $it['unit_value'] ?? 0),
                'importe' => (float) ($it['importe'] ?? $it['amount'] ?? 0),
                'clave_prod_serv' => $it['clave_prod_serv'] ?? $it['clave_sat'] ?? '00000000',
            ];
        })->values();

        return response()->json([
            'id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'partner_name' => $counterpart,
            'partner_rfc' => $counterpartRfc,
            'type' => $invoice->type,
            'total' => (float) $invoice->total,
            'subtotal' => (float) $invoice->subtotal,
            'iva' => (float) $invoice->iva,
            'issue_date' => \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y H:i'),
            'payment_method' => $invoice->payment_method ?? 'PUE',
            'payment_form' => $invoice->payment_form ?? '03',
            'items' => $formattedItems // <-- Ahora siempre vendrá con 'description', 'importe', etc.
        ]);
    }
}
