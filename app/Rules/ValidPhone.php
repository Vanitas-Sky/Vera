<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPhone implements Rule
{
    public function passes($attribute, $value)
    {
        // Limpiamos espacios y guiones
        $clean = preg_replace('/[^0-9]/', '', $value);
        // Validamos que sean exactamente 10 dígitos y no comiencen con 0
        return strlen($clean) === 10 && $clean[0] !== '0';
    }

    public function message()
    {
        return 'El número de teléfono debe contener 10 dígitos válidos.';
    }
}
