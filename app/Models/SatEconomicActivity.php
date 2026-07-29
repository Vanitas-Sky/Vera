<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatEconomicActivity extends Model
{
    use HasFactory;

    public $timestamps = false; 

    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    public function companies()
    {
        return $this->belongsToMany(
            Company::class, 
            'company_economic_activities', 
            'sat_economic_activity_id', 
            'company_id'
        );
    }
}
