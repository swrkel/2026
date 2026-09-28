<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Modules\ManagementReport\Services\Reports\ReviewSummaryService;
use Modules\ManagementReport\Support\ReportContext;

class FinalReviewSectionService extends BaseSectionService
{
    protected $sales;
    protected $stock;
    protected $outstanding;
    protected $variance;
    protected $dip;
    protected $summary;

    public function __construct(
        \Modules\ManagementReport\Support\SchemaGuard $schema,
        SalesSectionService $sales,
        StockValueSectionService $stock,
        OutstandingSectionService $outstanding,
        PumpVarianceSectionService $variance,
        DipDetailsSectionService $dip,
        ReviewSummaryService $summary
    ) {
        parent::__construct($schema);
        $this->sales = $sales;
        $this->stock = $stock;
        $this->outstanding = $outstanding;
        $this->variance = $variance;
        $this->dip = $dip;
        $this->summary = $summary;
    }

    public function key() { return 'final_review'; }

    public function build(ReportContext $context)
    {
        $sections = [
            'sales' => ['payload' => $this->sales->build($context)],
            'stock_value' => ['payload' => $this->stock->build($context)],
            'outstanding' => ['payload' => $this->outstanding->build($context)],
            'pump_variance' => ['payload' => $this->variance->build($context)],
            'dip_details' => ['payload' => $this->dip->build($context)],
        ];

        return ['rows' => $this->summary->build($sections)];
    }
}
