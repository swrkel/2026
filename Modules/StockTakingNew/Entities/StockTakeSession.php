<?php
namespace Modules\StockTakingNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTakeSession extends Model
{
    protected $table = 'stk_sessions';
    protected $guarded = [];
    protected $casts = [
        'count_date' => 'date', 'cutoff_at' => 'datetime', 'scope_json' => 'array',
        'prepared_at' => 'datetime', 'started_at' => 'datetime', 'submitted_at' => 'datetime',
        'approved_at' => 'datetime', 'posted_at' => 'datetime', 'closed_at' => 'datetime',
        'system_qty_total' => 'decimal:4', 'counted_qty_total' => 'decimal:4',
        'variance_qty_total' => 'decimal:4', 'variance_value_total' => 'decimal:4',
        'freeze_stock' => 'boolean', 'require_recount' => 'boolean',
    ];
    public function lines(): HasMany { return $this->hasMany(StockTakeLine::class, 'session_id'); }
    public function counts(): HasMany { return $this->hasMany(StockTakeCount::class, 'session_id'); }
    public function assignments(): HasMany { return $this->hasMany(StockTakeAssignment::class, 'session_id'); }
    public function approvals(): HasMany { return $this->hasMany(StockTakeApproval::class, 'session_id'); }
    public function audits(): HasMany { return $this->hasMany(StockTakeAuditLog::class, 'session_id'); }
    public function shareLinks(): HasMany { return $this->hasMany(StockTakeShareLink::class, 'session_id'); }
}
