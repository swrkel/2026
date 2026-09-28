<?php

namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\StockCenterService;

class StockCenterController extends Controller
{
    public function __construct(protected StockCenterService $stock) {}

    public function index(Request $request)
    {
        $filters = $this->stock->normaliseFilters($request->all());

        /*
         * IS2157: default the Location filter to the user's own location.
         *
         * The dropdown showed its placeholder ("Select Location") and the table
         * was empty. Neither was a permission fault - the user's
         * location_permissions column holds [2] and that location exists, so it
         * WAS in the list. Nothing marked it selected, so select2 fell back to
         * the placeholder and no location filter reached the query.
         *
         * When no location is chosen and the user has exactly one available, it
         * is selected automatically. That is the only case where the choice is
         * unambiguous - with several locations, picking one for the user would
         * hide the others' stock without saying so.
         *
         * Applied only when the request carries NO location_id at all. If the
         * user clears the filter deliberately, that is respected.
         */
        $filterLookups = $this->stock->filterLookups($filters);

        // "all" is a deliberate choice, not an empty one - the default below
        // must not overwrite it.
        if (($filters['location_id'] ?? '') !== 'all'
            && ! $request->has('location_id') && empty($filters['location_id'])) {
            $available = $filterLookups['locations'] ?? collect();

            /*
             | IS2169: always choose a location, not only when there is one.
             |
             | With none chosen the stock summed EVERY location - Lanka Petrol 92
             | showed 9,540 where the tanks held 2,188, because another location's
             | 7,302 was added in. Two products showed negative thousands the same
             | way.
             |
             | The user's own location first; failing that, the first they may see.
            */
            if ($available->count() === 1) {
                $filters['location_id'] = (int) $available->first()->id;
            } elseif ($available->count() > 1) {
                $own = (int) (session('user.location_id') ?: 0);
                $match = $own > 0 ? $available->firstWhere('id', $own) : null;
                $filters['location_id'] = (int) ($match->id ?? $available->first()->id);
            }
        }

        $rows = $this->stock->query($filters)
            ->paginate(50)
            ->appends($filters);

        return view('productsnew::stock_center.index', compact('rows', 'filterLookups', 'filters'));
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->stock->normaliseFilters($request->all());

        return response()->json(
            $this->stock->query($filters)
                ->paginate(50)
                ->appends($filters)
        );
    }

    public function details(Request $request, int $product): JsonResponse
    {
        $validated = $request->validate([
            'variation_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->stock->details(
                $product,
                isset($validated['variation_id']) ? (int) $validated['variation_id'] : null
            )
        );
    }
}
