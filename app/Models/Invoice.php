<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'uuid',
        'type',
        'issue_date',
        'subtotal',
        'iva',
        'total',
        'issuer_rfc',
        'issuer_name',
        'issuer_regimen',
        'issuer_cp',
        'receiver_rfc',
        'receiver_name',
        'receiver_regimen',
        'receiver_cp',
        'moneda',
        'metodo_pago',
        'forma_pago',
        'uso_cfdi',
        'items'
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'items' => 'array', // Laravel lo convertirá en arreglo automáticamente
    ];

    // Relación: Una factura pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
