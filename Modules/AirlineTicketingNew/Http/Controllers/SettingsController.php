<?php

namespace Modules\AirlineTicketingNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\AirlineTicketingSetting;

class SettingsController extends Controller
{
    public function index()
    {
        $businessId = (int) session('business.id');

        $settings = AirlineTicketingSetting::query()
            ->where('business_id', $businessId)
            ->pluck('value_text', 'key');

        return view('airlineticketingnew::settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_currency_code' => ['nullable', 'string', 'max:3'],
            'default_service_fee' => ['nullable', 'numeric', 'min:0'],
            'ticket_prefix' => ['required', 'string', 'max:20'],
            'booking_prefix' => ['required', 'string', 'max:20'],
        ]);

        $businessId = (int) session('business.id');

        foreach ($data as $key => $value) {
            AirlineTicketingSetting::query()->updateOrCreate(
                [
                    'business_id' => $businessId,
                    'business_location_id' => null,
                    'store_id' => null,
                    'key' => $key,
                ],
                [
                    'value_text' => (string) $value,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('airline-ticketing-new.settings.index')
            ->with('status', [
                'success' => 1,
                'msg' => trans('airlineticketingnew::messages.settings_saved'),
            ]);
    }
}
