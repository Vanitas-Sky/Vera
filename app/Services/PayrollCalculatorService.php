<?php

namespace App\Services;

use App\Models\Employee; // <-- IMPORTANTE: Agregar esta línea

class PayrollCalculatorService
{
    // ==========================================
    // VARIABLES FISCALES DE LEY (2026)
    // ==========================================
    private float $umaDiaria = 117.31; // UMA Diaria 2026 (INEGI/DOF 09-ene-2026, vigente desde 01-feb-2026) - para cálculo IMSS
    private float $umaMensual = 3566.22; // UMA Mensual 2026
    private float $umiDiaria = 100.81; // UMI 2026 congelada sin incremento (Infonavit, 3er año consecutivo en $100.81)
    private float $topeMensualSubsidio = 11492.66; // Tope de ingresos para Subsidio al Empleo 2026 (Decreto DOF 31-dic-2025)
    private float $salarioMinimoMensual = 9577.22; // $315.04 * 30.4 días (Zona General, CONASAMI/DOF 09-dic-2025, vigente desde 01-ene-2026)

    // Factor de Integración Mínimo de Ley (Aguinaldo 15 días, Prima Vacacional 25% de 12 días)
    // Fórmula: 1 + (15/365) + ((12*0.25)/365) = 1.0493
    private float $factorIntegracionBasico = 1.0493;

    // Tarifa mensual Art. 96 LISR - Anexo 8 RMF 2026 (DOF 28-dic-2025), factor de actualización 1.1321
    private array $monthlyIsrTable = [
        ['lower' => 0.01, 'upper' => 844.59, 'fixed_fee' => 0.00, 'rate' => 0.0192],
        ['lower' => 844.60, 'upper' => 7168.51, 'fixed_fee' => 16.22, 'rate' => 0.0640],
        ['lower' => 7168.52, 'upper' => 12598.02, 'fixed_fee' => 420.95, 'rate' => 0.1088],
        ['lower' => 12598.03, 'upper' => 14644.64, 'fixed_fee' => 1011.68, 'rate' => 0.1600],
        ['lower' => 14644.65, 'upper' => 17533.64, 'fixed_fee' => 1339.14, 'rate' => 0.1792],
        ['lower' => 17533.65, 'upper' => 35362.83, 'fixed_fee' => 1856.84, 'rate' => 0.2136],
        ['lower' => 35362.84, 'upper' => 55736.68, 'fixed_fee' => 5665.16, 'rate' => 0.2352],
        ['lower' => 55736.69, 'upper' => 106410.50, 'fixed_fee' => 10457.09, 'rate' => 0.3000],
        ['lower' => 106410.51, 'upper' => 141880.66, 'fixed_fee' => 25659.23, 'rate' => 0.3200],
        ['lower' => 141880.67, 'upper' => 425641.99, 'fixed_fee' => 37009.69, 'rate' => 0.3400],
        ['lower' => 425642.00, 'upper' => 999999999.00, 'fixed_fee' => 133488.54, 'rate' => 0.3500],
    ];

    // ==========================================
    // MÉTODOS ISR (Tu código intacto)
    // ==========================================
    private function calculateSubsidy(float $monthlySalary): float
    {
        // Subsidio al Empleo 2026: 15.02% de la UMA mensual (Decreto DOF 31-dic-2025)
        // Nota: enero 2026 usa un porcentaje transitorio de 15.59% sobre la UMA 2025 ($536.21);
        // de febrero a diciembre aplica 15.02% sobre la UMA 2026 ($535.65). Aquí se usa la tasa
        // general (feb-dic); si necesitas el mes de enero exacto, aplica 0.1559 solo para ese mes.
        if ($monthlySalary > 0 && $monthlySalary <= $this->topeMensualSubsidio) {
            return round($this->umaMensual * 0.1502, 2);
        }
        return 0.0;
    }

    public function calculateMonthlyISR(float $monthlySalary): float
    {
        // REGLA DE ORO: Escudo del Salario Mínimo (Art. 96 LISR)
        if ($monthlySalary <= $this->salarioMinimoMensual) {
            return 0.0;
        }

        foreach ($this->monthlyIsrTable as $row) {
            if ($monthlySalary >= $row['lower'] && $monthlySalary <= $row['upper']) {
                $surplus = $monthlySalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($monthlySalary);

                $finalIsr = $calculatedIsr - $subsidy;
                return $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
            }
        }
        return 0.0;
    }

    public function getIsrBreakdown(float $monthlySalary): array
    {
        // ... (Tu código intacto de getIsrBreakdown) ...
        if ($monthlySalary <= 0) {
            return [
                'base' => 0,
                'lower_limit' => 0,
                'surplus' => 0,
                'rate' => 0,
                'marginal_tax' => 0,
                'fixed_fee' => 0,
                'calculated_isr' => 0,
                'subsidy_applied' => 0,
                'total_isr' => 0,
                'is_minimum_wage' => false
            ];
        }

        $isMinimumWage = $monthlySalary <= $this->salarioMinimoMensual;

        foreach ($this->monthlyIsrTable as $row) {
            if ($monthlySalary >= $row['lower'] && $monthlySalary <= $row['upper']) {
                $surplus = $monthlySalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($monthlySalary);

                $finalIsr = $calculatedIsr - $subsidy;

                if ($isMinimumWage) {
                    $finalIsr = 0.0;
                } else {
                    $finalIsr = $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
                }

                return [
                    'base' => $monthlySalary,
                    'lower_limit' => $row['lower'],
                    'surplus' => $surplus,
                    'rate' => $row['rate'] * 100,
                    'marginal_tax' => $marginalTax,
                    'fixed_fee' => $row['fixed_fee'],
                    'calculated_isr' => round($calculatedIsr, 2),
                    'subsidy_applied' => $subsidy,
                    'total_isr' => $finalIsr,
                    'is_minimum_wage' => $isMinimumWage
                ];
            }
        }
        return [];
    }

