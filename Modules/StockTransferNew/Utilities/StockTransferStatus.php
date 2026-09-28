<?php

namespace Modules\StockTransferNew\Utilities;

class StockTransferStatus
{
    public const DRAFT = 'draft';
    public const PENDING = 'pending_approval';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const IN_TRANSIT = 'in_transit';
    public const RECEIVED = 'received';
    public const CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::IN_TRANSIT => 'In Transit',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        ];
    }
}
