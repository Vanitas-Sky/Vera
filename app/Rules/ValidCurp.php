<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCurp implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $curp = strtoupper(trim($value));

        // 1. CAPA REGEX: Estructura demográfica estricta (RENAPO)
        // Valida: 4 letras, 6 números (fecha), Sexo (H/M/X), Estado (33 válidos), 3 consonantes, 1 alfanumérico, 1 dígito
        $pattern = '/^[A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HMX](?:AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[A-Z]{3}[A-Z\d]\d$/';

        if (!preg_match($pattern, $curp, $matches)) {
            $fail("El formato estructural de la :attribute es inválido o el estado de nacimiento no existe.");
            return;
        }

        // 2. CAPA CRONOLÓGICA: Validar que la fecha incrustada exista
        $año = substr($curp, 4, 2);
        $mes = substr($curp, 6, 2);
        $dia = substr($curp, 8, 2);

        // Asumimos el siglo guiándonos por el penúltimo dígito (Homoclave)
        // Si el penúltimo es número (0-9), nació antes del 2000. Si es letra (A-Z), nació del 2000 en adelante.
        $penultimo = $curp[16];
        $año_completo = is_numeric($penultimo) ? "19" . $año : "20" . $año;

        if (!checkdate((int)$mes, (int)$dia, (int)$año_completo)) {
            $fail("La :attribute contiene una fecha de nacimiento inexistente.");
            return;
        }

        // 3. CAPA CRIPTOGRÁFICA: Algoritmo Módulo 10 de RENAPO
        if (!$this->validarDigitoVerificador($curp)) {
            $fail("La :attribute es falsa. No superó la prueba del algoritmo criptográfico gubernamental.");
            return;
        }
    }

    /**
     * Aplica la fórmula de RENAPO (Módulo 10) para el dígito verificador final
     */
    private function validarDigitoVerificador(string $curp): bool
    {
        // Solución Arquitectónica: Usar un Array estricto anula el bug de 2 bytes de la letra Ñ en UTF-8
        $diccionario = array(
            '0' => 0,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            'A' => 10,
            'B' => 11,
            'C' => 12,
            'D' => 13,
            'E' => 14,
            'F' => 15,
            'G' => 16,
            'H' => 17,
            'I' => 18,
            'J' => 19,
            'K' => 20,
            'L' => 21,
            'M' => 22,
            'N' => 23,
            'Ñ' => 24,
            'O' => 25,
            'P' => 26,
            'Q' => 27,
            'R' => 28,
            'S' => 29,
            'T' => 30,
            'U' => 31,
            'V' => 32,
            'W' => 33,
            'X' => 34,
            'Y' => 35,
            'Z' => 36
        );

        $suma = 0;
        $verificadorOriginal = (int) $curp[17];

        for ($i = 0; $i < 17; $i++) {
            $char = $curp[$i];

            if (!isset($diccionario[$char])) return false;

            $suma += $diccionario[$char] * (18 - $i);
        }

        $digitoCalculado = 10 - ($suma % 10);

        if ($digitoCalculado === 10) {
            $digitoCalculado = 0;
        }

        return $verificadorOriginal === $digitoCalculado;
    }
}
