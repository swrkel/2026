<?php

namespace Modules\Pawning\Services;

use Modules\Pawning\Models\Article;
use Modules\Pawning\Models\Pledge;

class PawningNumberService
{
    public function nextPledgeNo()
    {
        $next = (Pledge::max('id') ?: 0) + 1;
        return 'PW-' . date('ym') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function nextArticleNo()
    {
        $next = (Article::max('id') ?: 0) + 1;
        return 'ART-' . date('ym') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
