<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Loan\Entities\LoanOfficer;
use Modules\Loan\Entities\LoanSetting;

class LoanSetupController extends Controller
{
    protected $setupItemTypes = [
        'loan_purposes' => [
            'label' => 'Loan Purposes',
            'single' => 'Loan Purpose',
            'table' => 'loan_purposes',
            'tab' => 'loan_purposes',
            'icon' => 'fa-bullseye',
            'columns' => ['name', 'description', 'status'],
        ],
        'collateral_types' => [
            'label' => 'Collateral Types',
            'single' => 'Collateral Type',
            'table' => 'loan_collateral_types',
            'tab' => 'collateral_types',
            'icon' => 'fa-shield',
            'columns' => ['name', 'description', 'status'],
        ],
        'loan_charges' => [
            'label' => 'Loan Charges',
            'single' => 'Loan Charge',
            'table' => 'loan_charges',
            'tab' => 'loan_charges',
            'icon' => 'fa-money',
            'columns' => ['name', 'amount', 'charge_type', 'description', 'status'],
        ],
        'loan_statuses' => [
            'label' => 'Loan Status',
            'single' => 'Loan Status',
            'table' => 'loan_statuses',
            'tab' => 'loan_statuses',
            'icon' => 'fa-tags',
            'columns' => ['name', 'description', 'status'],
        ],
    ];

    public function index(Request $request)
    {
        $this->ensureTables();
        $business_id = $this->businessId();

        $settings = [
            'loan_application_prefix' => $this->getSetting($business_id, 'loan_application_prefix', 'LA'),
            'loan_application_starting_no' => $this->getSetting($business_id, 'loan_application_starting_no', '1'),
            'loan_application_next_no_preview' => $this->nextApplicationNoPreview($business_id),
        ];

        $officers = LoanOfficer::where('business_id', $business_id)->orderBy('name')->paginate(25)->appends($request->query());
        $users = $this->userOptions($business_id);
        $active_tab = $request->get('tab', 'application_no');
        $setup_types = $this->setupItemTypes;
        $setup_items = [];

        foreach ($setup_types as $type => $config) {
            $setup_items[$type] = $this->getSetupItems($business_id, $type);
        }

        return view('loan::loan_setup.index', compact('settings', 'officers', 'users', 'active_tab', 'setup_types', 'setup_items'));
    }

    public function saveApplicationNo(Request $request)
    {
        $this->ensureTables();
        $business_id = $this->businessId();

        $data = $request->validate([
            'loan_application_prefix' => ['required', 'string', 'max:20'],
            'loan_application_starting_no' => ['required', 'integer', 'min:1'],
        ]);

        $this->setSetting($business_id, 'loan_application_prefix', strtoupper(trim($data['loan_application_prefix'])));
        $this->setSetting($business_id, 'loan_application_starting_no', (string) ((int) $data['loan_application_starting_no']));

        return $this->redirectToSetup('application_no', 'Loan application starting number saved successfully.');
    }

    public function storeOfficer(Request $request)
    {
        $this->ensureTables();
        $business_id = $this->businessId();

        $data = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['business_id'] = $business_id;
        $data['created_by'] = optional(Auth::user())->id;
        $data['updated_by'] = optional(Auth::user())->id;

        LoanOfficer::create($data);

        return $this->redirectToSetup('loan_officers', 'Loan officer saved successfully.');
    }

    public function updateOfficer(Request $request, $id)
    {
        $this->ensureTables();
        $business_id = $this->businessId();
        $officer = LoanOfficer::where('business_id', $business_id)->findOrFail($id);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['updated_by'] = optional(Auth::user())->id;
        $officer->update($data);

        return $this->redirectToSetup('loan_officers', 'Loan officer updated successfully.');
    }

    public function deleteOfficer($id)
    {
        $this->ensureTables();
        $business_id = $this->businessId();
        LoanOfficer::where('business_id', $business_id)->findOrFail($id)->delete();

        return $this->redirectToSetup('loan_officers', 'Loan officer deleted successfully.');
    }

