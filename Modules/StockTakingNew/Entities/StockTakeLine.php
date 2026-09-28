<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StockTakeLine extends Model
{
    protected $table = 'stk_session_lines';
    protected $guarded = [];
    protected $casts = [
        'system_qty' => 'decimal:4', 'first_count_qty' => 'decimal:4', 'recount_qty' => 'decimal:4',
        'final_count_qty' => 'decimal:4', 'variance_qty' => 'decimal:4', 'unit_cost' => 'decimal:4',
        'variance_value' => 'decimal:4', 'counted_at' => 'datetime', 'recounted_at' => 'datetime',
        'is_counted' => 'boolean', 'requires_recount' => 'boolean', 'is_frozen' => 'boolean',
    ];
    public function session(): BelongsTo { return $this->belongsTo(StockTakeSession::class, 'session_id'); }
    public function counts(): HasMany { return $this->hasMany(StockTakeCount::class, 'line_id'); }
}
