<?php
namespace Modules\EggManagement\Utilities;
class Number
{
    public static function money($v) { return number_format((float)$v, config('egg.currency_decimals',4), '.', ','); }
    public static function qty($v) { return number_format((float)$v, config('egg.quantity_decimals',0), '.', ','); }
}
