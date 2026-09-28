<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    use Concerns;

    public function index()
    {
        try {
            $business_id = $this->businessId();
            $stats = [
                'bank_accounts' => $this->bankAccountsCount(),
                'templates' => $this->safeCount('cheq_templates', ['business_id' => $business_id]),
                'cheque_books' => $this->safeCount('cheq_cheque_books', ['business_id' => $business_id]),
                'issued_cheques' => $this->safeCount('cheq_cheques', ['business_id' => $business_id]),
                'available_leaves' => $this->safeCount('cheq_cheque_leaves', ['business_id' => $business_id, 'status' => 'available']),
                'pending_print' => $this->safeCount('cheq_cheques', ['business_id' => $business_id, 'status' => 'draft']),
                'printed' => $this->safeCount('cheq_cheques', ['business_id' => $business_id, 'status' => 'printed']),
                'low_stock' => 0,
            ];

            return view('chequer::dashboard.index', compact('stats'));
        } catch (\Throwable $e) {
            \Log::error('Chequer dashboard failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            $stats = [
                'bank_accounts' => 0,
                'templates' => 0,
                'cheque_books' => 0,
                'issued_cheques' => 0,
                'available_leaves' => 0,
                'pending_print' => 0,
                'printed' => 0,
                'low_stock' => 0,
            ];
            return view('chequer::dashboard.index', compact('stats'));
        }
    }
}
