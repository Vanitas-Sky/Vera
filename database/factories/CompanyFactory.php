<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rfc' => strtoupper(fake()->unique()->bothify('???######???')),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->companySuffix(),
            'postal_code' => fake()->numerify('#####'),
            'tax_regime_code' => fake()->randomElement(['601', '612', '621', '626']),
            'pac_api_key_sandbox' => Str::random(40),
        ];
    }
}
