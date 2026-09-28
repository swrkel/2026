<?php

namespace Modules\Loan\Console;

use Illuminate\Console\Command;

use Modules\Loan\Services\LoanDpdClassificationService;

class ProcessLoanDpdBuckets
    extends Command
{
    /**
     * Signature
     */

    protected $signature =
        'loan:process-dpd-buckets';

    /**
     * Description
     */

    protected $description =
        'Process loan DPD buckets';

    /**
     * Execute Command
     */

    public function handle()
    {
        $this->info(
            'Processing DPD buckets...'
        );

        $service =
            new LoanDpdClassificationService();

        $service->process();

        $this->info(
            'DPD bucket processing completed.'
        );
    }
}