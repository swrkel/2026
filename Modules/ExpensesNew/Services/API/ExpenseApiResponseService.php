<?php

namespace Modules\ExpensesNew\Services\API;

class ExpenseApiResponseService
{
    public function success(array $data = [], string $message = 'OK')
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
    public function error(string $message, int $status = 422, array $errors = [])
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