    public function storeSetupItem(Request $request)
    {
        $this->ensureTables();
        $business_id = $this->businessId();
        $type = $request->get('type');
        $config = $this->setupConfig($type);

        $data = $this->validateSetupItem($request, $type);
        $data['business_id'] = $business_id;
        $data['created_by'] = optional(Auth::user())->id;
        $data['updated_by'] = optional(Auth::user())->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table($config['table'])->insert($this->filterColumns($config['table'], $data));

        return $this->redirectToSetup($config['tab'], $config['single'] . ' saved successfully.');
    }

    public function updateSetupItem(Request $request, $id)
    {
        $this->ensureTables();
        $business_id = $this->businessId();
        $type = $request->get('type');
        $config = $this->setupConfig($type);

        $data = $this->validateSetupItem($request, $type);
        $data['updated_by'] = optional(Auth::user())->id;
        $data['updated_at'] = now();

        DB::table($config['table'])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->update($this->filterColumns($config['table'], $data));

        return $this->redirectToSetup($config['tab'], $config['single'] . ' updated successfully.');
    }

    public function deleteSetupItem(Request $request, $id)
    {
        $this->ensureTables();
        $business_id = $this->businessId();
        $type = $request->get('type');
        $config = $this->setupConfig($type);

        DB::table($config['table'])->where('business_id', $business_id)->where('id', $id)->delete();

        return $this->redirectToSetup($config['tab'], $config['single'] . ' deleted successfully.');
    }

