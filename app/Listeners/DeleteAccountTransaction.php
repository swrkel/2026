<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AccountTransaction;
use App\ContactLedger;
use App\Utils\ModuleUtil;

class DeleteAccountTransaction
{
    protected $moduleUtil;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // $account_module_enabled = $this->moduleUtil->isModuleEnabled('account');
        // We bypass the module enabled check since we always want to clean up ledgers
        
        \Log::info("DeleteAccountTransaction listener triggered for payment ID: " . $event->transactionPaymentId . " account_id: " . $event->accountId);
        
        AccountTransaction::where('transaction_payment_id', $event->transactionPaymentId)
            ->forceDelete();
        ContactLedger::where('transaction_payment_id', $event->transactionPaymentId)
            ->forceDelete();
            
        \Log::info("AccountTransaction and ContactLedger force deletion executed for payment ID: " . $event->transactionPaymentId);
    }
}
