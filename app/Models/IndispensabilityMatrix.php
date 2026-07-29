<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndispensabilityMatrix extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'sat_product_service_code',
        'deductibility_status',
        'notes'
    ];

    // Relación: Esta regla pertenece a una Empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relación: Esta regla se asocia a un Servicio/Producto del SAT
    public function satProductService()
    {
        return $this->belongsTo(SatProductService::class, 'sat_product_service_code', 'code');
    }
}