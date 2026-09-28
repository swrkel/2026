<?php

namespace Modules\Vat\Http\Controllers;

use App\BusinessLocation;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatInvoice2Prefix;
use Modules\Vat\Entities\VatPrefix;
use Modules\Vat\Entities\VatUserInvoicePrefix;
use Yajra\DataTables\Facades\DataTables;

class VatUserInvoicePrefixController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    public function __construct(
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function getTankProduct()
    {
        // Kept for backward route compatibility.
    }

    private function vatDropdownData(int $businessId): array
    {
        $businessLocations = BusinessLocation::forDropdown($businessId, false);

        $prefixes = VatPrefix::where('business_id', $businessId)
            ->orderBy('prefix')
            ->pluck('prefix', 'id');

        $prefixes2 = VatInvoice2Prefix::where('business_id', $businessId)
            ->orderBy('prefix')
            ->pluck('prefix', 'id');

        $users = User::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('is_cmmsn_agnt', 0)->orWhereNull('is_cmmsn_agnt');
            })
            ->where(function ($query) {
                $query->where('is_customer', 0)->orWhereNull('is_customer');
            })
            ->select(
                'id',
                DB::raw("TRIM(CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), ' ', COALESCE(username, ''))) as full_name")
            )
            ->orderBy('first_name')
            ->pluck('full_name', 'id')
            ->map(function ($name, $id) {
                return trim($name) !== '' ? trim($name) : ('User #' . $id);
            });

        return [
            'business_locations' => $businessLocations,
            'prefixes' => $prefixes,
            'prefixes2' => $prefixes2,
            'users' => $users,
        ];
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId($request);

        if (!$request->ajax()) {
            return response()->noContent();
        }

        $query = VatUserInvoicePrefix::query()
            ->leftJoin('users as uc', 'vat_user_invoice_prefixes.created_by', '=', 'uc.id')
            ->leftJoin('users', 'vat_user_invoice_prefixes.user_id', '=', 'users.id')
            ->leftJoin('vat_prefixes as vp', 'vat_user_invoice_prefixes.prefix_id', '=', 'vp.id')
            ->leftJoin('vat_invoice2_prefixes as vp2', 'vat_user_invoice_prefixes.prefix_id2', '=', 'vp2.id')
            ->leftJoin('business_locations as bl', 'vat_user_invoice_prefixes.location_id', '=', 'bl.id')
            ->where('vat_user_invoice_prefixes.business_id', $businessId)
            ->select([
                'vat_user_invoice_prefixes.*',
                'vp.prefix as prefix_name',
                'vp2.prefix as prefix_name2',
                'uc.username as user_created',
                'users.username as username',
                'bl.name as location_name',
            ]);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $locked = $this->hasRelatedInvoice2TransactionsByPrefix(
                    (int) $row->business_id,
                    (int) $row->prefix_id2,
                    (string) ($row->prefix_name2 ?? '')
                );
                $editUrl = action([self::class, 'edit'], [$row->id]);
                $deleteUrl = action([self::class, 'destroy'], [$row->id]);
                $lockedMessage = $this->lockedMessage();

                $html = '<div class="btn-group vat-action-menu-group vat-user-prefix-action-group vat-prefix-action-group">'
                    . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                    . e(__('messages.actions'))
                    . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>'
                    . '</button>'
                    // S664: vat-prefix-action-menu is the hook the shared script
                    // uses to lift this menu out to <body>. The locking logic
                    // below was already correct - the entries simply could not be
                    // SEEN, because the menu was clipped by the table wrapper.
                    . '<ul class="dropdown-menu dropdown-menu-right vat-prefix-action-menu" role="menu">';

                if ($locked) {
                    $html .= '<li class="disabled">'
                        . '<a href="#" onclick="return false;" aria-disabled="true" title="' . e($lockedMessage) . '">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit'))
                        . '</a></li>';
                    $html .= '<li class="disabled">'
                        . '<a href="#" onclick="return false;" aria-disabled="true" title="' . e($lockedMessage) . '">'
                        . '<i class="fa fa-trash"></i> ' . e(__('messages.delete'))
                        . '</a></li>';
                } else {
                    $html .= '<li><a href="#" data-href="' . e($editUrl) . '" class="vat-settings-ajax-modal-trigger" data-container=".fuel_tank_modal">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit'))
                        . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . e($deleteUrl) . '" class="delete_task">'
                        . '<i class="fa fa-trash"></i> ' . e(__('messages.delete'))
                        . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->editColumn('date_time', '{{@format_datetime($date_time)}}')
            ->removeColumn('id')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $businessId = $this->businessId($request);
        $dropdowns = $this->vatDropdownData($businessId);

        return view('vat::vat_userinvoice_prefixes.create', $dropdowns)
            ->with('business_id', $businessId);
    }

    public function store(Request $request)
    {
        $businessId = $this->businessId($request);
        $validated = $this->validateAssignment($request, $businessId);

        try {
            DB::transaction(function () use ($validated, $businessId) {
                VatUserInvoicePrefix::create([
                    'date_time' => Carbon::parse($validated['date_time'])->format('Y-m-d H:i:s'),
                    'location_id' => $validated['location_id'],
                    'user_id' => $validated['user_id'],
                    'prefix_id' => $validated['prefix_id'],
                    'prefix_id2' => $validated['prefix_id2'],
                    'created_by' => auth()->id(),
                    'business_id' => $businessId,
                ]);
            });

            $output = [
                'success' => true,
                'msg' => __('messages.success'),
            ];
        } catch (\Throwable $exception) {
            $this->logException($exception);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $this->respond($request, $output);
    }

    public function show($id)
    {
        return response()->noContent();
    }

    public function edit(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        $data = VatUserInvoicePrefix::where('business_id', $businessId)
            ->findOrFail($id);

        if ($this->hasRelatedInvoice2Transactions($data)) {
            return response($this->lockedMessage(), 409);
        }

        $dropdowns = $this->vatDropdownData($businessId);
        $dropdowns['data'] = $data;

        return view('vat::vat_userinvoice_prefixes.edit', $dropdowns);
    }

    public function update(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        $assignment = VatUserInvoicePrefix::where('business_id', $businessId)
            ->findOrFail($id);

        if ($this->hasRelatedInvoice2Transactions($assignment)) {
            return $this->lockedResponse($request);
        }

        $validated = $this->validateAssignment($request, $businessId);

        try {
            $assignment->update([
                'date_time' => Carbon::parse($validated['date_time'])->format('Y-m-d H:i:s'),
                'location_id' => $validated['location_id'],
                'user_id' => $validated['user_id'],
                'prefix_id' => $validated['prefix_id'],
                'prefix_id2' => $validated['prefix_id2'],
                'created_by' => auth()->id(),
                'business_id' => $businessId,
            ]);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Throwable $exception) {
            $this->logException($exception);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $this->respond($request, $output);
    }

    public function destroy(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        $assignment = VatUserInvoicePrefix::where('business_id', $businessId)
            ->findOrFail($id);

        if ($this->hasRelatedInvoice2Transactions($assignment)) {
            return response()->json([
                'success' => false,
                'msg' => $this->lockedMessage(),
            ], 409);
        }

        try {
            $assignment->delete();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $exception) {
            $this->logException($exception);
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    private function validateAssignment(Request $request, int $businessId): array
    {
        return $request->validate([
            'date_time' => ['required', 'date'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
            'prefix_id' => [
                'required',
                'integer',
                Rule::exists('vat_prefixes', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
            'prefix_id2' => [
                'required',
                'integer',
                Rule::exists('vat_invoice2_prefixes', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
        ]);
    }

    private function hasRelatedInvoice2Transactions(VatUserInvoicePrefix $assignment): bool
    {
        if (empty($assignment->prefix_id2)) {
            return false;
        }

        $prefixValue = (string) VatInvoice2Prefix::where('business_id', $assignment->business_id)
            ->whereKey($assignment->prefix_id2)
            ->value('prefix');

        return $this->hasRelatedInvoice2TransactionsByPrefix(
            (int) $assignment->business_id,
            (int) $assignment->prefix_id2,
            $prefixValue
        );
    }

    /**
     * Current records store the VAT Invoice2 prefix ID. A few legacy databases
     * stored the literal prefix, so both representations are checked.
     */
    private function hasRelatedInvoice2TransactionsByPrefix(
        int $businessId,
        int $prefixId,
        string $prefixValue = ''
    ): bool {
        if ($businessId <= 0 || $prefixId <= 0) {
            return false;
        }

        return VatInvoice2::where('business_id', $businessId)
            ->where(function ($query) use ($prefixId, $prefixValue) {
                $query->where('prefix', $prefixId);

                if ($prefixValue !== '' && $prefixValue !== (string) $prefixId) {
                    $query->orWhere('prefix', $prefixValue);
                }
            })
            ->exists();
    }

    private function lockedMessage(): string
    {
        return 'Edit and Delete are disabled because this VAT Invoice 2 prefix has related transactions. Delete the related records from List VAT Invoice2 first.';
    }

    private function lockedResponse(Request $request)
    {
        $output = [
            'success' => false,
            'msg' => $this->lockedMessage(),
        ];

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($output, 409);
        }

        return redirect()->back()->with('status', $output);
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
        );

        abort_if($businessId <= 0, 403, 'Business context is unavailable.');

        return $businessId;
    }

    private function respond(Request $request, array $output)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    private function logException(\Throwable $exception): void
    {
        Log::emergency(
            'File: ' . $exception->getFile()
            . ' Line: ' . $exception->getLine()
            . ' Message: ' . $exception->getMessage()
        );
    }
}
