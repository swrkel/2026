<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Transaction-safe finalization helper for PetroPD settlements.
 *
 * Controllers can call this service to avoid partially-finalized settlements,
 * duplicate finalization, and central-database assumptions.
 */
class PetroPdSettlementFinalizeService
{
    protected PetroPdFinalizationGuard $guard;
    protected PetroPdSchemaSafeQuery $schema;

    public function __construct(PetroPdFinalizationGuard $guard, PetroPdSchemaSafeQuery $schema)
    {
        $this->guard = $guard;
        $this->schema = $schema;
    }

    public function finalizeById(int $settlementId, ?int $businessId, callable $postingCallback = null): array
    {
        if (!$this->schema->hasTable('settlements')) {
            throw new RuntimeException('Required table settlements does not exist in tenant database.');
        }

        return DB::transaction(function () use ($settlementId, $businessId, $postingCallback) {
            $settlement = DB::table('settlements')->where('id', $settlementId)->lockForUpdate()->first();
            $this->guard->assertSettlementCanFinalize($settlement, $businessId);

            if ($postingCallback) {
                $postingCallback($settlement);
            }

            $payload = [];
            if ($this->schema->hasColumn('settlements', 'is_finalized')) {
                $payload['is_finalized'] = 1;
            }
            if ($this->schema->hasColumn('settlements', 'status')) {
                $payload['status'] = 'finalized';
            }
            if ($this->schema->hasColumn('settlements', 'settlement_status')) {
                $payload['settlement_status'] = 'finalized';
            }
            if ($this->schema->hasColumn('settlements', 'finalized_at')) {
                $payload['finalized_at'] = now();
            }
            if ($this->schema->hasColumn('settlements', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if (empty($payload)) {
                throw new RuntimeException('No supported finalization columns found on settlements table.');
            }

            DB::table('settlements')->where('id', $settlementId)->update($payload);

            return [
                'success' => true,
                'settlement_id' => $settlementId,
                'message' => 'Petro PD settlement finalized safely.',
            ];
        });
    }
}
