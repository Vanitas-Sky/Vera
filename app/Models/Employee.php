<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'rfc',
        'curp',
        'full_name',
        'email',
        'position',
        'cp',
        'nss',
        'clabe',
        'hire_date',
        'base_salary',
        'work_regime',
        'periodicity',
        'is_active'
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    // Añade este método en tu modelo Employee
    public function activeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class)->where('is_active', true);
    }
}
