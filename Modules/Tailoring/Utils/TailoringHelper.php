<?php

namespace Modules\Tailoring\Utils;

class TailoringHelper
{
    public static function formatStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
