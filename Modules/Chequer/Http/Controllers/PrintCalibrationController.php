<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintCalibrationController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $business_id = $this->businessId();
        $profiles = $this->tableReady('cheq_print_calibrations')
            ? DB::table('cheq_print_calibrations')
                ->leftJoin('accounts', 'cheq_print_calibrations.bank_account_id', '=', 'accounts.id')
                ->leftJoin('cheq_templates', 'cheq_print_calibrations.cheq_template_id', '=', 'cheq_templates.id')
                ->where('cheq_print_calibrations.business_id', $business_id)
                ->select('cheq_print_calibrations.*', 'accounts.name as bank_account_name', 'accounts.account_number', 'cheq_templates.template_name')
                ->orderByDesc('cheq_print_calibrations.is_default')
                ->orderBy('cheq_print_calibrations.profile_name')
                ->get()
            : collect();

        return view('chequer::print_calibration.index', [
            'profiles' => $profiles,
            'bankAccounts' => $this->bankAccountsForDropdown(),
            'templates' => $this->templatesForDropdown(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'profile_name' => 'required|string|max:191',
            'bank_account_id' => 'nullable|integer',
            'cheq_template_id' => 'nullable|integer',
            'printer_name' => 'nullable|string|max:191',
            'x_offset_mm' => 'nullable|numeric',
            'y_offset_mm' => 'nullable|numeric',
            'date_x_offset_mm' => 'nullable|numeric',
            'date_y_offset_mm' => 'nullable|numeric',
            'payee_x_offset_mm' => 'nullable|numeric',
            'payee_y_offset_mm' => 'nullable|numeric',
            'amount_x_offset_mm' => 'nullable|numeric',
            'amount_y_offset_mm' => 'nullable|numeric',
            'words_x_offset_mm' => 'nullable|numeric',
            'words_y_offset_mm' => 'nullable|numeric',
            'signature_x_offset_mm' => 'nullable|numeric',
            'signature_y_offset_mm' => 'nullable|numeric',
            'scale_percent' => 'nullable|numeric|min:50|max:150',
            'is_default' => 'nullable|boolean',
        ]);

        $business_id = $this->businessId();
        $payload = array_merge($data, [
            'business_id' => $business_id,
            'is_default' => $request->boolean('is_default') ? 1 : 0,
            'created_by' => auth()->id(),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        if ($this->tableReady('cheq_print_calibrations')) {
            if (!empty($payload['is_default'])) {
                DB::table('cheq_print_calibrations')->where('business_id', $business_id)->update(['is_default' => 0, 'updated_at' => now()]);
            }
            DB::table('cheq_print_calibrations')->insert($payload);
        }

        return redirect('/chequer-module/print-calibration')->with('status', ['success' => 1, 'msg' => 'Print calibration profile saved successfully']);
    }

    protected function templatesForDropdown()
    {
        if (!$this->tableReady('cheq_templates')) return collect();
        return DB::table('cheq_templates')->where('business_id', $this->businessId())->orderBy('template_name')->get(['id','template_name']);
    }
}
