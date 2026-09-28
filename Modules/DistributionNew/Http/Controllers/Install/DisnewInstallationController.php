<?php

namespace Modules\DistributionNew\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewInstallationStep;

class DisnewInstallationController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id') ?? 0);
        $this->seedDefaultSteps($businessId);
        $steps = DisnewInstallationStep::where('business_id', $businessId)->orderBy('id')->get();

        return view('distributionnew::install.checklist', compact('steps'));
    }

    public function complete(Request $request, int $id)
    {
        $step = DisnewInstallationStep::findOrFail($id);
        $step->update([
            'status' => 'completed',
            'notes' => $request->input('notes'),
            'completed_by' => optional($request->user())->id,
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('status', __('distributionnew::lang.installation_step_completed'));
    }

    protected function seedDefaultSteps(int $businessId): void
    {
        $steps = [
            'upload_files' => 'Upload DistributionNew module files',
            'run_sql' => 'Run DISNEW master or staged SQL',
            'clear_cache' => 'Clear Laravel cache',
            'verify_permissions' => 'Verify Distribution New permissions',
            'verify_menu' => 'Verify sidebar/menu visibility',
            'verify_sms_bridge' => 'Verify SMS bridge',
            'verify_customer_bridge' => 'Verify customer lookup bridge',
            'run_health_check' => 'Run audit and health check',
        ];

        foreach ($steps as $key => $title) {
            DisnewInstallationStep::firstOrCreate([
                'business_id' => $businessId,
                'step_key' => $key,
            ], [
                'step_title' => $title,
                'status' => 'pending',
            ]);
        }
    }
}
