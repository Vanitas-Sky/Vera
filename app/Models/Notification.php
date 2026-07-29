<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Desactivamos updated_at porque solo tenemos created_at en la tabla
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id', 'alert_type', 'priority', 'title', 'message', 'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
