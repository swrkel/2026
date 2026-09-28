<?php
namespace Modules\SimpleAudit\Models;
class StockSnapshot extends SimpleAuditModel
{
    protected $table = 'sau_stock_snapshots';
    protected $casts = ['qty_before' => 'decimal:6', 'qty_after' => 'decimal:6', 'changed_at' => 'datetime'];
}
