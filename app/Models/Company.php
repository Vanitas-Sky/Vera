<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rfc',
        'legal_name',
        'trade_name',
        'postal_code',
        'tax_regime_code',
        'pac_api_key_sandbox',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pac_api_key_sandbox',
    ];

    /**
     * Usuarios vinculados a esta empresa (N:M) a traves de user_companies.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_companies')
            ->withPivot('role_in_company')
            ->withTimestamps();
    }

    /**
     * Registros de la tabla pivote user_companies para esta empresa.
     */
    public function userCompanies(): HasMany
    {
        return $this->hasMany(UserCompany::class);
    }

    public function economicActivities()
    {
        return $this->belongsToMany(
            SatEconomicActivity::class, 
            'company_economic_activities', 
            'company_id', 
            'sat_economic_activity_id'
        );
    }
}
