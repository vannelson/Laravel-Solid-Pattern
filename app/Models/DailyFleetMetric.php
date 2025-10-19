<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyFleetMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'metric_date',
        'captured_at',
        'total_units',
        'active_units',
        'available_units',
        'returns_due_today',
        'outstanding_maintenance',
        'utilization_pct',
        'notes',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'captured_at' => 'datetime',
        'utilization_pct' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}

