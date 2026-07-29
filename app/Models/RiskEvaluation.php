<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskEvaluation extends Model
{
    use HasFactory;

    // Le decimos a Laravel cómo se llama tu columna de creación y apagamos la de actualización
    const CREATED_AT = 'evaluated_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id', 'period_year', 'period_month', 'risk_score', 'anomalies_detected_json'
    ];

    protected $casts = [
        'risk_score' => 'integer',
        // ¡Magia de Laravel! Al acceder a $evaluation->anomalies_detected_json, será un Array de PHP automáticamente
        'anomalies_detected_json' => 'array', 
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
