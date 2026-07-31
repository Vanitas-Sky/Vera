<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRfc implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rfc = strtoupper(trim($value));

        // 1. CAPA REGEX: Estructura legal básica
        // Acepta Personas Morales (3 letras) y Físicas (4 letras), 6 números y 3 alfanuméricos.
        $pattern = '/^([A-ZÑ&]{3,4})(\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01]))([A-Z\d]{2})([A\d])$/';

        if (!preg_match($pattern, $rfc, $matches)) {
            $fail("El formato del :attribute es inválido.");
            return;
        }

        // 2. CAPA CRONOLÓGICA: Validar que la fecha incrustada en el RFC exista
        $fecha = $matches[2]; // Extrae YYMMDD
        $año = substr($fecha, 0, 2);
        $mes = substr($fecha, 2, 2);
        $dia = substr($fecha, 4, 2);

        // Ajuste heurístico del milenio (Asume que 00-30 es 2000s, y 31-99 es 1900s)
        $año_completo = ($año < 31) ? "20" . $año : "19" . $año;

        if (!checkdate((int)$mes, (int)$dia, (int)$año_completo)) {
            $fail("El :attribute contiene una fecha de nacimiento/creación inexistente.");
            return;
        }

        // 3. CAPA CRIPTOGRÁFICA: Algoritmo Módulo 11 del SAT
        if (!$this->validarDigitoVerificador($rfc)) {
            $fail("El :attribute es falso. No aprueba el algoritmo criptográfico del SAT.");
            return;
        }
    }

    /**
     * Aplica el diccionario y la fórmula oficial del SAT para comprobar el último dígito
     */
    private function validarDigitoVerificador(string $rfc): bool
    {
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
            '&' => 24,
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
            'Z' => 36,
            ' ' => 37,
            'Ñ' => 38
        );

        // Acomodar a 13 posiciones exactas (Las Personas Morales de 12 llevan un espacio imaginario al inicio)
        if (strlen($rfc) === 12) {
            $rfc = ' ' . $rfc;
        }

        // Sumatoria del producto de cada carácter por su peso inverso
        $suma = 0;
        for ($i = 0; $i < 12; $i++) {
            $char = $rfc[$i];
            // Si hay un carácter raro que pasó el Regex, fallamos por seguridad
            if (!isset($diccionario[$char])) return false;

            // El multiplicador del SAT empieza en 13 y va bajando
            $suma += $diccionario[$char] * (13 - $i);
        }

        // Obtener el módulo 11 y restar de 11
        $mod = $suma % 11;
        $digitoCalculado = 11 - $mod;

        // Reglas especiales de conversión del SAT
        if ($digitoCalculado === 11) {
            $digitoCalculado = '0';
        } else if ($digitoCalculado === 10) {
            $digitoCalculado = 'A';
        } else {
            $digitoCalculado = (string)$digitoCalculado;
        }

        // Comparamos el último dígito del RFC que mandó el usuario, contra nuestra matemática
        return $rfc[12] === $digitoCalculado;
    }
}
