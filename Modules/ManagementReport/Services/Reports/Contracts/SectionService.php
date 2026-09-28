<?php
namespace Modules\ManagementReport\Services\Reports\Contracts;

use Modules\ManagementReport\Support\ReportContext;

interface SectionService
{
    public function key();
    public function build(ReportContext $context);
}
