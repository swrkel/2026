<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Services\ProductCatalogGateway;
use Modules\ReportsOther\Support\CurrentScope;

class SourceMappingController extends Controller
{
    public function store(Request $request, CurrentScope $scope, ProductCatalogGateway $catalog)
    {
        $data = $request->validate([
            'source_name' => ['required', 'string', 'max:120'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1'],
            'sub_category_ids' => ['nullable', 'array'],
            'sub_category_ids.*' => ['integer', 'min:1'],
        ]);

        $items = $catalog->selectedItems(
            $scope->businessId(),
            $data['category_ids'] ?? [],
            $data['sub_category_ids'] ?? []
        );

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors(['mapping' => 'Select at least one Product Category or Product Sub Category.']);
        }

        DB::transaction(function () use ($data, $items, $scope) {
            $source = Source::query()->create([
                'business_id' => $scope->businessId(),
                'location_id' => $scope->locationId(),
                'store_id' => $scope->storeId(),
                'scope_key' => $scope->key(),
                'source_name' => trim($data['source_name']),
                'created_by' => $scope->userId(),
                'created_by_name' => $scope->userName(),
            ]);

            $source->mappings()->createMany($items->map(fn ($item) => [
                'item_type' => $item['type'],
                'item_id' => $item['id'],
                'item_name_snapshot' => $item['name'],
                'created_at' => now(),
            ])->all());
        });

        return redirect()->route('reports-other.cash-receipt.index', ['tab' => 'mapping'])
            ->with('status', 'Source mapping saved successfully.');
    }

    public function destroy(Source $source, CurrentScope $scope)
    {
        abort_unless(hash_equals((string) $scope->key(), (string) $source->scope_key), 403);

        if (Receipt::query()->where('scope_key', $scope->key())->where('source_id', $source->id)->exists()) {
            return back()->withErrors(['mapping' => 'This Source is already used by one or more Receipts and cannot be deleted.']);
        }

        $source->delete();

        return redirect()->route('reports-other.cash-receipt.index', ['tab' => 'mapping'])
            ->with('status', 'Source mapping deleted successfully.');
    }
}
