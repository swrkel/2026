<?php

namespace Modules\Loan\Console;

use Illuminate\Console\Command;

use Modules\Loan\Services\LoanBrokenPromiseDetectionService;

class DetectBrokenPromises
    extends Command
{
    /**
     * Signature
     */

    protected $signature =
        'loan:detect-broken-promises';

    /**
     * Description
     */

    protected $description =
        'Detect broken promises to pay';

    /**
     * Execute Command
     */

    public function handle()
    {
        $this->info(
            'Detecting broken promises...'
        );

        $service =
            new LoanBrokenPromiseDetectionService();

        $service->process();

        $this->info(
            'Broken promise detection completed.'
        );
    }
}