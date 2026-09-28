<?php

namespace Modules\Vat\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatStatementPrefix;
use Yajra\DataTables\Facades\DataTables;

class VatStatementPrefixController extends Controller
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

    public function index(Request $request)
    {
        $businessId = $this->businessId($request);

        if (!$request->ajax()) {
            return response()->noContent();
        }

        $query = VatStatementPrefix::query()
            ->leftJoin('users', 'vat_statement_prefixes.created_by', '=', 'users.id')
            ->where('vat_statement_prefixes.business_id', $businessId)
            ->select([
                'vat_statement_prefixes.*',
                'users.username as user_created',
            ]);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $locked = $this->hasRelatedStatements(
                    (int) $row->business_id,
                    (string) ($row->prefix ?? '')
                );
                $editUrl = action([self::class, 'edit'], [$row->id]);
                $deleteUrl = action([self::class, 'destroy'], [$row->id]);
                $lockedMessage = $this->lockedMessage();

                $html = '<div class="btn-group vat-action-menu-group vat-statement-prefix-action-group">'
                    . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                    . e(__('messages.actions'))
                    . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>'
                    . '</button>'
                    . '<ul class="dropdown-menu dropdown-menu-right" role="menu">';

                if ($locked) {
                    $html .= '<li class="disabled">'
                        . '<a href="#" onclick="return false;" aria-disabled="true" tabindex="-1" title="' . e($lockedMessage) . '">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit'))
                        . '</a></li>';
                    $html .= '<li class="disabled">'
                        . '<a href="#" onclick="return false;" aria-disabled="true" tabindex="-1" title="' . e($lockedMessage) . '">'
                        . '<i class="fa fa-trash"></i> ' . e(__('messages.delete'))
                        . '</a></li>';
                } else {
                    $html .= '<li><a href="#" data-href="' . e($editUrl) . '" class="vat-ajax-modal-trigger" data-container=".fuel_tank_modal">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit'))
                        . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . e($deleteUrl) . '" class="delete_task">'
                        . '<i class="fa fa-trash"></i> ' . e(__('messages.delete'))
                        . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->removeColumn('id')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $businessId = $this->businessId($request);

        return view('vat::vat_statement_prefixes.create')
            ->with('business_id', $businessId);
    }

    public function store(Request $request)
    {
        $businessId = $this->businessId($request);

        // Restore the exact prefix after Laravel's TrimStrings middleware.
        $request->merge(['prefix' => $this->getExactPrefixInput($request)]);

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:191',
            'starting_no' => ['required', 'regex:/^\d+$/'],
        ]);

        try {
            DB::transaction(function () use ($validated, $businessId) {
                VatStatementPrefix::create([
                    'prefix' => $validated['prefix'] ?? null,
                    'starting_no' => $validated['starting_no'],
                    'created_by' => auth()->id(),
                    'business_id' => $businessId,
                ]);
            });

            $output = [
                'success' => true,
                'msg' => __('messages.success'),
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

        return $this->respond($request, $output);
    }

    public function show($id)
    {
        return response()->noContent();
    }

    public function edit(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        $data = VatStatementPrefix::where('business_id', $businessId)
            ->findOrFail($id);

        if ($this->hasRelatedStatements(
            (int) $data->business_id,
            (string) ($data->prefix ?? '')
        )) {
            return response($this->lockedMessage(), 409);
        }

        return view('vat::vat_statement_prefixes.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        $request->merge(['prefix' => $this->getExactPrefixInput($request)]);

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:191',
            'starting_no' => ['required', 'regex:/^\d+$/'],
        ]);

        try {
            $prefix = VatStatementPrefix::where('business_id', $businessId)
                ->findOrFail($id);

            if ($this->hasRelatedStatements(
                (int) $prefix->business_id,
                (string) ($prefix->prefix ?? '')
            )) {
                return $this->lockedResponse($request);
            }

            $prefix->update([
                'prefix' => $validated['prefix'] ?? null,
                'starting_no' => $validated['starting_no'],
                'created_by' => auth()->id(),
                'business_id' => $businessId,
            ]);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
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

        return $this->respond($request, $output);
    }

    public function destroy(Request $request, $id)
    {
        $businessId = $this->businessId($request);

        try {
            $prefix = VatStatementPrefix::where('business_id', $businessId)
                ->findOrFail($id);

            if ($this->hasRelatedStatements(
                (int) $prefix->business_id,
                (string) ($prefix->prefix ?? '')
            )) {
                return response()->json([
                    'success' => false,
                    'msg' => $this->lockedMessage(),
                ], 409);
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

        return response()->json($output);
    }

    /**
     * A VAT Statement prefix is locked while at least one statement number uses
     * that exact prefix followed by a numeric sequence. A single legacy hyphen
     * between the configured prefix and sequence is also recognised.
     */
    private function hasRelatedStatements(int $businessId, string $prefix): bool
    {
        $query = VatCustomerStatement::where('business_id', $businessId);

        if ($prefix !== '') {
            $pattern = str_replace(
                ['=', '%', '_'],
                ['==', '=%', '=_'],
                $prefix
            ) . '%';

            $query->whereRaw("statement_no LIKE ? ESCAPE '='", [$pattern]);
        }

        $sequenceStart = (function_exists('mb_strlen') ? mb_strlen($prefix) : strlen($prefix)) + 1;

        return $query
            ->whereRaw(
                "SUBSTRING(CAST(statement_no AS CHAR), ?) REGEXP '^-?[0-9]+$'",
                [$sequenceStart]
            )
            ->exists();
    }

    private function lockedMessage(): string
    {
        return 'Edit and Delete are disabled because this VAT Statement prefix has related statements. Delete the related records from List VAT Statements first.';
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

    /**
     * Return the prefix exactly as typed, including an intentional trailing space.
     */
    private function getExactPrefixInput(Request $request): ?string
    {
        $rawBody = (string) $request->getContent();

        if ($rawBody !== '') {
            $rawInput = [];
            parse_str($rawBody, $rawInput);

            if (array_key_exists('prefix', $rawInput)) {
                $prefix = $rawInput['prefix'];

                return is_array($prefix) ? null : (string) $prefix;
            }
        }

        $prefix = $request->input('prefix');

        return is_array($prefix) || $prefix === null ? null : (string) $prefix;
    }
}
