<?php

namespace Modules\AutoService\Services;

class AutoServicePostingValidator
{
    public function validateInvoiceForPosting($invoice): array
    {
        $errors = [];
        if (empty($invoice->total_amount) || $invoice->total_amount <= 0) $errors[] = 'Invoice total must be greater than zero.';
        if (empty($invoice->job_id)) $errors[] = 'Invoice must be linked with a Job Card.';
        if (empty(config('autoservice.accounting.labour_income_account'))) $errors[] = 'Labour income account is not mapped.';
        if (empty(config('autoservice.accounting.parts_income_account'))) $errors[] = 'Parts income account is not mapped.';
        if (empty(config('autoservice.accounting.receivable_account'))) $errors[] = 'Receivable account is not mapped.';
        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }
}
