<?php

namespace Modules\Loan\Console;

use Illuminate\Console\Command;

use Modules\Loan\Services\LoanRecoveryAssignmentService;

class AssignRecoveryQueues extends Command
{
    /**
     * Command Signature
     */

    protected $signature =
        'loan:assign-recovery-queues';

    /**
     * Command Description
     */

    protected $description =
        'Automatically assign overdue loans to recovery officers';

    /**
     * Execute Command
     */

    public function handle()
    {
        $this->info(
            'Starting recovery queue assignment...'
        );

        $service =
            new LoanRecoveryAssignmentService();

        $service->autoAssign();

        $this->info(
            'Recovery queue assignment completed successfully.'
        );
    }
}
