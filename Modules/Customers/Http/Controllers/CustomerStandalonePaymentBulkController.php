<?php

namespace Modules\Customers\Http\Controllers;

/**
 * Compatibility controller for historical /customer-payment-bulk URLs.
 *
 * All execution is inherited from the Customers-owned Bulk Payment controller;
 * no Contact, Accounting, Petro, Superadmin or core App model is referenced.
 */
class CustomerStandalonePaymentBulkController extends CustomerBulkPaymentController
{
}
