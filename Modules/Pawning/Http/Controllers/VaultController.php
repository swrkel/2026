<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Pawning\Models\VaultLocation;

class VaultController extends Controller
{
    public function index()
    {
        $vaults = VaultLocation::orderBy('vault_name')->paginate(20);
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        return view('pawning::vault.index', compact('vaults', 'locations'));
    }

    public function store(Request $request)
    {
        VaultLocation::create($request->all());
        return redirect()->route('pawning.vault.index')->with('status', ['success' => 1, 'msg' => 'Vault location saved successfully']);
    }
}
