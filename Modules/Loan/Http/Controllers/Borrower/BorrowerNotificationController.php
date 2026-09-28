<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class BorrowerNotificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Borrower Notifications
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::borrower.notifications.index'
        );
    }
}