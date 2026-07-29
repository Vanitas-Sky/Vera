<?php

namespace App\Services;

use Illuminate\Support\Str;

class PacSandboxService
{
    /**
     * Simula la conexión a un Proveedor Autorizado de Certificación (PAC)
     * y devuelve una respuesta estructurada como si fuera un entorno real.
     *
     * @param array $payload Los datos de la factura estructurados.
     * @return array
     */
    public function simularTimbrado(array $payload): array
    {
        // 1. Simulamos el retraso de red de una API externa (1 segundo)
        sleep(1);

        // 2. Generamos un UUID (Folio Fiscal) falso pero con formato válido del SAT
        $uuid = Str::uuid()->toString();

        // 3. Extraemos el total del payload para ponerlo en el XML
        $total = $payload['Conceptos'][0]['Importe'];
        
        if (isset($payload['Conceptos'][0]['Impuestos']['Traslados'][0]['Importe'])) {
            $total += $payload['Conceptos'][0]['Impuestos']['Traslados'][0]['Importe'];
        }

        // 4. Simulamos un archivo XML básico timbrado (CFDI 4.0)
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlString .= '<cfdi:Comprobante Version="4.0" Total="' . round($total, 2) . '" Folio="' . $uuid . '">';
        $xmlString .= '<cfdi:Complemento>';
        $xmlString .= '<tfd:TimbreFiscalDigital UUID="' . $uuid . '" FechaTimbrado="' . now()->toIso8601String() . '"/>';
        $xmlString .= '</cfdi:Complemento>';
        $xmlString .= '</cfdi:Comprobante>';
        
        // 5. Retornamos la estructura de éxito estándar de una API
        return [
            'success' => true,
            'data' => [
                'uuid' => $uuid,
                'xml_base64' => base64_encode($xmlString),
                'fecha_timbrado' => now()->toDateTimeString(),
            ],
            'message' => 'Timbrado exitoso en entorno de pruebas (Mock).'
        ];
    }
}