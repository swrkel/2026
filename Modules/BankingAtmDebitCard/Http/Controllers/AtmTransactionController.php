<?php
namespace Modules\BankingAtmDebitCard\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class AtmTransactionController extends Controller
{
    public function index()
    {
        return view('banking-cards::dashboard.index');
    }

    public function create()
    {
        return view('banking-cards::cards.form');
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Saved successfully.');
    }

    public function show($id)
    {
        return view('banking-cards::cards.show', compact('id'));
    }

    public function edit($id)
    {
        return view('banking-cards::cards.form', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return back()->with('status', 'Updated successfully.');
    }

    public function destroy($id)
    {
        return back()->with('status', 'Deleted successfully.');
    }

    public function activate($debit_card) { return back()->with('status', 'Card activated.'); }
    public function hotlist($debit_card) { return back()->with('status', 'Card hotlisted.'); }
    public function replace($debit_card) { return back()->with('status', 'Replacement request saved.'); }
    public function cardRegister() { return view('banking-cards::reports.card_register'); }
    public function hotlistedCards() { return view('banking-cards::reports.hotlisted_cards'); }
    public function atmReconciliation() { return view('banking-cards::reports.atm_reconciliation'); }
    public function disputes() { return view('banking-cards::reports.disputes'); }
}
