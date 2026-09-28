<?php
namespace Modules\ProductsNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewOpeningStockSession;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class OpeningStockService
{
    public function __construct(protected ProductsNewTenantGuard $guard, protected InventoryMovementService $movements) {}

    public function sessions(array $filters = [])
    {
        $q = DB::table('products_new_opening_stock_sessions as s')
            ->leftJoin('business_locations as l','l.id','=','s.location_id')
            ->select('s.*','l.name as location_name')
            ->orderByDesc('s.id');
        $this->guard->applyBusiness($q, 's.business_id');
        return $q;
    }

    public function createSession(array $data): ProductsNewOpeningStockSession
    {
        return ProductsNewOpeningStockSession::create([
            'business_id' => $this->guard->businessId(),
            'location_id' => $data['location_id'] ?? null,
            'reference_no' => $data['reference_no'] ?? ('OS-' . now()->format('Ymd-His')),
            'session_date' => $data['session_date'] ?? today(),
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    public function postLine(ProductsNewOpeningStockSession $session, array $data): void
    {
        $this->movements->record([
            'product_id' => $data['product_id'],
            'variation_id' => $data['variation_id'] ?? null,
            'location_id' => $session->location_id,
            'movement_type' => 'opening_stock',
            'movement_date' => $session->session_date,
            'qty' => $data['qty'],
            'unit_cost' => $data['unit_cost'] ?? 0,
            'reference_no' => $session->reference_no,
            'notes' => $data['notes'] ?? 'Opening stock session #' . $session->id,
        ]);
    }
}
