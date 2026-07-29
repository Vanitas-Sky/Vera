<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    // Roles conocidos del sistema (name)
    public const SUPER_ADMIN = 'SUPER_ADMIN';
    public const ADMIN_PYME = 'ADMIN_PYME';
    public const ACCOUNTANT = 'ACCOUNTANT';
    public const OPERATOR = 'OPERATOR';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Usuarios que tienen este rol global asignado.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
