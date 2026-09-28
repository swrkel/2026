<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ReportsOther\Services\SequenceService;

class NumberingController extends Controller
{
    public function store(Request $request, SequenceService $sequence)
    {
        $data = $request->validate([
            'prefix' => ['nullable', 'string', 'max:30'],
            'starting_number' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ]);

        $sequence->configure(
            config('reportsother.receipt_document_key'),
            $data['prefix'] ?? null,
            (int) $data['starting_number']
        );

        return redirect()->route('reports-other.cash-receipt.index', ['tab' => 'numbering'])
            ->with('status', 'Prefix and starting number saved successfully.');
    }
}
