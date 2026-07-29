<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CfdiItem;

class Cfdi extends Model
{
    use HasFactory;

    protected $table = 'cfdis'; // Aseguramos el nombre correcto de la tabla

    protected $fillable = [
        'company_id', 'uuid', 'rfc_issuer', 'name_issuer', 
        'rfc_receiver', 'name_receiver', 'invoice_type', 'issue_date',
        'subtotal', 'vat_amount', 'vat_retention', 'isr_retention', 'total',
        'payment_method_code', 'payment_form_code', 'cfdi_use_code',
        'deductibility_status', 'raw_xml_path'
    ];

    // Casteo de tipos para facilitar operaciones
    protected $casts = [
        'issue_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_retention' => 'decimal:2',
        'isr_retention' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relación: Una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relación: Una factura tiene muchos conceptos (ítems)
    public function items()
    {
        return $this->hasMany(CfdiItem::class, 'cfdi_id');
    }

    // Relaciones con los catálogos del SAT
    public function paymentMethod()
    {
        return $this->belongsTo(SatPaymentMethod::class, 'payment_method_code', 'code');
    }

    public function paymentForm()
    {
        return $this->belongsTo(SatPaymentForm::class, 'payment_form_code', 'code');
    }

    public function cfdiUse()
    {
        return $this->belongsTo(SatCfdiUse::class, 'cfdi_use_code', 'code');
    }
}
