<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePrintLog extends PoneBaseModel
{
    protected $table = 'pone_print_logs';
    protected $casts = ['printed_at' => 'datetime'];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
}
