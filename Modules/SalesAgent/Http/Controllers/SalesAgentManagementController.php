<?php

namespace Modules\SalesAgent\Http\Controllers;

use App\BusinessLocation;
use App\User;
use App\Utils\ModuleUtil;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\SalesAgent\Entities\SalesAgent;
use Yajra\DataTables\Facades\DataTables;

/**
 * Sales Agent Management Controller
 *
 * Standalone SalesAgent module controller.
 */
class SalesAgentManagementController extends Controller
{
    /**
     * @var ModuleUtil
     */
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of sales agents with the add form on the same page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location_id = $this->getFirstDropdownKey($business_locations);

        $users = User::where('business_id', $business_id)
            ->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"))
            ->pluck('full_name', 'id');

        $sales_agents = SalesAgent::where('business_id', $business_id)
            ->pluck('name', 'id');

        $employment_grades = SalesAgent::where('business_id', $business_id)
            ->distinct()
            ->pluck('employment_grade')
            ->filter()
            ->toArray();

        $currency_precision = $this->getCurrencyPrecision();

        return view('salesagent::sales_agent_management.index')
            ->with(compact(
                'business_locations',
                'default_location_id',
                'users',
                'sales_agents',
                'employment_grades',
                'currency_precision'
            ));
    }

    /**
     * Get sales agents data for DataTable.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesAgentsData(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = SalesAgent::forBusiness($business_id);

        if (! Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
            $query = $query->withoutGlobalScopes();
        }

        $selectColumns = [
            'distribution_sales_agents.id',
            'distribution_sales_agents.business_id',
            'distribution_sales_agents.name',
            'distribution_sales_agents.joined_date',
            'distribution_sales_agents.employment_grade',
            'distribution_sales_agents.salary',
            'distribution_sales_agents.commission',
            'distribution_sales_agents.location_id',
            'distribution_sales_agents.user_id',
            'distribution_sales_agents.created_by',
            'distribution_sales_agents.created_at',
        ];

        if (Schema::hasColumn('distribution_sales_agents', 'commission_entitled')) {
            $selectColumns[] = 'distribution_sales_agents.commission_entitled';
        }

        if (Schema::hasColumn('distribution_sales_agents', 'commission_type')) {
            $selectColumns[] = 'distribution_sales_agents.commission_type';
        }

        $sales_agents = $query
            ->with(['location', 'user', 'createdBy'])
            ->select($selectColumns);

        if (! empty($request->location_id)) {
            $sales_agents->where('location_id', $request->location_id);
        }

        if (! empty($request->employment_grade)) {
            $sales_agents->where('employment_grade', $request->employment_grade);
        }

        if (! empty($request->sales_agent)) {
            $sales_agents->where('distribution_sales_agents.id', $request->sales_agent);
        }

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            try {
                $start = \Carbon\Carbon::createFromFormat('d/m/Y', $request->start_date)
                    ->startOfDay()
                    ->format('Y-m-d');

                $end = \Carbon\Carbon::createFromFormat('d/m/Y', $request->end_date)
                    ->endOfDay()
                    ->format('Y-m-d');

                $sales_agents->whereBetween('joined_date', [$start, $end]);
            } catch (\Exception $e) {
                Log::error('SalesAgent date filter error: ' . $e->getMessage());
            }
        }

        Debugbar::disable();

        $currency_precision = $this->getCurrencyPrecision();

        return DataTables::of($sales_agents)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        ' . __('messages.actions') . ' <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="#" class="view-sales-agent" data-href="' . route('salesagent.management.show', [$row->id]) . '"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>
                        <li><a href="#" class="edit-sales-agent" data-href="' . route('salesagent.management.edit', [$row->id]) . '"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</a></li>
                        <li><a href="#" class="add-commission" data-id="' . e($row->id) . '" data-name="' . e($row->name) . '"><i class="fa fa-plus"></i> ' . __('lang_v1.add_commission') . '</a></li>
                        <li><a href="' . route('salesagent.management.ledger', [$row->id]) . '"><i class="fa fa-book"></i> ' . __('lang_v1.ledger') . '</a></li>
                    </ul>
                </div>';

                return $html;
            })
            ->addColumn('joined_date', function ($row) {
                if (! isset($row->joined_date) || ! $row->joined_date) {
                    return '-';
                }

                return \Carbon\Carbon::parse($row->joined_date)->format('Y-m-d');
            })
            ->addColumn('employment_grade', function ($row) {
                return $row->employment_grade ?? '-';
            })
            ->addColumn('salary', function ($row) use ($currency_precision) {
                return number_format((float) ($row->salary ?? 0), $currency_precision);
            })
            ->addColumn('commission_entitled', function ($row) {
                $value = isset($row->commission_entitled) ? $row->commission_entitled : 'no';

                return $value === 'yes' ? __('messages.yes') : __('messages.no');
            })
            ->addColumn('commission_type', function ($row) {
                $entitled = isset($row->commission_entitled) ? $row->commission_entitled : 'no';

                if ($entitled !== 'yes') {
                    return '-';
                }

                $type = isset($row->commission_type) ? $row->commission_type : 'percentage';

                return $type === 'fixed' ? 'Fixed' : 'Percentage';
            })
            ->addColumn('commission', function ($row) use ($currency_precision) {
                $entitled = isset($row->commission_entitled) ? $row->commission_entitled : 'no';

                if ($entitled !== 'yes') {
                    return number_format(0, $currency_precision);
                }

                return number_format((float) ($row->commission ?? 0), $currency_precision);
            })
            ->addColumn('location_name', function ($row) {
                if (! isset($row->location) || ! $row->location) {
                    return '-';
                }

                return $row->location->name;
            })
            ->addColumn('added_by', function ($row) {
                return $row->createdBy ? trim($row->createdBy->first_name . ' ' . $row->createdBy->last_name) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created sales agent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|string|max:255',
                'joined_date' => 'required|date',
                'commission_entitled' => 'nullable|in:yes,no',
                'commission_type' => 'nullable|in:percentage,fixed',
            ]);

            $input = $this->buildSalesAgentInput($request, $business_id);
            $input['business_id'] = $business_id;
            $input['created_by'] = Auth::id();

            DB::beginTransaction();

            SalesAgent::create($input);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' ' . $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Display the specified sales agent.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $sales_agent = SalesAgent::forBusiness($business_id)
            ->with(['location', 'createdBy', 'user'])
            ->findOrFail($id);

        return view('salesagent::sales_agent_management.show')
            ->with(compact('sales_agent'));
    }

    /**
     * Show the form for editing the specified sales agent.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $sales_agent = SalesAgent::forBusiness($business_id)->findOrFail($id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location_id = $sales_agent->location_id ?: $this->getFirstDropdownKey($business_locations);
        $users = User::forDropdown($business_id, false);
        $currency_precision = $this->getCurrencyPrecision();

        return view('salesagent::sales_agent_management.edit')
            ->with(compact('sales_agent', 'business_locations', 'default_location_id', 'users', 'currency_precision'));
    }

    /**
     * Update the specified sales agent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return array
     */
    public function update(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|string|max:255',
                'joined_date' => 'required|date',
                'commission_entitled' => 'nullable|in:yes,no',
                'commission_type' => 'nullable|in:percentage,fixed',
            ]);

            $sales_agent = SalesAgent::forBusiness($business_id)->findOrFail($id);

            $input = $this->buildSalesAgentInput($request, $business_id);

            DB::beginTransaction();

            $sales_agent->update($input);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' ' . $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified sales agent.
     *
     * @param  int  $id
     * @return array
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $sales_agent = SalesAgent::forBusiness($business_id)->findOrFail($id);

            DB::beginTransaction();

            $sales_agent->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Store a commission entry for a sales agent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function storeCommission(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            DB::beginTransaction();

            $sales_agent = SalesAgent::forBusiness($business_id)->findOrFail($request->sales_agent_id);

            $amount = ! empty($request->amount) ? $this->cleanDecimalValue($request->amount) : 0;
            $history = $sales_agent->commission_history ?? [];
            $history[] = [
                'commission_date' => now()->toDateTimeString(),
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'commission_for' => $request->commission_for,
                'ref_bill_no' => $request->ref_bill_no,
                'amount' => $amount,
                'created_by' => Auth::id(),
            ];

            $sales_agent->commission_history = $history;
            $sales_agent->commission = (float) $sales_agent->commission + (float) $amount;
            $sales_agent->save();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the ledger for a sales agent.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ledger($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $sales_agent = SalesAgent::forBusiness($business_id)
            ->findOrFail($id);

        return view('salesagent::sales_agent_management.ledger')
            ->with(compact('sales_agent'));
    }

    /**
     * Build the SalesAgent input payload while remaining safe on older DBs.
     *
     * @param Request $request
     * @param int $business_id
     * @return array
     */
    protected function buildSalesAgentInput(Request $request, $business_id)
    {
        $commission_entitled = $request->input('commission_entitled', 'no') === 'yes' ? 'yes' : 'no';
        $commission_type = $request->input('commission_type', 'percentage') === 'fixed' ? 'fixed' : 'percentage';

        $input = [
            'name' => $request->input('name'),
            'joined_date' => $request->input('joined_date'),
            'employment_grade' => $request->input('employment_grade'),
            'salary' => ! empty($request->input('salary')) ? $this->cleanDecimalValue($request->input('salary')) : 0,
            'commission' => $commission_entitled === 'yes' && ! empty($request->input('commission'))
                ? $this->cleanDecimalValue($request->input('commission'))
                : 0,
            'location_id' => $request->input('location_id') ?: $this->getFirstLocationId($business_id),
            'user_id' => $request->input('user_id'),
        ];

        if (Schema::hasColumn('distribution_sales_agents', 'commission_entitled')) {
            $input['commission_entitled'] = $commission_entitled;
        }

        if (Schema::hasColumn('distribution_sales_agents', 'commission_type')) {
            $input['commission_type'] = $commission_entitled === 'yes' ? $commission_type : null;
        }

        return $input;
    }

    /**
     * Clean decimal input and remove accidental percent signs.
     *
     * @param string|float|int|null $value
     * @return float
     */
    protected function cleanDecimalValue($value)
    {
        $value = str_replace('%', '', (string) $value);
        $amount = ! empty($value) ? $this->moduleUtil->num_uf($value) : 0;

        return round((float) $amount, $this->getCurrencyPrecision());
    }

    /**
     * Get configured business currency precision.
     *
     * @return int
     */
    protected function getCurrencyPrecision()
    {
        $precision = request()->session()->get('business.currency_precision');

        if ($precision === null) {
            $precision = request()->session()->get('currency_precision', 2);
        }

        return (int) $precision;
    }

    /**
     * Get first dropdown key.
     *
     * @param mixed $dropdown
     * @return mixed|null
     */
    protected function getFirstDropdownKey($dropdown)
    {
        if (empty($dropdown)) {
            return null;
        }

        if ($dropdown instanceof \Illuminate\Support\Collection) {
            return $dropdown->keys()->first();
        }

        if (is_array($dropdown)) {
            $keys = array_keys($dropdown);

            return $keys[0] ?? null;
        }

        return null;
    }

    /**
     * Get first location ID for a business.
     *
     * @param int $business_id
     * @return int|null
     */
    protected function getFirstLocationId($business_id)
    {
        return BusinessLocation::where('business_id', $business_id)
            ->orderBy('id', 'asc')
            ->value('id');
    }
}
