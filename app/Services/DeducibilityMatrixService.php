<?php

namespace App\Services;

class DeducibilityMatrixService
{
    /**
     * Evalúa una ClaveProdServ del SAT contra el Régimen Fiscal de la empresa
     * para determinar su nivel de deducibilidad (VERDE, AMARILLO, ROJO).
     */
    public function evaluateConcept(string $satCode, string $taxRegimeCode): array
    {
        $code = trim($satCode);
        $prefix6 = substr($code, 0, 6);
        $prefix4 = substr($code, 0, 4);

        // =========================================================================
        // 1. FILTRO ROJO: GASTOS ESTRICTAMENTE NO DEDUCIBLES (Art. 28 LISR)
        // =========================================================================
        $universalRedFlags = [
            // Vicios y productos restringidos
            '502115' => 'Tabaco y cigarros. Prohibido expresamente por el Art. 28 Fracc. II de la LISR.',
            '502017' => 'Bebidas alcohólicas y destilados. No deducible salvo giros de restaurante/bar.',
            
            // Gastos personales ajenos a la actividad empresarial
            '501115' => 'Carnes frías, embutidos y despensa personal no justificada.',
            '531015' => 'Ropa civil, vestidos o trajes de vestir (salvo uniformes o equipo de seguridad).',
            '531016' => 'Calzado civil o formal (no considerado calzado de protección industrial).',
            '531316' => 'Perfumería, cosméticos y artículos de cuidado personal.',
            '541115' => 'Joyería, piedras preciosas y relojes de alta gama.',

            // Diversión, ocio y apuestas
            '901215' => 'Juegos de azar, casinos, billetes de lotería y sorteos.',
            '901216' => 'Entradas a centros de espectáculos nocturnos, centros deportivos o recreativos.',

            // Vehículos y bienes de recreo o lujo
            '251117' => 'Embarcaciones de recreo, yates y lanchas deportivas.',
            '251115' => 'Aeronaves privadas o ejecutivas de uso personal.',

            // Sanciones públicas
            '841116' => 'Multas gubernamentales, recargos y sanciones fiscales impuestas por autoridades.'
        ];

        if (array_key_exists($prefix6, $universalRedFlags)) {
            return [
                'status' => 'ROJO',
                'reason' => 'Gasto no deducible: ' . $universalRedFlags[$prefix6]
            ];
        }

        // =========================================================================
        // 2. FILTRO AMARILLO: RIESGO FISCAL Y MATERIALIDAD (Art. 27 Fracc. I y Art. 69-B CFF)
        // =========================================================================
        $universalWarnings = [
            // Servicios Intangibles (Foco prioritario de auditoría del SAT por materialidad)
            '821015' => 'Campañas de publicidad digital y medios. Requiere contrato, muestras de entregables y métricas de pauta para comprobar materialidad.',
            '821016' => 'Publicidad exterior, impresa y relaciones públicas. Requiere evidencia fotográfica de su exhibición.',
            '801015' => 'Consultoría de negocios y optimización de procesos. Servicio intangible vulnerable ante el SAT; indispensable contrato formal y minutas de trabajo.',
            '801016' => 'Asesoría en recursos humanos o coaching laboral. Requiere justificar la necesidad para la productividad del personal.',
            '801216' => 'Servicios jurídicos y honorarios legales. Requiere vinculación con expedientes o contratos de asesoría legal activa.',
            '841115' => 'Servicios de auditoría y contabilidad externa. Requiere contrato y bitácora de dictámenes entregados.',

            // Viáticos, Alimentos y Viajes (Art. 28 Fracc. V y XX LISR)
            '901015' => 'Consumo en restaurantes y cafeterías. Deducible únicamente al 8.5% si es local, o al 100% como viático si se realizó a más de 50km del domicilio fiscal con tarjeta corporativa.',
            '901016' => 'Servicios de banquetes para eventos empresariales. Requiere lista de asistencia y justificación de evento corporativo.',
            '901115' => 'Hospedaje en hoteles. Válido solo como viático con itinerario comprobable y factura de transporte asociada.',
            '781118' => 'Transporte de pasajeros o peajes. Válido exclusivamente como viático documentado con motivo laboral.',
            '781117' => 'Arrendamiento de automóviles sin chofer. Sujeto al tope legal de $850.00 MXN diarios en territorio nacional.',

            // Combustibles y Mantenimientos Especiales
            '151015' => 'Combustibles (gasolina/diésel). Deducible solo si se liquidó mediante monedero electrónico autorizado, tarjeta corporativa o transferencia bancaria (0% deducible en efectivo).',
            '432315' => 'Software o licencias informáticas extranjeras. Requiere verificar cumplimiento en retención de IVA e ISR según convenios de doble tributación.',
            '861017' => 'Capacitación externa y diplomados. Debe estar directamente alineado a las funciones del personal según el plan de STPS.'
        ];

        if (array_key_exists($prefix6, $universalWarnings)) {
            return [
                'status' => 'AMARILLO',
                'reason' => 'Riesgo / Fiscalización: ' . $universalWarnings[$prefix6]
            ];
        }

        // =========================================================================
        // 3. REGLAS ESPECÍFICAS SEGÚN EL RÉGIMEN FISCAL DECLARADO
        // =========================================================================
        switch ($taxRegimeCode) {
            case '626': // RESICO (Régimen Simplificado de Confianza - Personas Físicas)
                return [
                    'status' => 'VERDE',
                    'reason' => 'Acreditable para IVA. En RESICO PF las compras no disminuyen el pago mensual de ISR, pero son 100% válidas para flujo e IVA.'
                ];

            case '612': // Personas Físicas con Actividades Empresariales y Profesionales
                // Si son activos fijos de cómputo o equipo (Familias 4321 o 2315)
                if (in_array($prefix4, ['4321', '2315'])) {
                    return [
                        'status' => 'VERDE',
                        'reason' => 'Inversión en activo fijo (Deducción vía depreciación contable según porcentajes máximos de LISR).'
                    ];
                }
                break;

            case '601': // Personas Morales - Régimen General de Ley
            default:
                // Si la factura pasó los filtros de exclusión (rojo) y advertencias (amarillo)
                break;
        }

        // =========================================================================
        // 4. SALIDA POR DEFECTO: GASTO OPERATIVO REGULAR (VERDE)
        // =========================================================================
        return [
            'status' => 'VERDE',
            'reason' => 'Gasto operativo indispensable alineado a la actividad empresarial (Art. 27 Fracc. I LISR).'
        ];
    }
}