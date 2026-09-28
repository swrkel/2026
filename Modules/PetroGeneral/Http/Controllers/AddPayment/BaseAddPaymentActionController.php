<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Routing\Controller;
use Modules\PetroGeneral\Http\Controllers\AddPaymentController;

/**
 * PG021 safe wrapper base.
 * Keeps existing AddPaymentController business logic unchanged while allowing
 * settlement payment actions to be routed through smaller controllers.
 */
abstract class BaseAddPaymentActionController extends Controller
{
    /** @var AddPaymentController */
    protected $legacy;

    public function __construct(AddPaymentController $legacy)
    {
        $this->legacy = $legacy;
    }
}
