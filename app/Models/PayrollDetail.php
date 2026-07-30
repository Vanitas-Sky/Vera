<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    public $timestamps = false; // Apagamos los timestamps según tu diseño

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'gross_salary',
        'isr_retention',
        'imss_employee',
        'net_salary'
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'isr_retention' => 'decimal:2',
        'imss_employee' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    // CORRECCIÓN: El método debe llamarse 'period' para coincidir con el Controlador y la Vista.
    // Le pasamos 'payroll_period_id' para que Laravel no intente buscar una columna llamada 'period_id'.
    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
