<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatProductService extends Model
{
    use HasFactory;

    // 1. Apagar timestamps si no los pusiste en la migración
    public $timestamps = false; 

    // 2. Indicar cuál es la llave primaria
    protected $primaryKey = 'code';

    // 3. Indicar que la llave primaria NO es autoincremental
    public $incrementing = false;

    // 4. Indicar que el tipo de la llave primaria es string
    protected $keyType = 'string';

    // 5. Permitir la asignación masiva
    protected $fillable = ['code', 'description'];
}
