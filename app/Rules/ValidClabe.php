<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidClabe implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $clabe = trim($value);

        if (!preg_match('/^\d{18}$/', $clabe)) {
            $fail("La :attribute debe contener exactamente 18 dígitos numéricos.");
            return;
        }

        if (!$this->validarDigitoVerificador($clabe)) {
            $fail("La :attribute es falsa. No superó el algoritmo del Banco de México.");
        }
    }

    private function validarDigitoVerificador(string $clabe): bool
    {
        // El Banco de México usa los pesos 3, 2, 7 que se repiten para los primeros 17 dígitos
        $ponderaciones = [3, 2, 7, 3, 2, 7, 3, 2, 7, 3, 2, 7, 3, 2, 7, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 17; $i++) {
            $producto = (int)$clabe[$i] * $ponderaciones[$i];
            $suma += $producto % 10;
        }

        $digitoCalculado = (10 - ($suma % 10)) % 10;

        return (int)$clabe[17] === $digitoCalculado;
    }
}
