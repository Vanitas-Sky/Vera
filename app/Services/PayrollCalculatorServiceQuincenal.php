<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Espejo de PayrollCalculatorService, pero para nómina QUINCENAL (pagos cada 15 días).
 *
 * SUPUESTO DE DISEÑO: $employee->base_salary se sigue interpretando como el salario
 * MENSUAL de referencia del empleado (igual que en el servicio mensual). Aquí se
 * calcula la percepción de la quincena como base_salary / 2, y sobre ese monto se
 * aplica la tarifa de ISR y el subsidio al empleo QUINCENALES (no la tabla mensual).
 *
 * El SAT publica en el Anexo 8 de la RMF tarifas para periodos distintos al mensual
 * (7, 10 y 15 días). Estas tarifas ya vienen así en el propio Anexo 8 (Apartado B,
 * fracciones II, III y IV): derivadas de una tabla DIARIA (mensual ÷ 30.4, redondeada
 * a 2 decimales) multiplicada por los días del periodo. NO son la tabla mensual
 * dividida entre 2 (eso da resultados ligeramente distintos a los oficiales). Las
 * tasas marginales (1.92% a 35%) son idénticas en todas las tablas.
 */
class PayrollCalculatorServiceQuincenal
{
    // ==========================================
    // VARIABLES FISCALES DE LEY (2026)
    // ==========================================
    // UMA y UMI son valores diarios/mensuales que NO dependen del periodo de nómina,
    // se usan igual que en el servicio mensual.
    private float $umaDiaria = 117.31; // UMA Diaria 2026 (INEGI/DOF 09-ene-2026, vigente desde 01-feb-2026)
    private float $umaMensual = 3566.22; // UMA Mensual 2026
    private float $umiDiaria = 100.81; // UMI 2026 (Infonavit, sin incremento)

    // Subsidio al empleo QUINCENAL 2026: igual que la tarifa ISR, el SAT prorratea por
    // días (mensual / 30.4 x 15), NO simplemente entre 2.
    private float $topeQuincenalSubsidio = 5670.75; // round(11492.66/30.4, 2) * 15

    // Salario mínimo QUINCENAL 2026: $315.04 * 15 días exactos (Zona General).
    // Nota: se usan 15 días exactos (no 30.4/2) para ser consistente con los "días
    // cotizados" de IMSS de una quincena real; en meses de 31 días la segunda quincena
    // legalmente puede tener 16 días, ajuste que este cálculo simplificado no contempla.
    private float $salarioMinimoQuincenal = 4725.60;

    // Factor de Integración Mínimo de Ley (igual que el servicio mensual, no depende del periodo)
    private float $factorIntegracionBasico = 1.0493;

    // Tarifa QUINCENAL Art. 96 LISR - valores VERBATIM del Anexo 8 RMF 2026, Apartado B,
    // fracción IV "Tarifa aplicable cuando hagan pagos que correspondan a un periodo de
    // 15 días" (DOF 28-dic-2025, PDF oficial del SAT). NO es la tabla mensual entre 2:
    // el SAT deriva primero una tabla diaria (mensual/30.4, redondeada a 2 decimales) y
    // luego la multiplica por 15 - por eso los límites no son exactamente la mitad de
    // los mensuales.
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

    // ==========================================
    // MÉTODOS ISR (mismo patrón que el servicio mensual, sobre la tarifa quincenal)
    // ==========================================
    private function calculateSubsidy(float $quincenalSalary): float
    {
        // Subsidio al empleo QUINCENAL 2026: (UMA mensual x 15.02% / 30.4 días) x 15 días
        if ($quincenalSalary > 0 && $quincenalSalary <= $this->topeQuincenalSubsidio) {
            $subsidioDiario = ($this->umaMensual * 0.1502) / 30.4;
            return round($subsidioDiario * 15, 2);
        }
        return 0.0;
    }

    public function calculateQuincenalISR(float $quincenalSalary): float
    {
        // REGLA DE ORO: Escudo del Salario Mínimo (Art. 96 LISR), versión quincenal
        if ($quincenalSalary <= $this->salarioMinimoQuincenal) {
            return 0.0;
        }

        foreach ($this->quincenalIsrTable as $row) {
            if ($quincenalSalary >= $row['lower'] && $quincenalSalary <= $row['upper']) {
                $surplus = $quincenalSalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($quincenalSalary);

                $finalIsr = $calculatedIsr - $subsidy;
                return $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
            }
        }
        return 0.0;
    }

