<?php
namespace Modules\StockTransferNew\Utilities;
class StockTransferTenant{public static function businessId(): ?int{return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;}public static function userId(): ?int{return optional(auth()->user())->id;}}
