<?php

namespace App\Services;
use App\Models\Employee; // <-- IMPORTANTE: Agregar esta línea

class PayrollCalculatorService
{
    // ==========================================
    // VARIABLES FISCALES DE LEY (2024/2026)
    // ==========================================
    private float $umaDiaria = 108.57; // UMA Diaria para cálculo IMSS
    private float $umaMensual = 3300.53;
    private float $umiDiaria = 100.81;
    private float $topeMensualSubsidio = 9081.00;
    private float $salarioMinimoMensual = 7567.47; // $248.93 * 30.4 días (Zona General)

    // Factor de Integración Mínimo de Ley (Aguinaldo 15 días, Prima Vacacional 25% de 12 días)
    // Fórmula: 1 + (15/365) + ((12*0.25)/365) = 1.0493
    private float $factorIntegracionBasico = 1.0493;

    private array $monthlyIsrTable = [
        ['lower' => 0.01, 'upper' => 746.04, 'fixed_fee' => 0.00, 'rate' => 0.0192],
        ['lower' => 746.05, 'upper' => 6332.05, 'fixed_fee' => 14.32, 'rate' => 0.0640],
        ['lower' => 6332.06, 'upper' => 11128.01, 'fixed_fee' => 371.83, 'rate' => 0.1088],
        ['lower' => 11128.02, 'upper' => 12935.82, 'fixed_fee' => 893.63, 'rate' => 0.1600],
        ['lower' => 12935.83, 'upper' => 15487.71, 'fixed_fee' => 1182.88, 'rate' => 0.1792],
        ['lower' => 15487.72, 'upper' => 31236.49, 'fixed_fee' => 1640.18, 'rate' => 0.2136],
        ['lower' => 31236.50, 'upper' => 49233.00, 'fixed_fee' => 5004.12, 'rate' => 0.2352],
        ['lower' => 49233.01, 'upper' => 93993.90, 'fixed_fee' => 9236.89, 'rate' => 0.3000],
        ['lower' => 93993.91, 'upper' => 125325.20, 'fixed_fee' => 22665.17, 'rate' => 0.3200],
        ['lower' => 125325.21, 'upper' => 375975.61, 'fixed_fee' => 32691.18, 'rate' => 0.3400],
        ['lower' => 375975.62, 'upper' => 999999999.00, 'fixed_fee' => 117912.32, 'rate' => 0.3500],
    ];

    // ==========================================
    // MÉTODOS ISR (Tu código intacto)
    // ==========================================
    private function calculateSubsidy(float $monthlySalary): float
    {
        if ($monthlySalary > 0 && $monthlySalary <= $this->topeMensualSubsidio) {
            return round($this->umaMensual * 0.1182, 2);
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
                'base' => 0, 'lower_limit' => 0, 'surplus' => 0, 'rate' => 0, 
                'marginal_tax' => 0, 'fixed_fee' => 0, 'calculated_isr' => 0, 
                'subsidy_applied' => 0, 'total_isr' => 0, 'is_minimum_wage' => false
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

        // 1. Retenciones de Ley (Obligatorias)
        $isrRetention = $this->calculateMonthlyISR($monthlySalary);
        $imssRetention = $this->calculateMonthlyIMSS($monthlySalary); 
        
        // 2. Salario Neto Base (Antes de deducciones personalizadas)
        $netSalaryBase = $monthlySalary - $isrRetention - $imssRetention;

        // 3. Procesamiento de Deducciones Personalizadas
        $customDeductionsList = [];
        $totalCustomDeductions = 0.0;

        foreach ($employee->activeDeductions as $deduction) {
            $amountToDeduct = 0.0;

            switch ($deduction->amount_type) {
                case 'fixed':
                    // Monto fijo directo (Ej: $500 pesos de Caja de Ahorro)
                    $amountToDeduct = $deduction->amount;
                    break;

                case 'percentage':
                    // Porcentaje (Ej: 20% de Pensión Alimenticia). 
                    // Regla legal: Se calcula sobre el Neto, después de impuestos de ley.
                    $amountToDeduct = $netSalaryBase * ($deduction->amount / 100);
                    break;

                case 'vsm':
                    // Factor Infonavit (Veces Salario Mínimo / UMI).
                    // Fórmula mensual aprox: (Factor VSM * UMI Diaria * 30.4) + Seguro de Daños Infonavit ($15 aprox)
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

        // 4. Salario Neto Final (Lo que realmente se le deposita)
        $finalNetSalary = $netSalaryBase - $totalCustomDeductions;

        return [
            'gross_salary' => round($monthlySalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'total_custom_deductions' => round($totalCustomDeductions, 2), // El total sumado
            'custom_deductions' => $customDeductionsList, // El array con el desglose para el PDF
            'net_salary' => round($finalNetSalary, 2),
        ];
    }
}
