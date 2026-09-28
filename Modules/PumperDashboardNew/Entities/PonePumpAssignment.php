<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePumpAssignment extends PoneBaseModel
{
    protected $table = 'pone_pump_assignments';
    protected $casts = [
        'opening_meter' => 'decimal:6',
        'current_meter' => 'decimal:6',
        'closing_meter' => 'decimal:6',
        'testing_quantity' => 'decimal:6',
        'sold_quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'amount' => 'decimal:4',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function readings() { return $this->hasMany(PoneMeterReading::class, 'assignment_id')->orderByDesc('recorded_at'); }
    public function events() { return $this->hasMany(PoneAssignmentEvent::class, 'assignment_id')->orderByDesc('occurred_at'); }
}
