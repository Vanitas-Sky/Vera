<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Motor Fiscal Centralizado (Mensual, Quincenal, Semanal)
 * Basado en el Anexo 8 de la RMF 2026 (DOF 28-dic-2025)
 */
class PayrollCalculatorService
{
    // ==========================================
    // VARIABLES FISCALES DE LEY GLOBALES (2026)
    // ==========================================
    private float $umaDiaria = 117.31;
    private float $umaMensual = 3566.22;
    private float $umiDiaria = 100.81;
    private float $factorIntegracionBasico = 1.0493;

    // Subsidio al Empleo (Bases mensuales prorrateables)
    private float $topeMensualSubsidio = 11492.66;
    
    // Salario Mínimo (Zona General)
    private float $salarioMinimoDiario = 315.04;

    // ==========================================
    // TABLAS ISR (Anexo 8 RMF 2026)
    // ==========================================
    
    // Fracción VI (Mensual)
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

    // Fracción IV (Quincenal - 15 días)
    private array $quincenalIsrTable = [
        ['lower' => 0.01, 'upper' => 416.70, 'fixed_fee' => 0.00, 'rate' => 0.0192],
        ['lower' => 416.71, 'upper' => 3537.15, 'fixed_fee' => 7.95, 'rate' => 0.0640],
        ['lower' => 3537.16, 'upper' => 6216.15, 'fixed_fee' => 207.75, 'rate' => 0.1088],
        ['lower' => 6216.16, 'upper' => 7225.95, 'fixed_fee' => 499.20, 'rate' => 0.1600],
        ['lower' => 7225.96, 'upper' => 8651.40, 'fixed_fee' => 660.75, 'rate' => 0.1792],
        ['lower' => 8651.41, 'upper' => 17448.75, 'fixed_fee' => 916.20, 'rate' => 0.2136],
        ['lower' => 17448.76, 'upper' => 27501.60, 'fixed_fee' => 2795.25, 'rate' => 0.2352],
        ['lower' => 27501.61, 'upper' => 52505.25, 'fixed_fee' => 5159.70, 'rate' => 0.3000],
        ['lower' => 52505.26, 'upper' => 70006.95, 'fixed_fee' => 12660.75, 'rate' => 0.3200],
        ['lower' => 70006.96, 'upper' => 210020.70, 'fixed_fee' => 18261.30, 'rate' => 0.3400],
        ['lower' => 210020.71, 'upper' => 999999999.00, 'fixed_fee' => 65866.05, 'rate' => 0.3500],
    ];

    // Fracción II (Semanal - 7 días)
    private array $semanalIsrTable = [
        ['lower' => 0.01, 'upper' => 194.46, 'fixed_fee' => 0.00, 'rate' => 0.0192],
        ['lower' => 194.47, 'upper' => 1650.67, 'fixed_fee' => 3.71, 'rate' => 0.0640],
        ['lower' => 1650.68, 'upper' => 2900.87, 'fixed_fee' => 96.95, 'rate' => 0.1088],
        ['lower' => 2900.88, 'upper' => 3372.11, 'fixed_fee' => 232.96, 'rate' => 0.1600],
        ['lower' => 3372.12, 'upper' => 4037.32, 'fixed_fee' => 308.35, 'rate' => 0.1792],
        ['lower' => 4037.33, 'upper' => 8142.75, 'fixed_fee' => 427.56, 'rate' => 0.2136],
        ['lower' => 8142.76, 'upper' => 12834.08, 'fixed_fee' => 1304.45, 'rate' => 0.2352],
        ['lower' => 12834.09, 'upper' => 24502.45, 'fixed_fee' => 2407.86, 'rate' => 0.3000],
        ['lower' => 24502.46, 'upper' => 32669.91, 'fixed_fee' => 5908.35, 'rate' => 0.3200],
        ['lower' => 32669.92, 'upper' => 98009.66, 'fixed_fee' => 8521.94, 'rate' => 0.3400],
        ['lower' => 98009.67, 'upper' => 999999999.00, 'fixed_fee' => 30737.49, 'rate' => 0.3500],
    ];

    // ==========================================
    // HELPERS DE PERIODO
    // ==========================================
    private function getPeriodDays(string $periodicity): float
    {
        return match ($periodicity) {
            'quincenal' => 15.0,
            'semanal' => 7.0,
            default => 30.4, // Mensual
        };
    }

    private function getIsrTable(string $periodicity): array
    {
        return match ($periodicity) {
            'quincenal' => $this->quincenalIsrTable,
            'semanal' => $this->semanalIsrTable,
            default => $this->monthlyIsrTable,
        };
    }

    // ==========================================
    // MÉTODOS ISR
    // ==========================================
    private function calculateSubsidy(float $salary, string $periodicity): float
    {
        $days = $this->getPeriodDays($periodicity);
        $topeSubsidio = round(($this->topeMensualSubsidio / 30.4) * $days, 2);

        if ($salary > 0 && $salary <= $topeSubsidio) {
            $subsidioDiario = ($this->umaMensual * 0.1502) / 30.4;
            return round($subsidioDiario * $days, 2);
        }
        return 0.0;
    }

