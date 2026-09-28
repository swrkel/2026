<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\FeatureSwitch;

class FeatureSwitchController extends Controller
{
    public function index()
    {
        $records = FeatureSwitch::query()
            ->where('business_id', (int) session('business.id'))
            ->orderBy('feature_code')
            ->paginate(50);

        return view('airlineticketingnew::admin.feature-switches.index', compact('records'));
    }

    public function update(Request $request, FeatureSwitch $featureSwitch)
    {
        abort_unless((int) $featureSwitch->business_id === (int) session('business.id'), 404);

        $featureSwitch->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'config_json' => $request->input('config_json', []),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Feature switch updated successfully.']);
    }
}
