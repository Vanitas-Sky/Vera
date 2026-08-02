<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SatValidationService
{
    public function validateCfdiStatus($rfcEmisor, $rfcReceptor, $total, $uuid)
    {
        // ==========================================
        // MODO UNIVERSIDAD / PRESENTACIÓN
        // ==========================================
        if (env('USE_PAC_VALIDATION', false) === false) {
            // Simulamos un pequeño retraso de red de 0.5 segundos para que la UI se vea realista
            usleep(500000); 
            
            return [
                'is_valid' => true,
                'status' => 'Vigente',
                'is_canceled' => false,
                'message' => 'Simulación: Validación exitosa (Modo Desarrollo).'
            ];
        }

        // ==========================================
        // MODO PRODUCCIÓN REAL (Smarter Web)
        // ==========================================
        $token = env('SW_API_TOKEN'); 
        $url = "https://services.test.sw.com.mx/api/v1/cfdi/validate/estado";

        try {
            $response = Http::withToken($token)
                ->post($url, [
                    're' => $rfcEmisor,
                    'rr' => $rfcReceptor,
                    'tt' => number_format((float) $total, 6, '.', ''),
                    'id' => $uuid
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $estado = $data['data']['estado'] ?? 'No Encontrado';
                return [
                    'is_valid' => in_array($estado, ['Vigente', 'Cancelado']),
                    'status' => $estado,
                    'is_canceled' => $estado === 'Cancelado',
                    'message' => 'Validación exitosa ante el SAT.'
                ];
            }
            return ['is_valid' => false, 'message' => 'Error de comunicación con el PAC.'];
        } catch (\Exception $e) {
            return ['is_valid' => false, 'message' => 'Fallo de conexión en el servidor.'];
        }
    }
}