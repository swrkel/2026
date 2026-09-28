<?php

namespace Modules\CustomerStatements\Services;

use App\CustomerStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CustomerStatementPersistenceService
{
    private CustomerStatementNumberingService $numbering;

    public function __construct(CustomerStatementNumberingService $numbering)
    {
        $this->numbering = $numbering;
    }

    /**
     * Save through the existing Customers statement engine, then persist the two
     * values that the shared action currently leaves incomplete: the selected
     * location and the configured Customer-wise/General statement number.
     */
    public function store(Request $request): array
    {
        $businessId = $this->numbering->businessId($request);
        $customerId = (int) $request->input('customer_id');

        if ($customerId < 1) {
            throw new RuntimeException('Please select a customer.');
        }

        $nextNumber = $this->numbering->nextNumber($businessId, $customerId)['statement_no'];
        $beforeId = (int) CustomerStatement::query()
            ->where('business_id', $businessId)
            ->max('id');

        $controllerClass = \Modules\Customers\Http\Controllers\CustomerStandaloneStatementController::class;
        if (! class_exists($controllerClass)) {
            throw new RuntimeException('The Customers statement controller is not available.');
        }

        // Keep the preview and the server-side save on the same authoritative number.
        $request->merge(['statement_no' => $nextNumber]);
        $result = app($controllerClass)->store($request);
        $payload = $this->normalisePayload($result);

        if ((int) ($payload['success'] ?? 0) !== 1) {
            return $payload;
        }

        $statement = CustomerStatement::query()
            ->where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->where('id', '>', $beforeId)
            ->orderByDesc('id')
            ->first();

        if (! $statement) {
            throw new RuntimeException('The Customer Statement was not found after saving.');
        }

        $locationId = $this->resolveLocationId(
            $businessId,
            (int) $statement->id,
            $request->filled('location_id') ? (int) $request->input('location_id') : null
        );

        $statement->statement_no = (string) $nextNumber;
        if ($locationId !== null) {
            $statement->location_id = $locationId;
        }
        $statement->save();

        $payload['statement_id'] = (int) $statement->id;
        $payload['statement_no'] = (string) $statement->statement_no;
        $payload['location_id'] = $statement->location_id !== null
            ? (int) $statement->location_id
            : null;

        return $payload;
    }

    /**
     * Return the existing bill rows while repairing the blank Date column exposed
     * by the shared Customers DataTable formatter. The authoritative bill date is
     * transactions.transaction_date and is resolved by transaction_id inside the
     * current business only.
     */
    public function billList(Request $request): Response
    {
        $businessId = $this->numbering->businessId($request);
        $controllerClass = \Modules\Customers\Http\Controllers\CustomerStandaloneStatementController::class;

        if (! class_exists($controllerClass)) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'The Customers statement controller is not available.',
            ], 422);
        }

        $payload = $this->normalisePayload(app($controllerClass)->index($request));
        $rows = isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : [];

        $transactionIds = collect($rows)
            ->pluck('transaction_id')
            ->filter(static function ($value): bool {
                return is_numeric($value) && (int) $value > 0;
            })
            ->map(static function ($value): int {
                return (int) $value;
            })
            ->unique()
            ->values();

        $transactionDates = $transactionIds->isEmpty()
            ? collect()
            : DB::table('transactions')
                ->where('business_id', $businessId)
                ->whereIn('id', $transactionIds->all())
                ->pluck('transaction_date', 'id');

        $util = app(\App\Utils\Util::class);

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $displayDate = trim(strip_tags((string) ($row['transaction_date'] ?? '')));
            if ($displayDate !== '' && $displayDate !== '&nbsp;') {
                continue;
            }

            $transactionId = isset($row['transaction_id']) && is_numeric($row['transaction_id'])
                ? (int) $row['transaction_id']
                : 0;
            $rawDate = $transactionId > 0 ? $transactionDates->get($transactionId) : null;

            if ($rawDate !== null && $rawDate !== '') {
                $rows[$index]['transaction_date'] = $util->format_date($rawDate);
            }
        }

        $payload['data'] = $rows;

        return response()->json($payload);
    }

    /**
     * Return the existing Customers DataTables response after repairing only old
     * statement rows whose location was never persisted by the shared save action.
     */
    public function statementList(Request $request): Response
    {
        $businessId = $this->numbering->businessId($request);
        $this->repairMissingLocations($businessId);

        $controllerClass = \Modules\Customers\Http\Controllers\CustomerStandaloneStatementController::class;
        if (! class_exists($controllerClass)) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'The Customers statement controller is not available.',
            ], 422);
        }

        return app($controllerClass)->getCustomerStatementList($request);
    }

    /**
     * DataTables-compatible rows for the numbering settings list. This endpoint
     * intentionally uses the same business-id resolver as save/show, avoiding the
     * user.business_id versus business.id session mismatch in the legacy action.
     */
    public function numberingSettingsRows(Request $request): array
    {
        $businessId = $this->numbering->businessId($request);
        $settings = $this->numbering->settings($businessId);
        $rows = [];

        $configSetting = DB::table('customer_statement_settings')
            ->where('business_id', $businessId)
            ->where('customer_id', 0)
            ->orderByDesc('id')
            ->first();

        $configAction = '';
        if ($settings['mode'] === 'general' && $configSetting) {
            $usage = $this->numbering->settingUsage($businessId, (int) $configSetting->id);
            $configAction = $this->numberingActionHtml(
                (int) $configSetting->id,
                0,
                max(1, (int) $settings['starting_no']),
                $usage,
                false
            );
        }

        $rows[] = [
            'customer_id' => 0,
            'customer_name' => $settings['mode'] === 'customer'
                ? 'Numbering Mode: Customer-wise'
                : 'Numbering Mode: General',
            'starting_no' => $settings['mode'] === 'general'
                ? (int) $settings['starting_no']
                : '—',
            'enable_separate_customer_statement_no' => $settings['mode'] === 'customer' ? 1 : 0,
            'action' => $configAction,
        ];

        if ($settings['mode'] === 'customer') {
            // Read only the newest row per customer. Older versions inserted a
            // duplicate on every save; the newest row is authoritative.
            $latestRows = DB::table('customer_statement_settings')
                ->where('business_id', $businessId)
                ->where('customer_id', '>', 0)
                ->select('customer_id', DB::raw('MAX(id) as latest_id'))
                ->groupBy('customer_id');

            $customerRows = DB::table('customer_statement_settings as css')
                ->joinSub($latestRows, 'latest_css', function ($join): void {
                    $join->on('latest_css.latest_id', '=', 'css.id');
                })
                ->join('contacts as c', function ($join): void {
                    $join->on('c.id', '=', 'css.customer_id')
                        ->on('c.business_id', '=', 'css.business_id');
                })
                ->where('css.business_id', $businessId)
                ->whereIn('c.type', ['customer', 'both'])
                ->select(
                    'css.id',
                    'css.customer_id',
                    'css.starting_no',
                    'css.enable_separate_customer_statement_no',
                    'c.name as customer_name'
                )
                ->orderBy('c.name')
                ->get();

            foreach ($customerRows as $row) {
                $settingId = (int) $row->id;
                $customerId = (int) $row->customer_id;
                $startingNo = max(1, (int) $row->starting_no);
                $usage = $this->numbering->settingUsage($businessId, $settingId);

                $rows[] = [
                    'customer_id' => $customerId,
                    'customer_name' => (string) $row->customer_name,
                    'starting_no' => $startingNo,
                    'enable_separate_customer_statement_no' => 1,
                    'action' => $this->numberingActionHtml(
                        $settingId,
                        $customerId,
                        $startingNo,
                        $usage,
                        true
                    ),
                ];
            }
        }

        return ['data' => $rows];
    }

    private function numberingActionHtml(
        int $settingId,
        int $customerId,
        int $startingNo,
        array $usage,
        bool $showEdit
    ): string {
        $buttons = [];

        if ($showEdit) {
            $buttons[] = '<button type="button" class="btn btn-primary btn-xs cs-numbering-edit"'
                . ' data-customer-id="' . $customerId . '"'
                . ' data-starting-no="' . $startingNo . '">'
                . '<i class="fa fa-pencil-square-o"></i> Edit</button>';
        }

        if (! empty($usage['is_used'])) {
            $count = (int) ($usage['transaction_count'] ?? 0);
            $title = 'Delete disabled: ' . $count
                . ' Customer Statement transaction(s) use this numbering code.';

            $buttons[] = '<button type="button" class="btn btn-danger btn-xs cs-numbering-delete"'
                . ' disabled="disabled" aria-disabled="true"'
                . ' title="' . e($title) . '">'
                . '<i class="fa fa-trash"></i> Delete</button>';
        } else {
            $buttons[] = '<button type="button" class="btn btn-danger btn-xs cs-numbering-delete"'
                . ' data-delete-url="'
                . e(route('customerstatements.numbering-settings.destroy', ['setting' => $settingId]))
                . '" title="Delete this unused numbering code">'
                . '<i class="fa fa-trash"></i> Delete</button>';
        }

        return '<div class="btn-group" role="group">' . implode(' ', $buttons) . '</div>';
    }

    private function repairMissingLocations(int $businessId): void
    {
        $statementIds = DB::table('customer_statements')
            ->where('business_id', $businessId)
            ->whereNull('location_id')
            ->orderBy('id')
            ->pluck('id');

        foreach ($statementIds as $statementId) {
            try {
                $locationId = $this->resolveLocationId($businessId, (int) $statementId, null);
                if ($locationId !== null) {
                    DB::table('customer_statements')
                        ->where('business_id', $businessId)
                        ->where('id', (int) $statementId)
                        ->whereNull('location_id')
                        ->update(['location_id' => $locationId]);
                }
            } catch (Throwable $exception) {
                Log::warning('Unable to repair a Customer Statement location.', [
                    'business_id' => $businessId,
                    'statement_id' => (int) $statementId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function resolveLocationId(
        int $businessId,
        int $statementId,
        ?int $requestedLocationId
    ): ?int {
        if ($requestedLocationId !== null) {
            $validRequestedLocation = DB::table('business_locations')
                ->where('business_id', $businessId)
                ->where('id', $requestedLocationId)
                ->value('id');

            if ($validRequestedLocation !== null) {
                return (int) $validRequestedLocation;
            }
        }

        $transactionLocation = DB::table('customer_statement_details as csd')
            ->join('transactions as t', 't.id', '=', 'csd.transaction_id')
            ->where('csd.business_id', $businessId)
            ->where('csd.statement_id', $statementId)
            ->where('t.business_id', $businessId)
            ->whereNotNull('t.location_id')
            ->orderByDesc('csd.id')
            ->value('t.location_id');

        if ($transactionLocation !== null) {
            return (int) $transactionLocation;
        }

        $namedLocation = DB::table('customer_statement_details as csd')
            ->join('business_locations as bl', function ($join): void {
                $join->on('bl.name', '=', 'csd.location')
                    ->on('bl.business_id', '=', 'csd.business_id');
            })
            ->where('csd.business_id', $businessId)
            ->where('csd.statement_id', $statementId)
            ->orderByDesc('csd.id')
            ->value('bl.id');

        if ($namedLocation !== null) {
            return (int) $namedLocation;
        }

        $defaultLocation = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->orderBy('id')
            ->value('id');

        return $defaultLocation !== null ? (int) $defaultLocation : null;
    }

    /** @param mixed $result */
    private function normalisePayload($result): array
    {
        if (is_array($result)) {
            return $result;
        }

        if ($result instanceof JsonResponse) {
            return (array) $result->getData(true);
        }

        if ($result instanceof Response) {
            $decoded = json_decode((string) $result->getContent(), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_object($result) && method_exists($result, 'toArray')) {
            return (array) $result->toArray();
        }

        throw new RuntimeException('The Customer Statement save action returned an invalid response.');
    }
}