    public function getIsrBreakdown(float $quincenalSalary): array
    {
        if ($quincenalSalary <= 0) {
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

        $isMinimumWage = $quincenalSalary <= $this->salarioMinimoQuincenal;

        foreach ($this->quincenalIsrTable as $row) {
            if ($quincenalSalary >= $row['lower'] && $quincenalSalary <= $row['upper']) {
                $surplus = $quincenalSalary - $row['lower'];
                $marginalTax = $surplus * $row['rate'];

                $calculatedIsr = $row['fixed_fee'] + $marginalTax;
                $subsidy = $this->calculateSubsidy($quincenalSalary);

                $finalIsr = $calculatedIsr - $subsidy;

                if ($isMinimumWage) {
                    $finalIsr = 0.0;
                } else {
                    $finalIsr = $finalIsr > 0 ? round($finalIsr, 2) : 0.0;
                }

                return [
                    'base' => $quincenalSalary,
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
     * Calcula la Cuota Obrera del IMSS por quincena (15 días cotizados).
     */
    public function calculateQuincenalIMSS(float $quincenalSalary): float
    {
        // 1. Salario Diario y SBC, usando 15 días exactos de la quincena (no 30.4)
        $salarioDiario = $quincenalSalary / 15;
        $sbc = $salarioDiario * $this->factorIntegracionBasico;

        // Tope legal del SBC: 25 UMAs (Art. 28 LSS) - es un tope diario, no cambia por periodo.
        $topeSbcDiario = $this->umaDiaria * 25;
        if ($sbc > $topeSbcDiario) {
            $sbc = $topeSbcDiario;
        }

        // Escudo IMSS: Art. 36 LSS
        if ($quincenalSalary <= $this->salarioMinimoQuincenal) {
            return 0.0;
        }

        $diasCotizadosQuincena = 15;

        // 2. Porcentajes de Ley (Cuota Obrera Fija) - iguales que en el cálculo mensual,
        // son porcentajes fijos por la Ley del Seguro Social, no varían por periodo de pago.
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

        // 4. Total quincenal a retener
        $retencionQuincenal = ($cuotaBaseDiaria + $cuotaExcedenteDiaria) * $diasCotizadosQuincena;

        return round($retencionQuincenal, 2);
    }

    // ==========================================
    // CÁLCULO FINAL DE NÓMINA QUINCENAL
    // ==========================================
    public function calculatePayroll(Employee $employee): array
    {
        // $employee->base_salary se asume MENSUAL; la quincena es la mitad.
        $quincenalSalary = $employee->base_salary / 2;

        $isrRetention = $this->calculateQuincenalISR($quincenalSalary);
        $imssRetention = $this->calculateQuincenalIMSS($quincenalSalary);

        $customDeductionsList = [];
        $totalCustomDeductions = 0.0;

        $deductions = $employee->activeDeductions;

        // ---------------------------------------------------------
        // FASE 1: Deducciones sobre el Salario Bruto (porcentaje, no necesita ajuste
        // por periodo: ya se calcula directo sobre el bruto de la quincena)
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', 'percentage_gross') as $deduction) {
            $amountToDeduct = $quincenalSalary * ($deduction->amount / 100);

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
        $alimonyBase = $quincenalSalary - $isrRetention - $imssRetention;

        // ---------------------------------------------------------
        // FASE 3: Deducciones sobre el Neto, Fijas y VSM
        //
        // OJO - SUPUESTO A VALIDAR: si en tu base de datos 'fixed' y el factor 'vsm'
        // están guardados como montos MENSUALES (igual que en el servicio mensual),
        // aquí se dividen entre 2 para prorratearlos a la quincena. Si en tu sistema
        // esos montos ya están capturados como monto POR QUINCENA (no mensual), quita
        // la división entre 2 en estos dos casos.
        // ---------------------------------------------------------
        foreach ($deductions->where('amount_type', '!=', 'percentage_gross') as $deduction) {
            $amountToDeduct = 0.0;

            switch ($deduction->amount_type) {
                case 'fixed':
                    // Monto fijo mensual prorrateado a la quincena
                    $amountToDeduct = $deduction->amount / 2;
                    break;

                case 'percentage_net':
                    // Pensión Alimenticia (sobre la Base Legal de la SCJN), ya calculada
                    // sobre el neto de la quincena - el porcentaje no se prorratea.
                    $amountToDeduct = $alimonyBase * ($deduction->amount / 100);
                    break;

                case 'vsm':
                    // Factor Infonavit mensual, prorrateado a la quincena.
                    $umiMensual = $this->umiDiaria * 30.4;
                    $amountToDeduct = (($deduction->amount * $umiMensual) + 15.00) / 2;
                    break;
            }

            $totalCustomDeductions += $amountToDeduct;

            $customDeductionsList[] = [
                'sat_key' => $deduction->sat_key,
                'description' => $deduction->description,
                'amount' => round($amountToDeduct, 2)
            ];
        }

        $finalNetSalary = $quincenalSalary - $isrRetention - $imssRetention - $totalCustomDeductions;

        return [
            'gross_salary' => round($quincenalSalary, 2),
            'isr_retention' => $isrRetention,
            'imss_retention' => $imssRetention,
            'total_custom_deductions' => round($totalCustomDeductions, 2),
            'custom_deductions' => $customDeductionsList,
            'net_salary' => round($finalNetSalary, 2),
        ];
    }
}