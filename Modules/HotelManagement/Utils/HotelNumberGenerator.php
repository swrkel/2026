<?php
namespace Modules\HotelManagement\Utils;
class HotelNumberGenerator { public static function make(string $prefix, int $id): string { return $prefix . str_pad((string)$id, 6, '0', STR_PAD_LEFT); } }
