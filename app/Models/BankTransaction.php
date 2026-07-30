<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     */
    protected $fillable = [
        'company_id',
        'transaction_date',
        'description',
        'withdrawal',
        'deposit',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'transaction_date' => 'date',
        'withdrawal' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    /**
     * Relación: Una transacción bancaria pertenece a una única empresa.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}