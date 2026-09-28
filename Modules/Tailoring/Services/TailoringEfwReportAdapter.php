<?php
namespace Modules\Tailoring\Services;
class TailoringEfwReportAdapter
{
    public function reports(): array
    {
        return ['tailoring.production','tailoring.material','tailoring.employee','tailoring.customer','tailoring.executive'];
    }
}
