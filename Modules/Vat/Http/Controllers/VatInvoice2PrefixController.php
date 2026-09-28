<?php

namespace Modules\Vat\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Modules\Vat\Entities\VatInvoice2Prefix;
use Yajra\DataTables\Facades\DataTables;

class VatInvoice2PrefixController extends Controller
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
        // Kept for backwards compatibility with older VAT routes.
    }

    public function index()
    {
        $businessId = $this->businessId();

        if (request()->ajax()) {
            $query = VatInvoice2Prefix::leftJoin(
                    'users',
                    'vat_invoice2_prefixes.created_by',
                    '=',
                    'users.id'
                )
                ->where('vat_invoice2_prefixes.business_id', $businessId)
                ->select([
                    'vat_invoice2_prefixes.*',
                    'users.username as user_created',
                ]);

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $editUrl = action([self::class, 'edit'], [$row->id]);
                    $deleteUrl = action([self::class, 'destroy'], [$row->id]);

                    // S-667: locked while the prefix has invoices behind it.
                    $locked = $this->hasRelatedInvoices(
                        (int) $row->business_id,
                        (int) $row->id,
                        $row->prefix ?? null
                    );
                    $lockedMessage = $this->lockedMessage();

                    return '<div class="btn-group vat-prefix-action-dropdown">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" '
                        . 'data-toggle="dropdown" aria-expanded="false">'
                        . e(__('messages.actions'))
                        . ' <span class="caret"></span>'
                        . '<span class="sr-only">Toggle Dropdown</span>'
                        . '</button>'
                        . '<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                        . ($locked
                            ? '<li class="disabled">'
                                . '<a href="#" onclick="return false;" aria-disabled="true" tabindex="-1" '
                                . 'title="' . e($lockedMessage) . '">'
                                . '<i class="glyphicon glyphicon-edit"></i> '
                                . e(__('messages.edit'))
                                . '</a></li>'
                                . '<li class="disabled">'
                                . '<a href="#" onclick="return false;" aria-disabled="true" tabindex="-1" '
                                . 'title="' . e($lockedMessage) . '">'
                                . '<i class="fa fa-trash"></i> '
                                . e(__('messages.delete'))
                                . '</a></li>'
                            : '<li><a href="' . e($editUrl) . '" '
                                . 'data-href="' . e($editUrl) . '" '
                                . 'class="vat-invoice2-prefix-modal-trigger vat-prefix-edit">'
                                . '<i class="glyphicon glyphicon-edit"></i> '
                                . e(__('messages.edit'))
                                . '</a></li>'
                                . '<li><a href="' . e($deleteUrl) . '" '
                                . 'data-href="' . e($deleteUrl) . '" '
                                . 'class="vat-prefix-delete">'
                                . '<i class="fa fa-trash"></i> '
                                . e(__('messages.delete'))
                                . '</a></li>')
                        . '</ul>'
                        . '</div>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('vat::vat_invoice2.prefixes');
    }


    /**
     * Does this prefix already have invoices behind it?
     *
     * S-667: Edit and Delete must be disabled while a prefix is in use, and
     * enabled again once the related invoices are deleted. Renumbering or
     * removing a prefix that documents already carry would leave those documents
     * referring to something that no longer exists.
     *
     * Two ways of asking, because vat_invoices_2 supports both:
     *
     *   1. the `prefix` column stores the prefix ID the invoice was created
     *      with. That is exact, and it is the reliable answer.
     *   2. customer_bill_no is matched against the prefix text as well, so an
     *      invoice written before that column was populated still counts. Without
     *      it an older prefix could look unused and be deleted out from under its
     *      own invoices.
     *
     * The LIKE escaping is deliberate and matches hasRelatedStatements() in
     * VatStatementPrefixController: a prefix containing % or _ would otherwise
     * act as a SQL wildcard and match invoices belonging to other prefixes.
     *
     * Schema is checked first. These tables come from migrations and tenant
     * databases here are not always fully migrated; a missing table must leave
     * the buttons usable rather than break the page.
     */
    private function hasRelatedInvoices(int $businessId, int $prefixId, ?string $prefix): bool
    {
        if (! Schema::hasTable('vat_invoices_2')) {
            return false;
        }

        $needle = trim((string) $prefix);

        $query = DB::table('vat_invoices_2')->where('business_id', $businessId);

        $matched = false;

        $query->where(function ($inner) use ($prefixId, $needle, &$matched) {
            if ($prefixId > 0 && Schema::hasColumn('vat_invoices_2', 'prefix')) {
                $inner->orWhere('prefix', $prefixId);
                $matched = true;
            }

            if ($needle !== '' && Schema::hasColumn('vat_invoices_2', 'customer_bill_no')) {
                $pattern = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $needle) . '%';
                $inner->orWhereRaw("customer_bill_no LIKE ? ESCAPE '='", [$pattern]);
                $matched = true;
            }
        });

        // Nothing to match on means nothing can be proven in use; do not lock.
        if (! $matched) {
            return false;
        }

        return $query->exists();
    }

    private function lockedMessage(): string
    {
        return 'Edit and Delete are disabled because this VAT Invoice2 prefix has related invoices. Delete the related records from List VAT Invoice2 first.';
    }

    public function create()
    {
        $businessId = $this->businessId();

        return view('vat::vat_invoice2_prefixes.create')
            ->with('business_id', $businessId);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        try {
            $prefix = DB::transaction(function () use ($request, $validated) {
                return VatInvoice2Prefix::create(
                    $this->prefixData($request, $validated)
                );
            });

            $output = [
                'success' => true,
                'msg' => __('messages.success'),
                'data' => $this->responseData($prefix),
            ];
        } catch (\Throwable $exception) {
            Log::emergency(
                'File: ' . $exception->getFile()
                . ' Line: ' . $exception->getLine()
                . ' Message: ' . $exception->getMessage()
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return Redirect::back()->with('status', $output);
    }

    public function show($id)
    {
        // Not used.
    }

    public function edit($id)
    {
        $data = VatInvoice2Prefix::where('business_id', $this->businessId())
            ->findOrFail($id);

        return view('vat::vat_invoice2_prefixes.edit')
            ->with(compact('data'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules());
        $businessId = $this->businessId();

        try {
            $prefix = DB::transaction(function () use ($request, $validated, $businessId, $id) {
                $prefix = VatInvoice2Prefix::where('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                /*
                 * S-667: refuse inside the locked transaction, not only in the UI.
                 *
                 * The disabled menu entry is a courtesy; this is the rule. A stale
                 * page, a bookmarked URL or a direct POST would otherwise renumber
                 * a prefix that invoices already carry. Checked after
                 * lockForUpdate() so an invoice cannot be created against this
                 * prefix between the check and the save.
                 */
                if ($this->hasRelatedInvoices($businessId, (int) $prefix->id, $prefix->prefix)) {
                    throw new \RuntimeException($this->lockedMessage());
                }

                $prefix->fill($this->prefixData($request, $validated));
                $prefix->save();

                return $prefix->fresh();
            });

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
                'data' => $this->responseData($prefix),
            ];
        } catch (\RuntimeException $exception) {
            /*
             * S-667: the lock message must reach the operator.
             *
             * The generic handler below reports "something went wrong", which
             * would leave them with no idea the prefix is in use or what to do
             * about it. Caught first, and deliberately not logged as an emergency
             * - a refused edit is the rule working, not a failure.
             */
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            Log::emergency(
                'File: ' . $exception->getFile()
                . ' Line: ' . $exception->getLine()
                . ' Message: ' . $exception->getMessage()
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return Redirect::back()->with('status', $output);
    }

    public function destroy($id)
    {
        $businessId = $this->businessId();

        try {
            /*
             * S-667: refuse to delete a prefix that invoices still use.
             *
             * Deleting it would leave those invoices numbered with a prefix that
             * no longer exists, and nothing would report it - the invoices would
             * simply stop matching their own prefix on every list and report that
             * filters by it.
             *
             * The message names what to do, because "something went wrong" would
             * leave the operator with no way forward. Once the related invoices
             * are removed from List VAT Invoice2 this passes and the prefix can be
             * deleted, which is the behaviour asked for.
             */
            $prefix = VatInvoice2Prefix::where('business_id', $businessId)
                ->where('id', $id)
                ->first();

            if (empty($prefix)) {
                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            if ($this->hasRelatedInvoices($businessId, (int) $prefix->id, $prefix->prefix)) {
                return [
                    'success' => false,
                    'msg' => $this->lockedMessage(),
                ];
            }

            $prefix->delete();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $exception) {
            Log::emergency(
                'File: ' . $exception->getFile()
                . ' Line: ' . $exception->getLine()
                . ' Message: ' . $exception->getMessage()
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if (request()->ajax()) {
            return response()->json($output);
        }

        return Redirect::back()->with('status', $output);
    }

    private function businessId()
    {
        return request()->session()->get('user.business_id')
            ?? request()->session()->get('business.id')
            ?? optional(auth()->user())->business_id;
    }

    private function rules(): array
    {
        return [
            'prefix' => 'nullable|string|max:191',
            'starting_no' => ['required', 'regex:/^\d+$/'],
            'unit_vat_no_of_decimals' => 'nullable|integer|min:0|max:10',
            'unit_vat_rounding_off_required' => 'nullable|in:1,on,true',
            'sub_total_no_of_decimals' => 'nullable|integer|min:0|max:10',
            'sub_total_rounding_off_required' => 'nullable|in:1,on,true',
        ];
    }

    private function prefixData(Request $request, array $validated): array
    {
        $data = [
            'prefix' => $validated['prefix'] ?? null,
            'starting_no' => (string) $validated['starting_no'],
            'created_by' => auth()->id(),
            'business_id' => $this->businessId(),
        ];

        if (Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_no_of_decimals')) {
            $data['unit_vat_no_of_decimals'] = array_key_exists('unit_vat_no_of_decimals', $validated)
                && $validated['unit_vat_no_of_decimals'] !== null
                && $validated['unit_vat_no_of_decimals'] !== ''
                    ? (int) $validated['unit_vat_no_of_decimals']
                    : 2;
        }

        if (Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_rounding_off_required')) {
            $data['unit_vat_rounding_off_required'] = $this->checked(
                $request,
                'unit_vat_rounding_off_required'
            );
        }

        if (Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_no_of_decimals')) {
            $data['sub_total_no_of_decimals'] = array_key_exists('sub_total_no_of_decimals', $validated)
                && $validated['sub_total_no_of_decimals'] !== null
                && $validated['sub_total_no_of_decimals'] !== ''
                    ? (int) $validated['sub_total_no_of_decimals']
                    : 2;
        }

        if (Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_rounding_off_required')) {
            $data['sub_total_rounding_off_required'] = $this->checked(
                $request,
                'sub_total_rounding_off_required'
            );
        }

        return $data;
    }

    private function checked(Request $request, string $field): bool
    {
        if (!$request->has($field)) {
            return false;
        }

        return in_array(
            strtolower((string) $request->input($field)),
            ['1', 'true', 'on', 'yes'],
            true
        );
    }

    private function responseData(VatInvoice2Prefix $prefix): array
    {
        return [
            'id' => $prefix->id,
            'unit_vat_no_of_decimals' => isset($prefix->unit_vat_no_of_decimals)
                ? (int) $prefix->unit_vat_no_of_decimals
                : 2,
            'unit_vat_rounding_off_required' => isset($prefix->unit_vat_rounding_off_required)
                ? (bool) $prefix->unit_vat_rounding_off_required
                : false,
            'sub_total_no_of_decimals' => isset($prefix->sub_total_no_of_decimals)
                ? (int) $prefix->sub_total_no_of_decimals
                : 2,
            'sub_total_rounding_off_required' => isset($prefix->sub_total_rounding_off_required)
                ? (bool) $prefix->sub_total_rounding_off_required
                : false,
        ];
    }
}
