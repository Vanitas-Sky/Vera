<?php

namespace App\Services;

class PayrollCalculatorService
{
    // Variables fiscales de Ley (2024)
    private float $umaMensual = 3300.53;
    private float $topeMensualSubsidio = 9081.00;
    private float $salarioMinimoMensual = 7567.47; // $248.93 * 30.4 días (Zona General)

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

    private function calculateSubsidy(float $monthlySalary): float
    {
        if ($monthlySalary > 0 && $monthlySalary <= $this->topeMensualSubsidio) {
            return round($this->umaMensual * 0.1182, 2);
        }
        return 0.0;
    }

    public function calculateMonthlyISR(float $monthlySalary): float
    {
        // REGLA DE ORO: Escudo del Salario Mínimo (Art. 96)
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
        if ($monthlySalary <= 0) {
            return [
                'base' => 0, 'lower_limit' => 0, 'surplus' => 0, 'rate' => 0, 
                'marginal_tax' => 0, 'fixed_fee' => 0, 'calculated_isr' => 0, 
                'subsidy_applied' => 0, 'total_isr' => 0, 'is_minimum_wage' => false
            ];
        }

        // Verificamos si aplica el escudo
        $isMinimumWage = $monthlySalary <= $this->salarioMinimoMensual;

        foreach ($this->monthlyIsrTable as $row) {
            if ($monthlySalary >= $row['lower'] && $monthlySalary <= $row['upper']) {
                $surplus = $monthlySalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];
                
                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($monthlySalary);
                
                $finalIsr = $calculatedIsr - $subsidy;
                
                // Si es salario mínimo, forzamos a 0. Si no, calculamos normal.
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
                    'is_minimum_wage' => $isMinimumWage // Pasamos la bandera a la UI
                ];
            }
        }
        return [];
    }

    public function calculatePayroll(float $monthlySalary): array
    {
        $isrRetention = $this->calculateMonthlyISR($monthlySalary);
        $imssRetention = 0.0; 
        $netSalary = $monthlySalary - $isrRetention - $imssRetention;

        return [
            'gross_salary' => round($monthlySalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'net_salary' => round($netSalary, 2),
        ];
    }
}
