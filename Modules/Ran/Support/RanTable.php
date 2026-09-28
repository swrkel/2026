<?php

namespace Modules\Ran\Support;

final class RanTable
{
    public static function name(string $suffix): string
    {
        return rtrim((string) config('ran.table_prefix', 'ran_'), '_').'_'.ltrim($suffix, '_');
    }
}
