<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Entities\TailoringFeatureSetting;
use Modules\Tailoring\Services\TailoringFeatureService;

class TailoringFeatureSettingController extends Controller
{
    public function index()
    {
        return view('tailoring::settings.smart_edition');
    }

    public function store(Request $request, TailoringFeatureService $service)
    {
        $answers = $request->input('setup_wizard_answers', []);
        $edition = $request->input('edition') ?: $service->editionFromWizard($answers);
        TailoringFeatureSetting::updateOrCreate(
            ['business_id' => session('business.id'), 'location_id' => $request->input('location_id')],
            [
                'edition' => $edition,
                'enabled_features' => $request->input('enabled_features', $service->editionFeatures($edition)),
                'menu_visibility' => $request->input('menu_visibility', []),
                'setup_wizard_answers' => $answers,
                'created_by' => auth()->id(),
            ]
        );
        return redirect()->back()->with('status', 'Tailoring edition and feature visibility saved successfully.');
    }
}
