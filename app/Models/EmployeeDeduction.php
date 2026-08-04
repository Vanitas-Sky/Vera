<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'sat_key',
        'description',
        'amount_type',
        'amount',
        'is_active',
    ];

    // Una deducción pertenece a un empleado
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}