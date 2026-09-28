<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\PriceChangeNew\Services\PriceChangeContext;
use Modules\PriceChangeNew\Services\PriceChangeSettingsService;

class SettingsController extends Controller
{
    public function __construct(
        private PriceChangeContext $context,
        private PriceChangeSettingsService $settings
    ) {
    }

    public function index()
    {
        return view('pricechangenew::settings.index', [
            'settings' => $this->settings->all($this->context->businessId()),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'approval_required' => ['nullable', 'boolean'],
            'allow_self_approval' => ['nullable', 'boolean'],
            'auto_apply_on_approval' => ['nullable', 'boolean'],
            'auto_apply_due' => ['nullable', 'boolean'],
            'conflict_policy' => ['required', Rule::in(['stop_all', 'skip_conflicts'])],
            'default_application_scope' => ['required', Rule::in(['business_base', 'location_price_groups'])],
            'reference_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'reference_padding' => ['required', 'integer', 'min:3', 'max:12'],
        ]);

        foreach (['approval_required', 'allow_self_approval', 'auto_apply_on_approval', 'auto_apply_due'] as $key) {
            $validated[$key] = $request->boolean($key);
        }
        $validated['default_stock_price_mode'] = 'all_stock';
        $this->settings->save($this->context->businessId(), $validated, auth()->id());

        return redirect()->route('pricechangenew.settings.index')
            ->with('status', 'Price Change settings were saved successfully.');
    }
}
