<?php

namespace Modules\CustomerStatements\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\CustomerStatements\Services\CustomerStatementNumberingService;
use Throwable;

class CustomerStatementNumberingController extends Controller
{
    private CustomerStatementNumberingService $numbering;

    public function __construct(CustomerStatementNumberingService $numbering)
    {
        $this->numbering = $numbering;
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $businessId = $this->numbering->businessId($request);
            $customerId = $request->filled('customer_id')
                ? (int) $request->input('customer_id')
                : null;

            return response()->json([
                'success' => 1,
                'data' => $this->numbering->settings($businessId, $customerId),
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $businessId = $this->numbering->businessId($request);
            $mode = $this->normaliseMode($request);
            $customerId = $request->filled('customer_id')
                ? (int) $request->input('customer_id')
                : null;
            $startingNo = max(1, (int) $request->input('starting_no', 1));

            $data = $this->numbering->save(
                $businessId,
                $mode,
                $startingNo,
                $customerId
            );

            return response()->json([
                'success' => 1,
                'msg' => __('customerstatements::lang.numbering_settings_saved'),
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function destroy(Request $request, int $setting): JsonResponse
    {
        try {
            $businessId = $this->numbering->businessId($request);
            $this->numbering->deleteSetting($businessId, $setting);

            return response()->json([
                'success' => 1,
                'msg' => 'Customer Statement numbering code deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function next(Request $request): JsonResponse
    {
        try {
            $businessId = $this->numbering->businessId($request);
            $customerId = (int) $request->input('customer_id');
            $data = $this->numbering->nextNumber($businessId, $customerId);
            $data['header'] = $this->statementHeader($request, $data['statement_no']);

            return response()->json($data);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    private function normaliseMode(Request $request): string
    {
        $mode = strtolower(trim((string) $request->input('mode', '')));

        if (in_array($mode, ['customer', 'customer-wise', 'customer_wise'], true)) {
            return 'customer';
        }

        if ($mode === 'general') {
            return 'general';
        }

        return (string) $request->input('enable_separate_customer_statement_no') === '0'
            ? 'general'
            : 'customer';
    }

    private function statementHeader(Request $request, int $statementNo): string
    {
        $controllerClass = \Modules\Customers\Http\Controllers\CustomerStandaloneStatementController::class;

        if (! class_exists($controllerClass)) {
            return '';
        }

        try {
            $controller = app($controllerClass);

            if (method_exists($controller, 'getStatementHeader')) {
                return (string) $controller->getStatementHeader($request, $statementNo);
            }
        } catch (Throwable $exception) {
            Log::warning('Customer Statement header preview could not be refreshed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return '';
    }

    private function failure(Throwable $exception): JsonResponse
    {
        Log::error('Customer Statement numbering operation failed.', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return response()->json([
            'success' => 0,
            'msg' => $exception->getMessage() ?: __('customerstatements::lang.numbering_settings_failed'),
        ], 422);
    }
}
