<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Espejo de PayrollCalculatorService, pero para nómina SEMANAL (pagos cada 7 días).
 *
 * SUPUESTO DE DISEÑO: $employee->base_salary se sigue interpretando como el salario
 * MENSUAL de referencia del empleado. La percepción semanal se calcula prorrateando
 * por días: base_salary x 7 / 30.4 (el mismo criterio de 30.4 días/mes promedio que
 * usa el propio SAT para derivar sus tablas periódicas - ver abajo). NO se usa /4 ni
 * /4.33, para ser consistentes con la fuente oficial.
 *
 * El SAT publica en el Anexo 8 de la RMF una tarifa específica para pagos de periodos
 * de 7 días (Apartado B, fracción II), verbatim de esa tarifa (Anexo-8-RMF-2026,
 * DOF 28-dic-2025). Se deriva de una tabla DIARIA (mensual ÷ 30.4, redondeada) x 7.
 * Las tasas marginales (1.92% a 35%) son idénticas a la tabla mensual.
 */
class PayrollCalculatorServiceSemanal
{
    // ==========================================
    // VARIABLES FISCALES DE LEY (2026)
    // ==========================================
    private float $umaDiaria = 117.31; // UMA Diaria 2026 (INEGI/DOF 09-ene-2026, vigente desde 01-feb-2026)
    private float $umaMensual = 3566.22; // UMA Mensual 2026
    private float $umiDiaria = 100.81; // UMI 2026 (Infonavit, sin incremento)

    // Subsidio al empleo SEMANAL 2026: prorrateado por días (mensual / 30.4 x 7).
    private float $topeSemanalSubsidio = 2646.35; // round(11492.66/30.4, 2) * 7

    // Salario mínimo SEMANAL 2026: $315.04 * 7 días exactos (Zona General).
    private float $salarioMinimoSemanal = 2205.28;

    // Factor de Integración Mínimo de Ley (igual que el servicio mensual, no depende del periodo)
    private float $factorIntegracionBasico = 1.0493;

    // Tarifa SEMANAL Art. 96 LISR - valores VERBATIM del Anexo 8 RMF 2026, Apartado B,
    // fracción II "Tarifa aplicable cuando hagan pagos que correspondan a un periodo de
    // 7 días" (DOF 28-dic-2025, PDF oficial del SAT).
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
    // MÉTODOS ISR
    // ==========================================
    private function calculateSubsidy(float $semanalSalary): float
    {
        // Subsidio al empleo SEMANAL 2026: (UMA mensual x 15.02% / 30.4 días) x 7 días
        if ($semanalSalary > 0 && $semanalSalary <= $this->topeSemanalSubsidio) {
            $subsidioDiario = ($this->umaMensual * 0.1502) / 30.4;
            return round($subsidioDiario * 7, 2);
        }
        return 0.0;
    }

    public function calculateSemanalISR(float $semanalSalary): float
    {
        // REGLA DE ORO: Escudo del Salario Mínimo (Art. 96 LISR), versión semanal
        if ($semanalSalary <= $this->salarioMinimoSemanal) {
            return 0.0;
        }

        foreach ($this->semanalIsrTable as $row) {
            if ($semanalSalary >= $row['lower'] && $semanalSalary <= $row['upper']) {
                $surplus = $semanalSalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($semanalSalary);

                $finalIsr = $calculatedIsr - $subsidy;
                return $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
            }
        }
        return 0.0;
    }

