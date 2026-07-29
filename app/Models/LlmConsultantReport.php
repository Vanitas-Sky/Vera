<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LlmConsultantReport extends Model
{
    use HasFactory;

    const CREATED_AT = 'generated_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id', 'period_year', 'period_month', 'executive_summary', 'recommendations_json'
    ];

    protected $casts = [
        // Casteo automático para la lista estructurada de consejos del LLM
        'recommendations_json' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
