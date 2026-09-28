<?php

namespace Modules\SettlementSW\Entities;

/**
 * Settlement SW module-local model wrapper for the shared ERP TransactionPayment table/model.
 *
 * The wrapper keeps Settlement SW controller/service code independent from direct
 * App\TransactionPayment imports while preserving the existing database schema and behaviour.
 */
class SettlementSwTransactionPayment extends \App\TransactionPayment
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.transaction_payments', $this->getTable()));
    }

}
