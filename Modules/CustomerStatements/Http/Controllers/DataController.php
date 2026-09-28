<?php

namespace Modules\CustomerStatements\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\CustomerStatements\Services\CustomerStatementPersistenceService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DataController extends Controller
{
    /**
     * ModuleUtil discovers every module DataController with `new $class()`.
     * Therefore this controller must remain constructible without dependency
     * injection. Resolve the module service lazily only when a route method is
     * actually called.
     */
    private function persistence(): CustomerStatementPersistenceService
    {
        return app(CustomerStatementPersistenceService::class);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            return response()->json($this->persistence()->store($request));
        } catch (Throwable $exception) {
            Log::error('Customer Statement module save failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => 0,
                'msg' => $exception->getMessage() ?: 'The Customer Statement could not be saved.',
            ], 422);
        }
    }

    public function billList(Request $request): Response
    {
        try {
            return $this->persistence()->billList($request);
        } catch (Throwable $exception) {
            Log::error('Customer Statement module bill list failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function statementList(Request $request): Response
    {
        try {
            return $this->persistence()->statementList($request);
        } catch (Throwable $exception) {
            Log::error('Customer Statement module list failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function numberingSettings(Request $request): JsonResponse
    {
        try {
            return response()->json($this->persistence()->numberingSettingsRows($request));
        } catch (Throwable $exception) {
            Log::error('Customer Statement numbering list failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'data' => [],
                'error' => $exception->getMessage(),
            ], 422);
        }
    }
}
