<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Church Management settings — one set per business.
 *
 * Stored key/value in chc_settings rather than as columns, so a later phase can
 * add a setting without a migration on every tenant.
 */
class SettingsController extends Controller
{
    use ChurchTenantContext;

    /**
     * Known settings and their defaults.
     *
     * A setting absent from the table falls back to the value here, so the page
     * is usable before anything has ever been saved - and a new setting works
     * on every tenant the moment it is added to this list.
     */
    public const DEFAULTS = [
        'church_name'      => '',
        'pastor_name'      => '',
        'contact_phone'    => '',
        'contact_email'    => '',
        'address'          => '',
        'service_days'     => 'Sunday',
        'membership_notes' => '',
    ];

    public function index(Request $request)
    {
        $installed = $this->tableExists('settings');

        $settings = self::DEFAULTS;

        if ($installed) {
            $stored = $this->scopedQuery('settings')->pluck('setting_value', 'setting_key');

            foreach (array_keys(self::DEFAULTS) as $key) {
                if ($stored->has($key) && $stored[$key] !== null) {
                    $settings[$key] = $stored[$key];
                }
            }
        }

        return view('churchmanagement::settings.index', compact('installed', 'settings'));
    }

    public function update(Request $request)
    {
        if (! $this->tableExists('settings')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $data = $request->validate([
            'church_name'      => 'nullable|string|max:191',
            'pastor_name'      => 'nullable|string|max:191',
            'contact_phone'    => 'nullable|string|max:50',
            'contact_email'    => 'nullable|email|max:191',
            'address'          => 'nullable|string|max:1000',
            'service_days'     => 'nullable|string|max:191',
            'membership_notes' => 'nullable|string|max:2000',
        ]);

        $businessId = $this->businessId();

        if (! $businessId) {
            return back()->with('chc_error', 'No business is selected for this session.');
        }

        try {
            $table = $this->table('settings');

            /*
             | updateOrInsert on the (business_id, setting_key) unique index.
             | Only the keys declared in DEFAULTS are written, so a crafted
             | request cannot add arbitrary rows to the settings table.
             */
            foreach (array_keys(self::DEFAULTS) as $key) {
                DB::table($table)->updateOrInsert(
                    ['business_id' => $businessId, 'setting_key' => $key],
                    [
                        'setting_value' => $data[$key] ?? null,
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ]
                );
            }

            return back()->with('chc_success', 'Settings saved.');
        } catch (\Throwable $e) {
            Log::error('Church Management: settings save failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to save settings.');
        }
    }
}