    // ==========================================
    // MÉTODOS IMSS (NUEVO)
    // ==========================================

    /**
     * Calcula la Cuota Obrera del IMSS mensual.
     */
    public function calculateMonthlyIMSS(float $monthlySalary): float
    {
        // 1. Obtener el Salario Diario y el SBC (Salario Base de Cotización)
        $salarioDiario = $monthlySalary / 30.4; // Mes promedio fiscal
        $sbc = $salarioDiario * $this->factorIntegracionBasico;

        // Tope legal del SBC: 25 UMAs (Art. 28 de la Ley del Seguro Social).
        // El SBC nunca puede cotizar por arriba de este límite, sin importar qué tan alto sea el sueldo real.
        $topeSbcDiario = $this->umaDiaria * 25;
        if ($sbc > $topeSbcDiario) {
            $sbc = $topeSbcDiario;
        }

        // Escudo IMSS: Si el empleado gana el salario mínimo, el patrón paga el IMSS obrero.
        // Art. 36 de la Ley del Seguro Social.
        if ($monthlySalary <= $this->salarioMinimoMensual) {
            return 0.0;
        }

        $diasCotizadosMes = 30.4; // Promedio mensual

        // 2. Porcentajes de Ley (Cuota Obrera Fija)
        $pctEspecie = 0.00375;  // Enf. y Mat. (Gastos Médicos Pensionados)
        $pctDinero = 0.0025;    // Enf. y Mat. (Prestaciones en Dinero)
        $pctInvalidez = 0.00625; // Invalidez y Vida
        $pctCesantia = 0.01125;  // Cesantía en Edad Avanzada y Vejez

        // Suma base (2.375%)
        $cuotaBaseDiaria = $sbc * ($pctEspecie + $pctDinero + $pctInvalidez + $pctCesantia);

        // 3. Regla del Excedente (Más de 3 UMAs)
        $cuotaExcedenteDiaria = 0.0;
        $tresUmas = $this->umaDiaria * 3;

        if ($sbc > $tresUmas) {
            $excedente = $sbc - $tresUmas;
            $cuotaExcedenteDiaria = $excedente * 0.0040; // 0.40% sobre el excedente
        }

        // 4. Total mensual a retener
        $retencionMensual = ($cuotaBaseDiaria + $cuotaExcedenteDiaria) * $diasCotizadosMes;

        return round($retencionMensual, 2);
    }

    // ==========================================
    // CÁLCULO FINAL DE NÓMINA
    // ==========================================
    public function calculatePayroll(Employee $employee): array
    {
        $monthlySalary = $employee->base_salary;

        // 1. Retenciones de Ley Obligatorias
        $isrRetention = $this->calculateMonthlyISR($monthlySalary);
        $imssRetention = $this->calculateMonthlyIMSS($monthlySalary);

        $customDeductionsList = [];
        $totalCustomDeductions = 0.0;

        $deductions = $employee->activeDeductions;

        // ---------------------------------------------------------
        // FASE 1: Deducciones sobre el Salario Bruto (Ej. Sindicatos, Fondos base bruta)
        // ---------------------------------------------------------
        $deduccionesBrutas = 0.0;
        foreach ($deductions->where('amount_type', 'percentage_gross') as $deduction) {
            $amountToDeduct = $monthlySalary * ($deduction->amount / 100);

            $deduccionesBrutas += $amountToDeduct;
            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        // ---------------------------------------------------------
        // FASE 2: Obtener Salario Neto Legal (Base para Pensiones u Órdenes Judiciales sobre Neto)
        // ---------------------------------------------------------
        $alimonyBase = $monthlySalary - $isrRetention - $imssRetention;

        // ---------------------------------------------------------
        // FASE 3: Deducciones sobre el Neto, Fijas y VSM
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', '!=', 'percentage_gross') as $deduction) {
            $amountToDeduct = 0.0;

            switch ($deduction->amount_type) {
                case 'fixed':
                    // Monto fijo
                    $amountToDeduct = $deduction->amount;
                    break;

                case 'percentage_net':
                    // Pensión Alimenticia (Calculada sobre la Base Legal de la SCJN)
                    $amountToDeduct = $alimonyBase * ($deduction->amount / 100);
                    break;

                case 'vsm':
                    // Factor Infonavit
                    $umiMensual = $this->umiDiaria * 30.4;
                    $amountToDeduct = ($deduction->amount * $umiMensual) + 15.00;
                    break;
            }

            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        // Salario Neto Final a depositar
        $finalNetSalary = $monthlySalary - $isrRetention - $imssRetention - $totalCustomDeductions;

        return [
            'gross_salary' => round($monthlySalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'total_custom_deductions' => round($totalCustomDeductions, 2),
            'custom_deductions' => $customDeductionsList,
            'net_salary' => round($finalNetSalary, 2),
        ];
    }
}
