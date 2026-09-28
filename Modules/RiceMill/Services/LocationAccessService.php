<?php
namespace Modules\RiceMill\Services;

use App\Services\BusinessLocationAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rice Mill adapter for the ERP's authoritative location access boundary.
 *
 * v27 keeps a request-local snapshot of location masters. The same request
 * often needs Locations more than once (for example a form + Store filtering),
 * so re-querying the same tenant/business rows wastes time and can make pages
 * feel slow. No cross-request cache is used, so newly-added Locations remain
 * immediately visible on the next request.
 */
class LocationAccessService
{
    private array $memo = [];
    private ?array $columns = null;

    public function __construct(private BusinessLocationAccessService $access)
    {
    }

    /** @return array<int, array{id:int,name:string,business_id:int}> */
    public function options(int $businessId): array
    {
        $key = 'operational:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if (! Schema::hasTable('business_locations')) {
            return $this->memo[$key] = [];
        }

        $allowed = $this->access->locationOptions($businessId);
        $ids = $allowed->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        if (! $ids) {
            return $this->memo[$key] = [];
        }

        $columns = $this->locationColumns();
        $query = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->whereIn('id', $ids);

        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', 1);
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        return $this->memo[$key] = $query->orderBy('name')
            ->get(['id', 'name', 'business_id'])
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'business_id' => (int) $row->business_id,
            ])
            ->all();
    }

    /**
     * Return every active Business Location for the selected business inside
     * the already-initialized tenant database. This is intentionally NOT
     * filtered by the logged-in user's operational location permissions.
     *
     * @return array<int, array{id:int,name:string,business_id:int}>
     */
    public function businessOptions(int $businessId): array
    {
        $key = 'business:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        if (! Schema::hasTable('business_locations')) {
            return $this->memo[$key] = [];
        }

        $columns = $this->locationColumns();
        $query = DB::table('business_locations')->where('business_id', $businessId);

        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', 1);
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        return $this->memo[$key] = $query->orderBy('name')
            ->get(['id', 'name', 'business_id'])
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'business_id' => (int) $row->business_id,
            ])
            ->all();
    }

    public function assertBusinessLocation(?int $locationId, int $businessId, bool $required = false): void
    {
        if (! $locationId) {
            if ($required) {
                abort(422, 'Please select a location.');
            }
            return;
        }

        if (! Schema::hasTable('business_locations')) {
            abort(422, 'Business Locations are not available in this tenant.');
        }

        $columns = $this->locationColumns();
        $query = DB::table('business_locations')
            ->where('id', $locationId)
            ->where('business_id', $businessId);

        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', 1);
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        abort_unless(
            $query->exists(),
            403,
            'The selected location does not belong to the active tenant/business.'
        );
    }

    /** @return array<int, int> */
    public function ids(int $businessId): array
    {
        $key = 'ids:' . $businessId;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        return $this->memo[$key] = array_values(array_map(
            static fn ($row) => (int) $row['id'],
            $this->options($businessId)
        ));
    }

    public function assert(?int $locationId, int $businessId, bool $required = false): void
    {
        if (! $locationId) {
            if ($required) {
                abort(422, 'Please select a location.');
            }
            return;
        }

        $this->access->assertLocationAccess($locationId, $businessId);
    }

    private function locationColumns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        return $this->columns = Schema::getColumnListing('business_locations');
    }
}
