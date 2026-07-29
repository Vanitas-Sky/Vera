<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulatedInvoice extends Model
{
    use HasFactory;

    // Desactivamos la columna de actualización automática
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'test_uuid',
        'receiver_rfc',
        'receiver_name',
        'receiver_postal_code',
        'receiver_tax_regime_code',
        'cfdi_use_code',
        'payment_method_code',
        'payment_form_code',
        'total',
        'pdf_sandbox_path',
        'xml_sandbox_path'
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    // Relación: La factura simulada pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relaciones con los catálogos del SAT
    public function cfdiUse()
    {
        return $this->belongsTo(SatCfdiUse::class, 'cfdi_use_code', 'code');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(SatPaymentMethod::class, 'payment_method_code', 'code');
    }

    public function paymentForm()
    {
        return $this->belongsTo(SatPaymentForm::class, 'payment_form_code', 'code');
    }
}
