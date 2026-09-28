<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\StockTransfer;

class DocumentController extends Controller
{
    public function dispatchNote(StockTransfer $transfer)
    {
        $transfer->load('lines');
        return view('stocktransfernew::documents.dispatch_note', compact('transfer'));
    }

    public function receiveNote(StockTransfer $transfer)
    {
        $transfer->load('lines');
        return view('stocktransfernew::documents.receive_note', compact('transfer'));
    }
}
