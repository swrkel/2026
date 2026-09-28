<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeImportBatch;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\CountService;
use Modules\StockTakingNew\Services\TenantScopeService;

class ImportController extends Controller
{
    public function template(StockTakeSession $session, Request $request, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        $fileName = 'stock-taking-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $session->stock_take_no) . '-count-import.csv';

        return response()->streamDownload(function () use ($session): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['line_id', 'sku', 'product_name', 'counted_qty', 'bin_location', 'notes']);
            $session->lines()->orderBy('product_name')->orderBy('id')->chunkById(500, function ($lines) use ($output): void {
                foreach ($lines as $line) {
                    fputcsv($output, [
                        $line->id,
                        $line->sku,
                        $line->product_name,
                        $line->final_count_qty,
                        $line->bin_location,
                        $line->notes,
                    ]);
                }
            });
            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(
        StockTakeSession $session,
        Request $request,
        TenantScopeService $scope,
        CountService $counts
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));
        $request->validate(['count_file' => 'required|file|mimes:csv,txt|max:10240']);

        $file = $request->file('count_file');
        $batch = StockTakeImportBatch::create([
            'business_id' => $session->business_id,
            'session_id' => $session->id,
            'file_name' => $file->getClientOriginalName(),
            'status' => 'processing',
            'created_by' => auth()->id(),
            'started_at' => now(),
        ]);

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $map = array_flip(array_map(fn ($value) => strtolower(trim((string) $value)), $header ?: []));
        $required = ['line_id', 'counted_qty'];
        foreach ($required as $column) {
            if (! array_key_exists($column, $map)) {
                fclose($handle);
                $batch->update([
                    'status' => 'failed',
                    'summary' => ['error' => "Missing required CSV column: {$column}"],
                    'completed_at' => now(),
                ]);
                return back()->withErrors("Missing required CSV column: {$column}");
            }
        }

        $rows = [];
        $total = 0;
        $failed = 0;
        $seen = [];
        while (($data = fgetcsv($handle)) !== false) {
            $total++;
            $lineId = (int) ($data[$map['line_id']] ?? 0);
            $quantity = $data[$map['counted_qty']] ?? null;
            if (! $lineId || $quantity === null || $quantity === '' || ! is_numeric($quantity) || isset($seen[$lineId])) {
                $failed++;
                continue;
            }
            $seen[$lineId] = true;
            $rows[] = [
                'line_id' => $lineId,
                'counted_qty' => (float) $quantity,
                'bin_location' => isset($map['bin_location']) ? ($data[$map['bin_location']] ?? null) : null,
                'notes' => isset($map['notes']) ? ($data[$map['notes']] ?? null) : null,
            ];
        }
        fclose($handle);

        try {
            $saved = $rows ? $counts->save($session, $rows, $session->status === 'recount') : 0;
            $batch->update([
                'status' => 'completed',
                'total_rows' => $total,
                'success_rows' => $saved,
                'failed_rows' => $failed,
                'summary' => ['saved' => $saved, 'skipped' => $failed],
                'completed_at' => now(),
            ]);
            return back()->with('status', "Import completed: {$saved} rows saved, {$failed} rows skipped.");
        } catch (\Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'total_rows' => $total,
                'failed_rows' => $total,
                'summary' => ['error' => $exception->getMessage()],
                'completed_at' => now(),
            ]);
            return back()->withErrors($exception->getMessage());
        }
    }
}
