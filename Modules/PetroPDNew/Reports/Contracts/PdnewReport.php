<?php

namespace Modules\PetroPDNew\Reports\Contracts;

use Illuminate\Database\Query\Builder;

interface PdnewReport
{
    public function key(): string;
    public function title(): string;
    public function permission(): string;
    public function columns(): array;
    public function query(int $businessId, array $filters = []): Builder;
}
