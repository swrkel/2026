<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    use Concerns;

    public function index()
    {
        $row = $this->tableReady('cheq_default_settings')
            ? DB::table('cheq_default_settings')->where('business_id', $this->businessId())->first()
            : null;

        $accounts = $this->bankAccountsForDropdown();
        $templates = $this->tableReady('cheq_templates')
            ? DB::table('cheq_templates')->where('business_id', $this->businessId())->pluck('template_name', 'id')
            : collect();

        return view('chequer::settings.index', compact('row', 'accounts', 'templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'default_bank_account_id' => 'nullable|integer',
            'default_template_id' => 'nullable|integer',
            'default_currency' => 'nullable|string|max:20',
            'default_font' => 'nullable|string|max:100',
            'default_font_size' => 'nullable|numeric'
        ]);

        $data['business_id'] = $this->businessId();
        $data['updated_at'] = now();

        $exists = DB::table('cheq_default_settings')->where('business_id', $this->businessId())->first();
        if ($exists) {
            DB::table('cheq_default_settings')->where('id', $exists->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('cheq_default_settings')->insert($data);
        }

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Settings saved successfully']);
    }
}
