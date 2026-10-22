<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;

class AiFiscalDeducibilityService
{
    /**
     * Evalúa las facturas de egreso de la empresa contra su giro comercial usando Claude.
     */
    public function auditCompanyExpenses(Company $company, int $limit = 10): array
    {
        // 1. Obtener giro comercial y régimen
        $giro = $company->industry ?? 'Comercio y Servicios Generales';
        $rfc = $company->rfc;
        $razonSocial = $company->legal_name;

        // 2. Obtener facturas de egreso activas (gastos de proveedores)
        $invoices = Invoice::where('company_id', $company->id)
            ->where(function ($query) use ($rfc) {
                $query->where('type', 'E')
                      ->orWhere('receiver_rfc', $rfc);
            })
            ->where('is_canceled', 0)
            ->orderBy('issue_date', 'desc')
            ->take($limit)
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'status' => 'empty',
                'summary' => 'No se encontraron facturas de egresos recientes para analizar.',
                'evaluated_invoices' => []
            ];
        }

        // 3. Estructurar el listado para el prompt
        $listaFacturas = "";
        foreach ($invoices as $inv) {
            $listaFacturas .= "- UUID: {$inv->uuid} | Proveedor: {$inv->issuer_name} (RFC: {$inv->issuer_rfc}) | Monto: $" . number_format($inv->total, 2) . " | Descripción/Uso: {$inv->description} ({$inv->cfdi_use})\n";
        }

        // 4. Construir el prompt especializado con fundamento en la Ley del ISR
        $prompt = "Eres un Auditor Fiscal Senior del SAT y experto en la Ley del Impuesto sobre la Renta (LISR) mexicana.\n";
        $prompt .= "Analiza si las siguientes facturas de egresos cumplen con el principio de ESTRICTA INDISPENSABILIDAD (Art. 27, Fracción I de la LISR) para la empresa:\n";
        $prompt .= "EMPRESA: {$razonSocial}\n";
        $prompt .= "RFC: {$rfc}\n";
        $prompt .= "GIRO COMERCIAL / ACTIVIDAD DECLARADA: {$giro}\n\n";
        $prompt .= "LISTA DE FACTURAS DE GASTOS A EVALUAR:\n";
        $prompt .= $listaFacturas . "\n";
        $prompt .= "INSTRUCCIONES DE RESPUESTA:\n";
        $prompt .= "1. Proporciona un Dictamen General en 2 líneas indicando el nivel de riesgo de la empresa.\n";
        $prompt .= "2. Para cada factura analizada, clasifícala exactamente con uno de estos 3 estados:\n";
        $prompt .= "   - [🟢 DEDUCIBLE]: Gasto indispensable y coherente con el giro.\n";
        $prompt .= "   - [🟡 RIESGO FISCAL]: Requiere contrato, bitácora o justificación de materialidad.\n";
        $prompt .= "   - [🔴 NO DEDUCIBLE]: Gasto ajeno al giro o no deducible por normativa.\n";
        $prompt .= "3. Añade una justificación legal breve para cada caso. Usa Markdown limpio con viñetas.";

        $payload = [
            "model" => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
            "max_tokens" => 1200,
            "temperature" => 0.1,
            "system" => "Eres el motor de auditoría fiscal automatizado de Vera ERP. Tu tono es técnico, preventivo y fundamentado.",
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
                return [
                    'status' => 'success',
                    'analysis' => $data['content'][0]['text'] ?? 'Sin respuesta.'
                ];
            }

            return ['status' => 'error', 'analysis' => 'No se pudo conectar con el servicio de IA.'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'analysis' => 'Error: ' . $e->getMessage()];
        }
    }
}