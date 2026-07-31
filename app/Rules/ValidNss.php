<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNss implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nss = trim($value);

        if (!preg_match('/^\d{11}$/', $nss)) {
            $fail("El :attribute debe contener exactamente 11 dígitos numéricos.");
            return;
        }

        if (!$this->validarDigitoVerificador($nss)) {
            $fail("El :attribute es falso. No superó el Algoritmo de Luhn del IMSS.");
        }
    }

    private function validarDigitoVerificador(string $nss): bool
    {
        $suma = 0;

        // Se evalúan los primeros 10 dígitos (el 11 es el verificador)
        for ($i = 0; $i < 10; $i++) {
            $digito = (int)$nss[$i];
            // En el NSS, las posiciones impares visuales (índice par en código 0,2,4...) multiplican x1, y las pares x2
            $multiplicador = ($i % 2 === 0) ? 1 : 2; 
            
            $producto = $digito * $multiplicador;
            
            // Si el producto es de 2 dígitos (ej. 14), se suman entre sí (1+4=5). Matemáticamente es lo mismo que restar 9.
            if ($producto > 9) {
                $producto -= 9;
            }
            
            $suma += $producto;
        }

        $digitoCalculado = (10 - ($suma % 10)) % 10;

        return (int)$nss[10] === $digitoCalculado;
    }
}