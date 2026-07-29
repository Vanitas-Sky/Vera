<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cfdi;

class CfdiItem extends Model
{
    use HasFactory;

    // Apagamos timestamps ya que no existen en la migración
    public $timestamps = false;

    protected $fillable = [
        'cfdi_id', 'sat_product_service_code', 'item_number',
        'original_description', 'nlp_interpreted_category',
        'quantity', 'unit_price', 'subtotal', 'vat_amount',
        'deductibility_status'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    // Relación: Un concepto pertenece a una factura
    public function cfdi()
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_id');
    }

    // Relación: Un concepto tiene un código del SAT
    public function satProductService()
    {
        return $this->belongsTo(SatProductService::class, 'sat_product_service_code', 'code');
    }
}