    private function validateSetupItem(Request $request, $type)
    {
        $rules = [
            'type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if ($type === 'loan_charges') {
            $rules['amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['charge_type'] = ['nullable', 'string', 'max:100'];
        }

        $data = $request->validate($rules);
        unset($data['type']);

        if (isset($data['amount'])) {
            $data['amount'] = $data['amount'] === null || $data['amount'] === '' ? 0 : $data['amount'];
        }

        if ($type === 'loan_statuses') {
            $data['active'] = $data['status'] === 'active' ? 1 : 0;
        }

        return $data;
    }

    private function getSetupItems($business_id, $type)
    {
        $config = $this->setupConfig($type);
        $table = $config['table'];

        if (!Schema::hasTable($table)) {
            return collect();
        }

        $query = DB::table($table)->where('business_id', $business_id);

        if (Schema::hasColumn($table, 'name')) {
            $query->orderBy('name');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->get();
    }

    private function setupConfig($type)
    {
        if (!isset($this->setupItemTypes[$type])) {
            abort(404, 'Invalid loan setup type.');
        }
        return $this->setupItemTypes[$type];
    }

    private function filterColumns($table, array $data)
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (Schema::hasColumn($table, $key)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

    private function nextApplicationNoPreview($business_id)
    {
        $prefix = $this->getSetting($business_id, 'loan_application_prefix', 'LA');
        $start = (int) $this->getSetting($business_id, 'loan_application_starting_no', '1');
        $next = $start;

        if (Schema::hasTable('loan_applications') && Schema::hasColumn('loan_applications', 'application_no')) {
            $lastQuery = DB::table('loan_applications')
                ->where('application_no', 'like', $prefix . '-%');

            if (Schema::hasColumn('loan_applications', 'business_id')) {
                $lastQuery->where('business_id', $business_id);
            }

            $last = $lastQuery->orderBy('id', 'desc')->value('application_no');
            if (!empty($last) && preg_match('/(\d+)$/', $last, $matches)) {
                $next = max($start, ((int) $matches[1]) + 1);
            }
        }

        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    private function getSetting($business_id, $key, $default = null)
    {
        $value = LoanSetting::where('business_id', $business_id)->where('setting_key', $key)->value('setting_value');
        return $value === null || $value === '' ? $default : $value;
    }

    private function setSetting($business_id, $key, $value)
    {
        LoanSetting::updateOrCreate(
            ['business_id' => $business_id, 'setting_key' => $key],
            ['setting_value' => $value, 'updated_by' => optional(Auth::user())->id, 'created_by' => optional(Auth::user())->id]
        );
    }

    private function userOptions($business_id)
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->where('business_id', $business_id)
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(function ($user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                if ($name === '') {
                    $name = $user->username ?? $user->email ?? ('User #' . $user->id);
                }
                return [$user->id => $name];
            })->toArray();
    }

    private function ensureTables()
    {
        if (!Schema::hasTable('loan_settings')) {
            Schema::create('loan_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('setting_key', 100)->index();
                $table->text('setting_value')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'setting_key'], 'loan_settings_business_key_unique');
            });
        }

        if (!Schema::hasTable('loan_officers')) {
            Schema::create('loan_officers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('name', 191);
                $table->string('email', 191)->nullable();
                $table->string('mobile', 30)->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureSimpleSetupTable('loan_purposes');
        $this->ensureSimpleSetupTable('loan_collateral_types');
        $this->ensureSimpleSetupTable('loan_statuses', true);
        $this->ensureChargesTable();
    }

    private function ensureSimpleSetupTable($table, $hasActive = false)
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $tableBlueprint) use ($hasActive) {
                $tableBlueprint->increments('id');
                $tableBlueprint->unsignedInteger('business_id')->index();
                $tableBlueprint->string('name', 191);
                $tableBlueprint->text('description')->nullable();
                $tableBlueprint->string('status', 30)->default('active')->index();
                if ($hasActive) {
                    $tableBlueprint->tinyInteger('active')->default(1)->index();
                }
                $tableBlueprint->unsignedInteger('created_by')->nullable();
                $tableBlueprint->unsignedInteger('updated_by')->nullable();
                $tableBlueprint->timestamps();
            });
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table, $hasActive) {
            if (!Schema::hasColumn($table, 'business_id')) $tableBlueprint->unsignedInteger('business_id')->nullable()->index()->after('id');
            if (!Schema::hasColumn($table, 'description')) $tableBlueprint->text('description')->nullable();
            if (!Schema::hasColumn($table, 'status')) $tableBlueprint->string('status', 30)->default('active')->index();
            if ($hasActive && !Schema::hasColumn($table, 'active')) $tableBlueprint->tinyInteger('active')->default(1)->index();
            if (!Schema::hasColumn($table, 'created_by')) $tableBlueprint->unsignedInteger('created_by')->nullable();
            if (!Schema::hasColumn($table, 'updated_by')) $tableBlueprint->unsignedInteger('updated_by')->nullable();
            if (!Schema::hasColumn($table, 'created_at')) $tableBlueprint->timestamp('created_at')->nullable();
            if (!Schema::hasColumn($table, 'updated_at')) $tableBlueprint->timestamp('updated_at')->nullable();
        });
    }

    private function ensureChargesTable()
    {
        if (!Schema::hasTable('loan_charges')) {
            Schema::create('loan_charges', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('name', 191);
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('charge_type', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        Schema::table('loan_charges', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_charges', 'business_id')) $table->unsignedInteger('business_id')->nullable()->index()->after('id');
            if (!Schema::hasColumn('loan_charges', 'amount')) $table->decimal('amount', 22, 4)->default(0);
            if (!Schema::hasColumn('loan_charges', 'charge_type')) $table->string('charge_type', 100)->nullable();
            if (!Schema::hasColumn('loan_charges', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('loan_charges', 'status')) $table->string('status', 30)->default('active')->index();
            if (!Schema::hasColumn('loan_charges', 'created_by')) $table->unsignedInteger('created_by')->nullable();
            if (!Schema::hasColumn('loan_charges', 'updated_by')) $table->unsignedInteger('updated_by')->nullable();
            if (!Schema::hasColumn('loan_charges', 'created_at')) $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('loan_charges', 'updated_at')) $table->timestamp('updated_at')->nullable();
        });
    }

    private function redirectToSetup($tab, $message)
    {
        return redirect()->route('loan.setup.index', ['tab' => $tab])->with('status', ['success' => 1, 'msg' => $message]);
    }

    private function businessId()
    {
        return request()->session()->get('user.business_id') ?: request()->session()->get('business.id') ?: optional(Auth::user())->business_id;
    }
}