    public function getIsrBreakdown(float $semanalSalary): array
    {
        if ($semanalSalary <= 0) {
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

        $isMinimumWage = $semanalSalary <= $this->salarioMinimoSemanal;

        foreach ($this->semanalIsrTable as $row) {
            if ($semanalSalary >= $row['lower'] && $semanalSalary <= $row['upper']) {
                $surplus = $semanalSalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($semanalSalary);

                $finalIsr = $calculatedIsr - $subsidy;

                if ($isMinimumWage) {
                    $finalIsr = 0.0;
                } else {
                    $finalIsr = $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
                }

                return [
                    'base' => $semanalSalary,
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

    /**
     * Calcula la Cuota Obrera del IMSS por semana (7 días cotizados).
     */
    public function calculateSemanalIMSS(float $semanalSalary): float
    {
        // 1. Salario Diario y SBC, usando 7 días exactos de la semana
        $salarioDiario = $semanalSalary / 7;
        $sbc = $salarioDiario * $this->factorIntegracionBasico;

        // Tope legal del SBC: 25 UMAs (Art. 28 LSS) - es un tope diario, no cambia por periodo.
        $topeSbcDiario = $this->umaDiaria * 25;
        if ($sbc > $topeSbcDiario) {
            $sbc = $topeSbcDiario;
        }

        // Escudo IMSS: Art. 36 LSS
        if ($semanalSalary <= $this->salarioMinimoSemanal) {
            return 0.0;
        }

        $diasCotizadosSemana = 7;

        // 2. Porcentajes de Ley (Cuota Obrera Fija) - fijos por Ley del Seguro Social,
        // no varían por periodo de pago.
        $pctEspecie = 0.00375;
        $pctDinero = 0.0025;
        $pctInvalidez = 0.00625;
        $pctCesantia = 0.01125;

        $cuotaBaseDiaria = $sbc * ($pctEspecie + $pctDinero + $pctInvalidez + $pctCesantia);

        // 3. Regla del Excedente (Más de 3 UMAs)
        $cuotaExcedenteDiaria = 0.0;
        $tresUmas = $this->umaDiaria * 3;

        if ($sbc > $tresUmas) {
            $excedente = $sbc - $tresUmas;
            $cuotaExcedenteDiaria = $excedente * 0.0040;
        }

        // 4. Total semanal a retener
        $retencionSemanal = ($cuotaBaseDiaria + $cuotaExcedenteDiaria) * $diasCotizadosSemana;

        return round($retencionSemanal, 2);
    }

    // ==========================================
    // CÁLCULO FINAL DE NÓMINA SEMANAL
    // ==========================================
    public function calculatePayroll(Employee $employee): array
    {
        // $employee->base_salary se asume MENSUAL; la semana se prorratea por días
        // (x7 / 30.4), igual que hace el propio SAT para derivar su tarifa semanal.
        $semanalSalary = $employee->base_salary * 7 / 30.4;

        $isrRetention = $this->calculateSemanalISR($semanalSalary);
        $imssRetention = $this->calculateSemanalIMSS($semanalSalary);

        $customDeductionsList = [];
        $totalCustomDeductions = 0.0;

        $deductions = $employee->activeDeductions;

        // ---------------------------------------------------------
        // FASE 1: Deducciones sobre el Salario Bruto (porcentaje, no necesita ajuste
        // por periodo: ya se calcula directo sobre el bruto de la semana)
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', 'percentage_gross') as $deduction) {
            $amountToDeduct = $semanalSalary * ($deduction->amount / 100);

            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        // ---------------------------------------------------------
        // FASE 2: Salario Neto Legal (Base para Pensiones u Órdenes Judiciales sobre Neto)
        // ---------------------------------------------------------
        $alimonyBase = $semanalSalary - $isrRetention - $imssRetention;

        // ---------------------------------------------------------
        // FASE 3: Deducciones sobre el Neto, Fijas y VSM
        //
        // OJO - SUPUESTO A VALIDAR: si en tu base de datos 'fixed' y el factor 'vsm'
        // están guardados como montos MENSUALES, aquí se prorratean a la semana con el
        // mismo factor (x7/30.4). Si en tu sistema esos montos ya están capturados como
        // monto POR SEMANA, quita el prorrateo en estos dos casos.
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', '!=', 'percentage_gross') as $deduction) {
            $amountToDeduct = 0.0;

            switch ($deduction->amount_type) {
                case 'fixed':
                    // Monto fijo mensual prorrateado a la semana
                    $amountToDeduct = $deduction->amount * 7 / 30.4;
                    break;

                case 'percentage_net':
                    // Pensión Alimenticia (sobre la Base Legal de la SCJN), ya calculada
                    // sobre el neto de la semana - el porcentaje no se prorratea.
                    $amountToDeduct = $alimonyBase * ($deduction->amount / 100);
                    break;

                case 'vsm':
                    // Factor Infonavit mensual, prorrateado a la semana.
                    $umiMensual = $this->umiDiaria * 30.4;
                    $amountToDeduct = (($deduction->amount * $umiMensual) + 15.00) * 7 / 30.4;
                    break;
            }

            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        $finalNetSalary = $semanalSalary - $isrRetention - $imssRetention - $totalCustomDeductions;

        return [
            'gross_salary' => round($semanalSalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'total_custom_deductions' => round($totalCustomDeductions, 2),
            'custom_deductions' => $customDeductionsList,
            'net_salary' => round($finalNetSalary, 2),
        ];
    }
}