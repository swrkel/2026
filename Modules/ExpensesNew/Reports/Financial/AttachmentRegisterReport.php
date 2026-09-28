<?php

namespace Modules\ExpensesNew\Reports\Financial;

class AttachmentRegisterReport
{
    public string $code = 'attachmentregister';
    public string $title = 'AttachmentRegister Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
