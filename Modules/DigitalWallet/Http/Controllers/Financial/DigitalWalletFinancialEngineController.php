<?php

namespace Modules\DigitalWallet\Http\Controllers\Financial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletAdjustment;
use Modules\DigitalWallet\Entities\DigitalWalletReservation;
use Modules\DigitalWallet\Services\Financial\DigitalWalletFinancialEngineService;

class DigitalWalletFinancialEngineController extends Controller
{
    public function __construct(protected DigitalWalletFinancialEngineService $engine) {}

    public function index()
    {
        return view('digitalwallet::financial.index', [
            'wallets' => DigitalWallet::orderBy('wallet_name')->get(),
            'reservations' => DigitalWalletReservation::latest()->limit(50)->get(),
            'adjustments' => DigitalWalletAdjustment::latest()->limit(50)->get(),
            'stats' => [
                'reserved' => DigitalWalletReservation::where('status', 'reserved')->sum('amount'),
                'committed' => DigitalWalletReservation::where('status', 'committed')->whereDate('updated_at', today())->sum('amount'),
                'released' => DigitalWalletReservation::where('status', 'released')->whereDate('updated_at', today())->sum('amount'),
                'adjustments_today' => DigitalWalletAdjustment::whereDate('created_at', today())->sum('amount'),
            ],
        ]);
    }

    public function recharge(Request $request, DigitalWallet $wallet)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:50'],
            'external_reference' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string'],
        ]);

        $this->engine->recharge($wallet, $data['amount'], $data);

        return back()->with('status', 'Wallet recharge completed successfully.');
    }

    public function reserve(Request $request, DigitalWallet $wallet)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_reference' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string'],
        ]);

        $this->engine->reserve($wallet, $data['amount'], $data);

        return back()->with('status', 'Reservation created successfully.');
    }

    public function commit(DigitalWalletReservation $reservation)
    {
        $this->engine->commitReservation($reservation);

        return back()->with('status', 'Reservation committed successfully.');
    }

    public function release(DigitalWalletReservation $reservation)
    {
        $this->engine->releaseReservation($reservation);

        return back()->with('status', 'Reservation released successfully.');
    }

    public function adjustment(Request $request, DigitalWallet $wallet)
    {
        $data = $request->validate([
            'adjustment_type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string'],
        ]);

        $this->engine->adjust($wallet, $data['adjustment_type'], $data['amount'], $data);

        return back()->with('status', 'Adjustment posted successfully.');
    }
}
