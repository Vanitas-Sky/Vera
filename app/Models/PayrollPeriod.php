<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'period_name',
        'start_date',
        'end_date',
        'total_gross',
        'total_isr_retention',
        'total_imss_employee',
        'total_imss_employer',
        'total_net'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_isr_retention' => 'decimal:2',
        'total_imss_employee' => 'decimal:2',
        'total_imss_employer' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class, 'payroll_period_id');
    }
}
