<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWalletType;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletTypeService;

class DigitalWalletTypeController extends Controller
{
    public function index()
    {
        $types = DigitalWalletType::orderBy('type_name')->paginate(50);
        return view('digitalwallet::types.index', compact('types'));
    }

    public function create()
    {
        return view('digitalwallet::types.form', ['type' => new DigitalWalletType()]);
    }

    public function store(Request $request)
    {
        DigitalWalletType::create($this->validated($request));
        return redirect()->route('digitalwallet.types.index')->with('status', 'Wallet type created successfully.');
    }

    public function edit(DigitalWalletType $type)
    {
        return view('digitalwallet::types.form', compact('type'));
    }

    public function update(Request $request, DigitalWalletType $type)
    {
        $type->update($this->validated($request));
        return redirect()->route('digitalwallet.types.index')->with('status', 'Wallet type updated successfully.');
    }

    public function seedDefaults(DigitalWalletTypeService $service)
    {
        $service->seedDefaults();
        return redirect()->route('digitalwallet.types.index')->with('status', 'Default wallet types created / updated.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type_code' => 'required|string|max:100',
            'type_name' => 'required|string|max:191',
            'channel' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]) + ['currency' => 'LKR', 'is_default' => false, 'is_active' => true];
    }
}