    public function calculateISR(float $salary, string $periodicity): float
    {
        $days = $this->getPeriodDays($periodicity);
        $salarioMinimoPeriodo = $this->salarioMinimoDiario * $days;

        if ($salary <= $salarioMinimoPeriodo) {
            return 0.0;
        }

        $table = $this->getIsrTable($periodicity);

        foreach ($table as $row) {
            if ($salary >= $row['lower'] && $salary <= $row['upper']) {
                $surplus = $salary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];
                
                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($salary, $periodicity);
                
                $finalIsr = $calculatedIsr - $subsidy;
                return $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
            }
        }
        return 0.0;
    }

    public function getIsrBreakdown(float $salary, string $periodicity = 'mensual'): array
    {
        $days = $this->getPeriodDays($periodicity);
        $salarioMinimoPeriodo = $this->salarioMinimoDiario * $days;

        if ($salary <= 0) {
            return [
                'base' => 0, 'lower_limit' => 0, 'surplus' => 0, 'rate' => 0,
                'marginal_tax' => 0, 'fixed_fee' => 0, 'calculated_isr' => 0,
                'subsidy_applied' => 0, 'total_isr' => 0, 'is_minimum_wage' => false
            ];
        }

        $isMinimumWage = $salary <= $salarioMinimoPeriodo;
        $table = $this->getIsrTable($periodicity);

        foreach ($table as $row) {
            if ($salary >= $row['lower'] && $salary <= $row['upper']) {
                $surplus = $salary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($salary, $periodicity);
                $finalIsr = $calculatedIsr - $subsidy;

                if ($isMinimumWage) {
                    $finalIsr = 0.0;
                } else {
                    $finalIsr = $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
                }

                return [
                    'base' => $salary,
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
    // MÉTODOS IMSS
    // ==========================================
    public function calculateIMSS(float $salary, string $periodicity): float
    {
        $days = $this->getPeriodDays($periodicity);
        $salarioMinimoPeriodo = $this->salarioMinimoDiario * $days;
        
        $salarioDiario = $salary / $days; 
        $sbc = $salarioDiario * $this->factorIntegracionBasico;

        $topeSbcDiario = $this->umaDiaria * 25;
        if ($sbc > $topeSbcDiario) {
            $sbc = $topeSbcDiario;
        }

        if ($salary <= $salarioMinimoPeriodo) {
            return 0.0;
        }

        $pctEspecie = 0.00375;  
        $pctDinero = 0.0025;    
        $pctInvalidez = 0.00625; 
        $pctCesantia = 0.01125;  

        $cuotaBaseDiaria = $sbc * ($pctEspecie + $pctDinero + $pctInvalidez + $pctCesantia);

        $cuotaExcedenteDiaria = 0.0;
        $tresUmas = $this->umaDiaria * 3;

        if ($sbc > $tresUmas) {
            $cuotaExcedenteDiaria = ($sbc - $tresUmas) * 0.0040; 
        }

        $retencionPeriodo = ($cuotaBaseDiaria + $cuotaExcedenteDiaria) * $days;

        return round($retencionPeriodo, 2);
    }

    // ==========================================
    // CÁLCULO FINAL DE NÓMINA (MOTOR UNIFICADO)
    // ==========================================
    public function calculatePayroll(Employee $employee): array
    {
        $periodicity = $employee->periodicity ?? 'mensual';

        // 1. Calcular el Salario Bruto del Periodo Específico
        $periodSalary = match ($periodicity) {
            'quincenal' => $employee->base_salary / 2,
            'semanal' => $employee->base_salary * 7 / 30.4,
            default => $employee->base_salary,
        };

        // 2. Retenciones de Ley Obligatorias
        $isrRetention = $this->calculateISR($periodSalary, $periodicity);
        $imssRetention = $this->calculateIMSS($periodSalary, $periodicity);

        $customDeductionsList = [];
        $totalCustomDeductions = 0.0;
        $deductions = $employee->activeDeductions;

        // ---------------------------------------------------------
        // FASE 1: Deducciones sobre el Salario Bruto del Periodo
        // ---------------------------------------------------------
        $deduccionesBrutas = 0.0;
        foreach ($deductions->where('amount_type', 'percentage_gross') as $deduction) {
            $amountToDeduct = $periodSalary * ($deduction->amount / 100);

            $deduccionesBrutas += $amountToDeduct;
            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        // ---------------------------------------------------------
        // FASE 2: Obtener Salario Neto Legal del Periodo (Pensión)
        // ---------------------------------------------------------
        $alimonyBase = $periodSalary - $isrRetention - $imssRetention;

        // ---------------------------------------------------------
        // FASE 3: Deducciones sobre el Neto, Fijas y VSM (Prorrateadas)
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', '!=', 'percentage_gross') as $deduction) {
            $amountToDeduct = 0.0;

            switch ($deduction->amount_type) {
                case 'fixed':
                    // Prorrateo de monto fijo mensual al periodo
                    $amountToDeduct = match ($periodicity) {
                        'quincenal' => $deduction->amount / 2,
                        'semanal' => $deduction->amount * 7 / 30.4,
                        default => $deduction->amount,
                    };
                    break;

                case 'percentage_net':
                    // Pensión Alimenticia
                    $amountToDeduct = $alimonyBase * ($deduction->amount / 100);
                    break;

                case 'vsm':
                    // Prorrateo Factor Infonavit mensual al periodo
                    $umiMensual = $this->umiDiaria * 30.4;
                    $deduccionMensual = ($deduction->amount * $umiMensual) + 15.00;
                    $amountToDeduct = match ($periodicity) {
                        'quincenal' => $deduccionMensual / 2,
                        'semanal' => $deduccionMensual * 7 / 30.4,
                        default => $deduccionMensual,
                    };
                    break;
            }

            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        // Salario Neto Final del Periodo a depositar
        $finalNetSalary = $periodSalary - $isrRetention - $imssRetention - $totalCustomDeductions;

        return [
            'gross_salary' => round($periodSalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'total_custom_deductions' => round($totalCustomDeductions, 2),
            'custom_deductions' => $customDeductionsList,
            'net_salary' => round($finalNetSalary, 2),
            'period_type' => ucfirst($periodicity), // Útil para la vista
        ];
    }
}
