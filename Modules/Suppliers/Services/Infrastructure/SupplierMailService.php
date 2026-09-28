<?php

namespace Modules\Suppliers\Services\Infrastructure;

use Illuminate\Support\Facades\Mail;

/**
 * Suppliers module mail gateway.
 */
class SupplierMailService
{
    public function to($users)
    {
        return Mail::to($users);
    }

    public function queue($mailable): void
    {
        Mail::queue($mailable);
    }

    public function send($mailable): void
    {
        Mail::send($mailable);
    }
}
