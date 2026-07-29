<?php

namespace App\Services;

class DeducibilityMatrixService
{
    /**
     * Evalúa una ClaveProdServ del SAT contra el Régimen Fiscal de la empresa
     * para determinar su nivel de deducibilidad (Verde, Amarillo, Rojo).
     */
    public function evaluateConcept(string $satCode, string $taxRegimeCode): array
    {
        // 1. Filtro Universal: Gastos estrictamente NO deducibles para cualquier régimen (ROJO)
        $universalRedFlags = [
            '502115' => 'Tabaco y cigarros (No deducible por ley)', // ¡Aquí atrapamos tu XML de prueba!
            '502017' => 'Bebidas alcohólicas (No deducible salvo giros muy específicos)',
            '501115' => 'Carnes y embutidos (Despensa personal no deducible)',
            '531015' => 'Ropa y calzado (Salvo uniformes oficiales)',
            '251117' => 'Vehículos de lujo o deportivos',
        ];

        // Revisamos si la clave (o sus primeros 6 dígitos) está en la lista negra universal
        $prefix6 = substr($satCode, 0, 6);
        if (array_key_exists($prefix6, $universalRedFlags)) {
            return [
                'status' => 'rojo',
                'reason' => 'Gasto personal o restringido: ' . $universalRedFlags[$prefix6]
            ];
        }

        // 2. Filtro de Advertencias y Viáticos (AMARILLO)
        $universalWarnings = [
            '901015' => 'Restaurantes y consumo de alimentos. Requiere pago con tarjeta y justificación (viáticos o atención a clientes).',
            '781118' => 'Transporte de pasajeros o peajes. Válido solo si es viático comprobable a más de 50km del domicilio fiscal.',
        ];

        if (array_key_exists($prefix6, $universalWarnings)) {
            return [
                'status' => 'amarillo',
                'reason' => $universalWarnings[$prefix6]
            ];
        }

        // 3. Reglas Específicas por Régimen Fiscal
        switch ($taxRegimeCode) {
            case '626': // RESICO (Régimen Simplificado de Confianza)
                // OJO: En RESICO Personas Físicas, las compras NO restan ISR (el cálculo es sobre ingresos brutos), 
                // pero SÍ sirven para acreditar IVA. Todo gasto operativo debe ser amarillo/verde pero con advertencia.
                return [
                    'status' => 'verde',
                    'reason' => 'Válido para acreditar IVA. Recuerda que en RESICO las compras no disminuyen tu pago de ISR.'
                ];
            
            case '612': // Actividades Empresariales y Profesionales
                // Aquí aplicaría la lógica profunda de cruzar la actividad económica exacta (el giro) 
                // con la clave del producto (Ej. Solo si eres médico puedes deducir material quirúrgico).
                // Por ahora, si pasó los filtros rojos y amarillos, asumimos que es operativo.
                break;
        }

        // 4. Salida por Defecto (VERDE)
        // Si no está en ninguna lista restrictiva, el sistema asume que es un gasto operativo regular (papelería, servidores, renta, etc.)
        return [
            'status' => 'verde',
            'reason' => 'Gasto operativo aparentemente alineado con la actividad de la empresa.'
        ];
    }
}