<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider_name',
        'category',
        'description',
        'monthly_amount',
        'due_day',
        'contract_start_date',
        'contract_end_date',
        'is_active'
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'is_active' => 'boolean',
        'due_day' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
