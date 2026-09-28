<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serves the administrative division lists used by location forms.
 *
 * ---------------------------------------------------------------------
 * WHY THIS READS THE CENTRAL DATABASE
 * ---------------------------------------------------------------------
 * admin_divisions lives in the central database, not in each tenant. One
 * list, maintained once. Add a country and every tenant sees it, with no
 * per-tenant import and no chance of nineteen databases drifting apart.
 *
 * The chosen VALUES are still stored in the tenant's own
 * business_locations row, as text. So reports inside a tenant need no
 * cross-database join - only the form reaches across, and only while
 * someone is filling it in.
 *
 * ---------------------------------------------------------------------
 * WHY IT LOADS ONE LEVEL AT A TIME
 * ---------------------------------------------------------------------
 * The form asks for countries, then the provinces of one country, then
 * the districts of one province. Nothing ever loads a full global list.
 *
 * That matters on this installation: the Manage page already makes 292
 * requests and transfers 25 MB. A complete worldwide division list would
 * be several megabytes on its own.
 *
 * ---------------------------------------------------------------------
 * CACHING
 * ---------------------------------------------------------------------
 * These lists change very rarely - a country is added perhaps once a
 * year. Cached for a day, keyed by parent, so a form that is opened
 * repeatedly does not re-query central every time.
 */
class AdminDivisionController extends Controller
{
    private const CACHE_MINUTES = 1440;

    /**
     * The connection holding admin_divisions.
     *
     * Prefers the explicit `system` connection, which config/database.php
     * defines as the central database and which tenancy never repoints.
     * Falls back to the configured central connection, then to the default.
     */
    private function centralConnection(): string
    {
        if (! empty(config('database.connections.system.database'))) {
            return 'system';
        }

        $candidate = (string) config('tenancy.database.central_connection', config('database.default'));

        return $candidate !== '' ? $candidate : (string) config('database.default');
    }

    /**
     * Countries. Level 1.
     */
    public function countries(): JsonResponse
    {
        return response()->json($this->divisions(null, 1));
    }

    /**
     * Provinces or states of one country. Level 2.
     */
    public function provinces(Request $request): JsonResponse
    {
        $countryName = trim((string) $request->query('country', ''));

        if ($countryName === '') {
            return response()->json([]);
        }

        $countryId = $this->idOfName($countryName, 1);

        return response()->json($countryId === null ? [] : $this->divisions($countryId, 2));
    }

    /**
     * Districts of one province. Level 3.
     */
    public function districts(Request $request): JsonResponse
    {
        $provinceName = trim((string) $request->query('province', ''));
        $countryName = trim((string) $request->query('country', ''));

        if ($provinceName === '') {
            return response()->json([]);
        }

        $countryId = $countryName === '' ? null : $this->idOfName($countryName, 1);
        $provinceId = $this->idOfName($provinceName, 2, $countryId);

        return response()->json($provinceId === null ? [] : $this->divisions($provinceId, 3));
    }

    /**
     * Names at one level under one parent.
     *
     * Returns a plain list of names rather than ids, because the form
     * stores the NAME in business_locations - matching how country, state
     * and city are stored today. Changing that to ids would mean migrating
     * existing rows across every tenant, which is not worth it for a
     * reporting improvement.
     */
    private function divisions(?int $parentId, int $level): array
    {
        $key = 'admin_divisions:' . $level . ':' . ($parentId ?? 'root');

        try {
            return Cache::remember($key, now()->addMinutes(self::CACHE_MINUTES), function () use ($parentId, $level) {
                $query = DB::connection($this->centralConnection())
                    ->table('admin_divisions')
                    ->where('level', $level)
                    ->where('is_active', 1);

                $parentId === null
                    ? $query->whereNull('parent_id')
                    : $query->where('parent_id', $parentId);

                return $query->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name')
                    ->all();
            });
        } catch (\Throwable $e) {
            // A location form must remain usable even if the central list is
            // unreachable. An empty list leaves the field free text rather
            // than blocking the page.
            Log::warning('Admin divisions could not be read.', [
                'level' => $level,
                'parent_id' => $parentId,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Resolve a division name to its id, optionally within a parent.
     */
    private function idOfName(string $name, int $level, ?int $parentId = null): ?int
    {
        try {
            $query = DB::connection($this->centralConnection())
                ->table('admin_divisions')
                ->where('level', $level)
                ->where('name', $name);

            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            }

            $id = $query->value('id');

            return $id === null ? null : (int) $id;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
