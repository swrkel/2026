<?php

namespace Modules\PetroDirect\Http\Controllers\Settlement\Concerns;

use Modules\PetroDirect\Support\PetroDirectDebug;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\PetroDirect\Entities\CustomerPayment;
use Modules\PetroDirect\Entities\CustomerBillVatPrefix;
use Modules\PetroDirect\Entities\DailyCard;
use Modules\PetroDirect\Entities\DailyCollection;
use Modules\PetroDirect\Entities\DailyVoucher;
use Modules\PetroDirect\Entities\DayEnd;
use Modules\PetroDirect\Entities\FuelTank;
use Modules\PetroDirect\Entities\MeterSale;
use Modules\PetroDirect\Entities\OtherIncome;
use Modules\PetroDirect\Entities\OtherSale;
use Modules\PetroDirect\Entities\PetroShift;
use Modules\PetroDirect\Entities\PetroWhatsAppTemplate;
use Modules\PetroDirect\Entities\Pump;
use Modules\PetroDirect\Entities\PumperDayEntry;
use Modules\PetroDirect\Entities\PumpOperator;
use Modules\PetroDirect\Entities\PumpOperatorAssignment;
use Modules\PetroDirect\Entities\PumpOperatorCommission;
use Modules\PetroDirect\Entities\PumpOperatorPayment;
use Modules\PetroDirect\Entities\PumpOperatorOtherSale;
use Modules\PetroDirect\Entities\Settlement;
use Modules\PetroDirect\Entities\SettlementCardPayment;
use Modules\PetroDirect\Entities\SettlementCashDeposit;
use Modules\PetroDirect\Entities\SettlementCashPayment;
use Modules\PetroDirect\Entities\SettlementChequePayment;
use Modules\PetroDirect\Entities\SettlementCreditSalePayment;
use Modules\PetroDirect\Entities\SettlementEditHistory;
use Modules\PetroDirect\Entities\SettlementExcessPayment;
use Modules\PetroDirect\Entities\PumpOperatorMeterSale;
use Modules\PetroDirect\Entities\SettlementExpensePayment;
use Modules\PetroDirect\Entities\SettlementShortagePayment;
use Modules\PetroDirect\Entities\SettlementLoanPayment;
use Modules\PetroDirect\Entities\SettlementDrawingPayment;
use Modules\PetroDirect\Entities\SettlementCustomerLoan;
use Modules\PetroDirect\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroDirect\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Methods specific to PetroDirect that the shared grouping does not cover.
 *
 * MA-002: split out of PetroDirect's SettlementController, which was 10,590
 * lines in a single file.
 *
 * The grouping follows the one used for the PD settlement controllers, since
 * these files share most of their method names - but it was rebuilt against
 * THIS file, because the modules have genuinely diverged in content.
 *
 * Traits, not separate controllers: routes, action() targets and the $this->
 * calls between these methods all resolve exactly as before. Method bodies
 * are byte-identical to the original.
 *
 * Methods here: getCurrentBusinessIdForDirectSettlement, shouldShowMechanicalMeterToo, getMechanicalMeterDifferenceSummary, excludePetroPdModuleAssignments, getDirectSettlementPrefix, getNextDirectSettlementNo, scopePetroDirectOwnedSettlements, isPetroDirectOwnedSettlement, repairLegacyDirectDraftOwnership, ensureDirectSettlementOwnership, isHistoricalDirectSettlementRecord, findReusableDirectDraftSettlement, getDirectSettlementHiddenPendingPumpOperatorIds, getDirectSettlementPumpOperators, shouldShowPendingShiftPumpOperatorsInDirectSettlement, mechanicalMeter, hasPetroDirectAccess, canEditDirectSettlements, getDirectSettlementOtherSaleItems, normalizeDirectSettlementDate, getPumpOperatorOtherSaleTotalForSettlement, getPrintPumpOperatorOtherSales, extractShiftIdsFromSettlement, normalizeDirectSettlementShiftLabel, getDirectSettlementCreditSales, getCreditSaleReportDate, getCreditSaleBillNumber, ensureCreditSaleCustomerAccounting, getNextAutoShiftNumber, createAutoIncrementedShiftForSettlement, resolveAutoShiftLocationId, incrementShiftNumber, storeManualShiftNumber, getPumpsByLocation, extractRequestedSettlementShiftIds, draftSettlementMatchesRequestedShifts, getDirectSettlementStoreDropdown, resolveDirectSettlementDefaultStoreId, checkPreviousPumpSettlement, laterPumpSettlementExists, getMeterSaleTableHtml, attachUnsettledRteMeterSalesToSettlement, syncRealTimePaymentsToSettlement, updateSettlementTotalAmount
 */
trait HandlesModuleSpecifics
{
    /**
     * Resolve the operational business only after PetroDirect tenancy has been
     * initialized. Never map a central login to a tenant user by numeric user ID:
     * numeric IDs are independent between databases and can point at another
     * person/business in the tenant DB.
     */
    private function getCurrentBusinessIdForDirectSettlement(): ?int
    {
        $request = request();
        $session = $request->session();
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && method_exists($authUser, 'can') && $authUser->can('superadmin');

        // 1) Strongest mapping: match the already-authenticated identity to the
        // active DB by stable identity fields. Require one unambiguous business.
        try {
            if ($authUser && Schema::hasTable('users') && Schema::hasTable('business')) {
                $username = trim((string) ($authUser->username ?? ''));
                $email = trim((string) ($authUser->email ?? ''));
                $hasUsername = $username !== '' && Schema::hasColumn('users', 'username');
                $hasEmail = $email !== '' && Schema::hasColumn('users', 'email');

                if (($hasUsername || $hasEmail) && Schema::hasColumn('users', 'business_id')) {
                    $localBusinessIds = DB::table('users')
                        ->where(function ($query) use ($hasUsername, $hasEmail, $username, $email) {
                            if ($hasUsername) {
                                $query->where('username', $username);
                            }
                            if ($hasEmail) {
                                $hasUsername
                                    ? $query->orWhere('email', $email)
                                    : $query->where('email', $email);
                            }
                        })
                        ->whereNotNull('business_id')
                        ->pluck('business_id')
                        ->map(static fn ($id) => (int) $id)
                        ->filter(static fn ($id) => $id > 0)
                        ->unique()
                        ->values();

                    if ($localBusinessIds->count() === 1) {
                        $businessId = (int) $localBusinessIds->first();
                        if (Business::where('id', $businessId)->exists()) {
                            $this->syncDirectSettlementBusinessSession($businessId);
                            return $businessId;
                        }
                    }

                    if ($localBusinessIds->count() > 1) {
                        Log::warning('PetroDirect: login identity maps to multiple tenant businesses; refusing to guess.', [
                            'username' => $username,
                            'email' => $email,
                            'business_ids' => $localBusinessIds->all(),
                            'path' => $request->path(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('PetroDirect: stable-identity business resolution failed.', [
                'message' => $exception->getMessage(),
                'path' => $request->path(),
            ]);
        }

        // 2) A tenant-local selected location can resolve the business, but only
        // when that location exists in the active database.
        $locationIds = array_values(array_unique(array_filter([
            (int) ($session->get('business_location_id') ?? 0),
            (int) ($session->get('user.business_location_id') ?? 0),
            (int) ($session->get('location_id') ?? 0),
        ], static fn ($id) => (int) $id > 0)));

        foreach ($locationIds as $locationId) {
            try {
                $locationBusinessId = (int) BusinessLocation::where('id', $locationId)->value('business_id');
                if (
                    $locationBusinessId > 0
                    && Business::where('id', $locationBusinessId)->exists()
                    && ($isSuperAdmin || $this->authenticatedIdentityBelongsToBusiness($locationBusinessId, $authUser))
                ) {
                    $this->syncDirectSettlementBusinessSession($locationBusinessId);
                    return $locationBusinessId;
                }
            } catch (\Throwable $exception) {
                // Continue to deterministic single-business fallback.
            }
        }

        // 3) A one-business tenant is deterministic. This also covers central
        // installations where PetroDirect is used directly in the master DB.
        try {
            $businessIds = Business::query()->orderBy('id')->limit(2)->pluck('id')->map('intval')->all();
            if (count($businessIds) === 1) {
                $businessId = (int) $businessIds[0];
                $this->syncDirectSettlementBusinessSession($businessId);
                return $businessId;
            }
        } catch (\Throwable $exception) {
        }

        // 4) On a multi-business DB, session IDs are accepted only when the
        // authenticated stable identity can be verified inside that same business.
        // This prevents a valid-but-wrong business ID from exposing another
        // business's settlement sequence and shifts.
        $sessionBusinessIds = array_values(array_unique(array_filter([
            (int) ($session->get('business.id') ?? 0),
            (int) ($session->get('user.business_id') ?? 0),
            (int) ($authUser->business_id ?? 0),
        ], static fn ($id) => (int) $id > 0)));

        foreach ($sessionBusinessIds as $businessId) {
            try {
                if (! Business::where('id', $businessId)->exists()) {
                    continue;
                }

                if ($isSuperAdmin || $this->authenticatedIdentityBelongsToBusiness($businessId, $authUser)) {
                    $this->syncDirectSettlementBusinessSession($businessId);
                    return $businessId;
                }
            } catch (\Throwable $exception) {
            }
        }

        Log::error('PetroDirect: no unambiguous active business for Direct Settlement; request blocked to prevent cross-business data.', [
            'session_business_ids' => $sessionBusinessIds,
            'path' => $request->path(),
            'authenticated_user_id' => (int) ($authUser->id ?? 0),
            'authenticated_username' => (string) ($authUser->username ?? ''),
        ]);

        return null;
    }

    private function authenticatedIdentityBelongsToBusiness(int $businessId, $authUser): bool
    {
        if (! $authUser || ! Schema::hasTable('users') || ! Schema::hasColumn('users', 'business_id')) {
            return false;
        }

        $username = trim((string) ($authUser->username ?? ''));
        $email = trim((string) ($authUser->email ?? ''));
        $hasUsername = $username !== '' && Schema::hasColumn('users', 'username');
        $hasEmail = $email !== '' && Schema::hasColumn('users', 'email');

        if (! $hasUsername && ! $hasEmail) {
            return false;
        }

        return DB::table('users')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($hasUsername, $hasEmail, $username, $email) {
                if ($hasUsername) {
                    $query->where('username', $username);
                }
                if ($hasEmail) {
                    $hasUsername
                        ? $query->orWhere('email', $email)
                        : $query->where('email', $email);
                }
            })
            ->exists();
    }

    /** Keep only operational business values synchronized; never replace auth identity. */
    private function syncDirectSettlementBusinessSession(int $businessId): void
    {
        if ($businessId <= 0 || ! request()->hasSession()) {
            return;
        }

        $session = request()->session();
        $session->put('business.id', $businessId);
        $session->put('user.business_id', $businessId);

        try {
            $business = Business::find($businessId);
            if (! $business) {
                return;
            }

            foreach (['ref_no_prefixes', 'ref_no_starting_number', 'default_store', 'currency_precision'] as $field) {
                if (isset($business->{$field}) || $business->{$field} !== null) {
                    $session->put('business.' . $field, $business->{$field});
                }
            }
        } catch (\Throwable $exception) {
            // Business ID is already synchronized; optional presentation settings
            // can safely fall back to DB reads in the caller.
        }
    }

    private function getCurrentDirectSettlementOwnerId(): int
    {
        return (int) (optional(auth()->user())->id ?? 0);
    }

    /**
     * An unsaved Direct Settlement draft belongs to the browser user who created
     * it. Never silently hydrate another user's draft merely because it is the
     * newest open row for the same business/location.
     */
    private function scopeCurrentDirectSettlementDraftOwner($query, ?int $businessId = null)
    {
        if (SchemaCapabilityCache::hasColumn('settlements', 'created_by')) {
            $ownerId = $this->getCurrentDirectSettlementOwnerId();
            if ($ownerId > 0) {
                $query->where('created_by', $ownerId);
            } else {
                // No authenticated owner means no draft may be auto-hydrated.
                $query->whereRaw('1 = 0');
            }

            return $query;
        }

        // Older schemas without settlements.created_by are still protected:
        // only the draft ID remembered in THIS user's Laravel session can be reused.
        $businessId = (int) ($businessId ?: ($this->getCurrentBusinessIdForDirectSettlement() ?: 0));
        $rememberedDraftId = $this->getRememberedDirectSettlementDraftId($businessId);
        $query->where('id', $rememberedDraftId > 0 ? $rememberedDraftId : -1);

        return $query;
    }

    private function directSettlementCreatedByAttributes(): array
    {
        if (! SchemaCapabilityCache::hasColumn('settlements', 'created_by')) {
            return [];
        }

        $ownerId = $this->getCurrentDirectSettlementOwnerId();
        return $ownerId > 0 ? ['created_by' => $ownerId] : [];
    }

    private function directSettlementDraftSessionKey(int $businessId): string
    {
        return 'petrodirect_direct_draft_id_' . max(0, $businessId);
    }

    private function rememberDirectSettlementDraft(Settlement $settlement): void
    {
        if (! request()->hasSession() || (int) $settlement->business_id <= 0 || (int) $settlement->status !== 1) {
            return;
        }

        request()->session()->put(
            $this->directSettlementDraftSessionKey((int) $settlement->business_id),
            (int) $settlement->id
        );
    }

    private function getRememberedDirectSettlementDraftId(int $businessId): int
    {
        if (! request()->hasSession() || $businessId <= 0) {
            return 0;
        }

        return (int) request()->session()->get($this->directSettlementDraftSessionKey($businessId), 0);
    }

    private function forgetRememberedDirectSettlementDraft(int $businessId, ?int $settlementId = null): void
    {
        if (! request()->hasSession() || $businessId <= 0) {
            return;
        }

        $key = $this->directSettlementDraftSessionKey($businessId);
        if ($settlementId !== null && (int) request()->session()->get($key, 0) !== (int) $settlementId) {
            return;
        }

        request()->session()->forget($key);
    }

    private const SHOW_MECHANICAL_METER_SETTING = 'mech_mtr';
    /**
     * All Utils instance.
     */






    protected function shouldShowMechanicalMeterToo($business_id): bool
    {
        $business_ids = array_values(array_unique(array_filter([
            $business_id,
            request()->session()->get('business.id'),
            request()->session()->get('user.business_id'),
        ])));

        $latest_setting = CustomerBillVatPrefix::whereIn('business_id', $business_ids)
            ->where('prefix', self::SHOW_MECHANICAL_METER_SETTING)
            ->latest('id')
            ->first();

        return empty($latest_setting) || (int) $latest_setting->starting_no === 1;
    }

    protected function getMechanicalMeterDifferenceSummary(Settlement $settlement): string
    {
        if (! SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
            return '';
        }

        return MeterSale::petroDirectOwned()
            ->leftJoin('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->where('meter_sales.settlement_no', $settlement->id)
            ->whereNotNull('meter_sales.mechanical_meter_difference')
            ->select('meter_sales.mechanical_meter_difference', 'pumps.pump_name', 'pumps.pump_no')
            ->get()
            ->map(function ($meter_sale) {
                $pump_name = $meter_sale->pump_name ?: $meter_sale->pump_no;

                return trim(($pump_name ?: 'Pump') . ': ' . number_format((float) $meter_sale->mechanical_meter_difference, 3, '.', ''));
            })
            ->filter()
            ->implode(', ');
    }

    protected function excludePetroPdModuleAssignments($query, $business_id, string $assignmentAlias = 'pump_operator_assignments', string $settlementAlias = 'settlements')
    {
        $petroPdPrefixes = $this->getPetroPdModuleSettlementPrefixes($business_id);

        $query->where(function ($q) use ($petroPdPrefixes, $settlementAlias) {
            $q->whereNull($settlementAlias . '.id')
                ->orWhere(function ($notPdSettlement) use ($petroPdPrefixes, $settlementAlias) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $notPdSettlement->where($settlementAlias . '.settlement_no', 'NOT LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('settlements as pd_assignment_settlements')
                ->whereColumn('pd_assignment_settlements.id', $assignmentAlias . '.settlement_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_assignment_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('pump_operator_meter_sales as pd_operator_meter_sales')
                ->whereColumn('pd_operator_meter_sales.shift_id', $assignmentAlias . '.shift_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_operator_meter_sales.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        $query->whereNotExists(function ($subQuery) use ($assignmentAlias, $petroPdPrefixes) {
            $subQuery->select(DB::raw(1))
                ->from('meter_sales as pd_meter_sales')
                ->join('settlements as pd_meter_settlements', 'pd_meter_settlements.id', '=', 'pd_meter_sales.settlement_no')
                ->whereColumn('pd_meter_sales.shift_id', $assignmentAlias . '.shift_id')
                ->where(function ($prefixQuery) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $prefixQuery->orWhere('pd_meter_settlements.settlement_no', 'LIKE', $prefix . '%');
                    }
                });
        });

        return $query;
    }

    /**
     * Legacy settlement prefix used by historical Petro Direct records.
     *
     * IMPORTANT: this is kept only so old ST... Direct settlements remain
     * readable.  New Direct Settlements never use this prefix anymore.
     */
    protected function getDirectSettlementPrefix($business_id): string
    {
        $prefixes = [];

        if (! empty($business_id)) {
            $business = Business::find((int) $business_id);
            $prefixes = $business->ref_no_prefixes ?? [];
        }

        if (empty($prefixes)) {
            $prefixes = request()->session()->get('business.ref_no_prefixes', []);
        }

        if (is_string($prefixes)) {
            $decoded = json_decode($prefixes, true);
            $prefixes = is_array($decoded) ? $decoded : [];
        }

        $prefix = $prefixes['settlement'] ?? 'ST';

        return ! empty($prefix) ? $prefix : 'ST';
    }

    /**
     * Canonical prefix for every NEW Petro Direct settlement and Direct shift.
     * This is intentionally NOT configurable: the user requested one permanent
     * sequence DST1, DST2, DST3 ... for both values.
     */
    protected function getCanonicalDirectSettlementPrefix(): string
    {
        return 'DST';
    }

    protected function isCanonicalDirectSettlementNo(?string $settlementNo): bool
    {
        $settlementNo = trim((string) $settlementNo);

        return (bool) preg_match('/^' . preg_quote($this->getCanonicalDirectSettlementPrefix(), '/') . '[1-9][0-9]*$/', $settlementNo);
    }

    /**
     * Accept both the new DST... number series and the legacy configured Direct
     * settlement series when READING historical records.
     */
    protected function isDirectSettlementNumberCandidate(?string $settlementNo, int $businessId): bool
    {
        $settlementNo = trim((string) $settlementNo);
        if ($settlementNo === '' || Str::startsWith($settlementNo, 'SET-SW') || Str::startsWith($settlementNo, 'PDST')) {
            return false;
        }

        if ($this->isCanonicalDirectSettlementNo($settlementNo)) {
            return true;
        }

        $legacyPrefix = trim((string) $this->getDirectSettlementPrefix($businessId));

        return $legacyPrefix !== '' && Str::startsWith($settlementNo, $legacyPrefix);
    }

    protected function scopeDirectSettlementNumberSeries($query, int $businessId, string $column = 'settlement_no')
    {
        $canonicalPrefix = $this->getCanonicalDirectSettlementPrefix();
        $legacyPrefix = trim((string) $this->getDirectSettlementPrefix($businessId));

        return $query->where(function ($numberQuery) use ($column, $canonicalPrefix, $legacyPrefix) {
            $numberQuery->where($column, 'LIKE', $canonicalPrefix . '%');

            if ($legacyPrefix !== '' && $legacyPrefix !== $canonicalPrefix) {
                $numberQuery->orWhere($column, 'LIKE', $legacyPrefix . '%');
            }
        });
    }

    /**
     * True only for the NEW canonical Direct sequence.  Historical DST shift
     * labels do not advance the counter; only a stored pair DSTn / DSTn does.
     */
    protected function isCanonicalDirectSettlementPair(Settlement $settlement, int $businessId): bool
    {
        $settlementNo = trim((string) $settlement->settlement_no);
        if (! $this->isCanonicalDirectSettlementNo($settlementNo)) {
            return false;
        }

        $shiftLabel = $this->normalizeDirectSettlementShiftLabel($settlement->work_shift, $businessId);

        return $shiftLabel === $settlementNo;
    }

    /**
     * Preview the next NEW Direct Settlement number.
     *
     * Only canonical pairs DSTn / DSTn advance this sequence, so old ST...
     * settlements and old haphazard DST shift numbers are completely ignored.
     */
    protected function getNextDirectSettlementNo($business_id, ?string $preferredSettlementNo = null): string
    {
        $business_id = (int) $business_id;
        $prefix = $this->getCanonicalDirectSettlementPrefix();
        $preferredSettlementNo = trim((string) $preferredSettlementNo);

        if ($this->isCanonicalDirectSettlementNo($preferredSettlementNo)) {
            $ownedDraftQuery = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $preferredSettlementNo)
                ->where('status', 1);
            $this->scopeCurrentDirectSettlementDraftOwner($ownedDraftQuery, $business_id);

            $ownedDraft = $ownedDraftQuery->first();
            if (! empty($ownedDraft) && $this->isCanonicalDirectSettlementPair($ownedDraft, $business_id)) {
                return $preferredSettlementNo;
            }

            if (! Settlement::where('business_id', $business_id)->where('settlement_no', $preferredSettlementNo)->exists()) {
                $preferredSequence = $this->extractLastInteger($preferredSettlementNo);
                $lastCanonicalSequence = $this->getLastCanonicalDirectSettlementSequence($business_id);
                if ($preferredSequence === $lastCanonicalSequence + 1) {
                    return $preferredSettlementNo;
                }
            }
        }

        return $prefix . ($this->getLastCanonicalDirectSettlementSequence($business_id) + 1);
    }

    protected function getLastCanonicalDirectSettlementSequence(int $businessId): int
    {
        $prefix = $this->getCanonicalDirectSettlementPrefix();

        return (int) (Settlement::where('business_id', $businessId)
            ->where('settlement_no', 'LIKE', $prefix . '%')
            ->get(['settlement_no', 'work_shift'])
            ->filter(function (Settlement $settlement) use ($businessId) {
                return $this->isCanonicalDirectSettlementPair($settlement, $businessId);
            })
            ->map(function (Settlement $settlement) {
                return $this->extractLastInteger($settlement->settlement_no);
            })
            ->max() ?? 0);
    }

    /**
     * Atomically reserve a NEW Direct Settlement / Shift pair.
     *
     * Locking the active business row serializes number allocation per business,
     * preventing two users from receiving the same DST number concurrently.
     */
    protected function createCanonicalDirectSettlementDraft(int $businessId, array $attributes): Settlement
    {
        return DB::transaction(function () use ($businessId, $attributes) {
            $lockedBusiness = Business::where('id', $businessId)->lockForUpdate()->first();
            if (empty($lockedBusiness)) {
                throw new \RuntimeException('Unable to resolve the active Petro Direct business while allocating the DST number.');
            }

            $settlementNo = $this->getNextDirectSettlementNo($businessId);

            $attributes['business_id'] = $businessId;
            $attributes['settlement_no'] = $settlementNo;
            $attributes['work_shift'] = [$settlementNo];
            $attributes['status'] = array_key_exists('status', $attributes) ? $attributes['status'] : 1;

            return Settlement::create($attributes);
        }, 3);
    }


    /**
     * IS1761: PetroDirect owns only settlements carrying its synthetic DST work-shift label.
     * Pumper Dashboard / PetroPD shifts share legacy tables, so settlement number prefixes alone
     * are not a reliable module boundary before a PD settlement is finalized.
     */

    protected function scopePetroDirectOwnedSettlements($query, int $business_id, string $workShiftColumn = 'work_shift')
    {
        return $query->where($workShiftColumn, 'LIKE', '%' . $this->getDirectSettlementShiftPrefix($business_id) . '%');
    }

    protected function isPetroDirectOwnedSettlement(?Settlement $settlement, ?int $business_id = null): bool
    {
        if (empty($settlement)) {
            return false;
        }

        $business_id = $business_id ?: (int) ($settlement->business_id ?: $this->getCurrentBusinessIdForDirectSettlement());

        return ! empty($this->normalizeDirectSettlementShiftLabel($settlement->work_shift, $business_id));
    }

    /**
     * Repair a legacy Petro Direct draft which was created by the old payment
     * helper before the synthetic DST ownership marker was persisted.
     *
     * The repair is deliberately conservative.  A record linked to an
     * operational assignment, an operational meter row or a pumper meter row
     * is owned by Pumper Dashboard/Petro PD and can never be claimed here.
     */

    protected function repairLegacyDirectDraftOwnership(Settlement $settlement, int $business_id): bool
    {
        if ($this->isPetroDirectOwnedSettlement($settlement, $business_id)) {
            return true;
        }

        $settlementNo = trim((string) $settlement->settlement_no);

        if (
            (int) $settlement->business_id !== $business_id
            || (int) $settlement->status !== 1
            || ! $this->isDirectSettlementNumberCandidate($settlementNo, $business_id)
        ) {
            return false;
        }

        // A legacy Direct draft can legitimately contain meter_sales rows with
        // a historical shift_id.  That alone is not proof of Pumper/Petro PD
        // ownership.  Reject only when an operational Pumper meter source is
        // actually linked to the assignment/settlement.
        $hasOperationalAssignmentMeter = false;
        if (
            SchemaCapabilityCache::hasTable('pump_operator_assignments')
            && SchemaCapabilityCache::hasTable('pump_operator_meter_sales')
            && SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'settlement_id')
            && SchemaCapabilityCache::hasColumn('pump_operator_assignments', 'shift_id')
            && SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'shift_id')
        ) {
            $hasOperationalAssignmentMeter = DB::table('pump_operator_assignments as direct_ownership_assignments')
                ->where('direct_ownership_assignments.business_id', $business_id)
                ->where('direct_ownership_assignments.settlement_id', $settlement->id)
                ->whereExists(function ($meterQuery) {
                    $meterQuery->select(DB::raw(1))
                        ->from('pump_operator_meter_sales as direct_ownership_meter_sales')
                        ->whereColumn(
                            'direct_ownership_meter_sales.shift_id',
                            'direct_ownership_assignments.shift_id'
                        );
                })
                ->exists();
        }

        $hasPumperMeter = false;
        if (
            SchemaCapabilityCache::hasTable('pump_operator_meter_sales')
            && SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'settlement_no')
        ) {
            $hasPumperMeter = DB::table('pump_operator_meter_sales')
                ->where('business_id', $business_id)
                ->where(function ($linked) use ($settlement) {
                    $linked->where('settlement_no', (string) $settlement->id)
                        ->orWhere('settlement_no', (string) $settlement->settlement_no);
                })
                ->exists();
        }

        $isPetroPdOnlyOperator = false;
        if (
            ! empty($settlement->pump_operator_id)
            && SchemaCapabilityCache::hasTable('pump_operators')
            && SchemaCapabilityCache::hasColumn('pump_operators', 'is_petro_pd_only')
        ) {
            $isPetroPdOnlyOperator = DB::table('pump_operators')
                ->where('business_id', $business_id)
                ->where('id', $settlement->pump_operator_id)
                ->where('is_petro_pd_only', 1)
                ->exists();
        }

        if ($hasOperationalAssignmentMeter || $hasPumperMeter || $isPetroPdOnlyOperator) {
            return false;
        }

        $sequence = max(1, $this->extractLastInteger($settlementNo));
        $settlement->work_shift = [
            $this->getDirectSettlementShiftPrefix($business_id) . $sequence,
        ];
        $settlement->save();

        return true;
    }

    /**
     * Public boundary used by PetroDirect payment endpoints before writing to a
     * legacy Direct draft.  It keeps the ownership repair in one conservative
     * place instead of duplicating module-detection rules in every controller.
     */

    public function ensureDirectSettlementOwnership(Settlement $settlement, int $business_id): bool
    {
        return $this->repairLegacyDirectDraftOwnership($settlement, $business_id);
    }


    /**
     * Historical Direct settlements created before the DST marker existed are
     * still valid. This boundary accepts them only after the same prefix,
     * Petro-PD exclusion and isolation checks used by the list page.
     */

    protected function isHistoricalDirectSettlementRecord(int $settlement_id, int $business_id): bool
    {
        if ($settlement_id <= 0 || $business_id <= 0) {
            return false;
        }

        /*
         * IS1871: a PetroDirect-owned DST marker is the strongest ownership
         * signal for records created by the current Direct Settlement flow.
         * Check it before the conservative legacy exclusions: a newly finalized
         * Direct settlement can share historical shift references with Pumper/
         * PetroPD data, which previously made Print and Edit abort with 404.
         */
        $settlement = Settlement::where('business_id', $business_id)
            ->where('id', $settlement_id)
            ->first(['id', 'business_id', 'settlement_no', 'work_shift']);

        if (empty($settlement)) {
            return false;
        }

        $settlementNo = trim((string) $settlement->settlement_no);

        // The synthetic Direct shift marker is the strongest ownership signal.
        // This keeps historical ST.../DST... records readable after the new
        // settlement-number series itself moves to DST... .
        if ($this->isPetroDirectOwnedSettlement($settlement, $business_id)) {
            return true;
        }

        if (! $this->isDirectSettlementNumberCandidate($settlementNo, $business_id)) {
            return false;
        }

        /*
         * Legacy Direct settlements created before the DST marker remain
         * available only after the original conservative separation checks.
         */
        $query = Settlement::where('settlements.business_id', $business_id)
            ->where('settlements.id', $settlement_id)
            ->where('settlements.settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlements.settlement_no', 'NOT LIKE', 'PDST%');
        $this->scopeDirectSettlementNumberSeries($query, $business_id, 'settlements.settlement_no');

        $this->excludePetroPdModuleSettlements($query, $business_id);
        $this->excludeSettlementsWithPetroPdSettledShifts($query, $business_id);
        \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($query);

        return $query->exists();
    }

    protected function findReusableDirectDraftSettlement($business_id, ?string $settlementNo = null, ?int $locationId = null)
    {
        $query = Settlement::where('business_id', $business_id)
            ->where('status', 1)
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%');
        $this->scopeDirectSettlementNumberSeries($query, (int) $business_id);

        $this->scopePetroDirectOwnedSettlements($query, (int) $business_id);
        $this->scopeCurrentDirectSettlementDraftOwner($query, (int) $business_id);

        if (! empty($settlementNo)) {
            $query->where('settlement_no', $settlementNo);
        }

        if (! empty($locationId)) {
            $query->where('location_id', $locationId);
        }

        return $query->orderByDesc('id')->first();
    }


    /**
     * Constructor

     *

     * @param  ProductUtils  $product
     * @return void
     */

    private function getDirectSettlementHiddenPendingPumpOperatorIds($business_id): array
    {
        /*
         * IS1839-PENDING-ISOLATION:
         * Petro Direct is a manual, independent settlement flow. Pending
         * assignments in pump_operator_assignments belong to Petro PD/Pumper
         * Dashboard and must never hide, preload, or otherwise affect a Petro
         * Direct operator. This rule is independent of whether Petro Direct
         * displays a shift number.
         */
        return [];
    }

    /**
     * Direct Settlement operators. Preserve the module's existing active-only
     * behaviour while remaining compatible with older schemas without the flag.
     */

    private function getDirectSettlementPumpOperators($business_id)
    {
        $query = PumpOperator::withoutGlobalScopes()
            ->where('business_id', $business_id);

        if (SchemaCapabilityCache::hasColumn('pump_operators', 'active')) {
            $query->where('active', 1);
        }
        \Modules\PetroDirect\Support\PetroDirectIsolation::excludeOperators($query, 'pump_operators.is_petro_pd_only');

        return $query;
    }

    private function shouldShowPendingShiftPumpOperatorsInDirectSettlement($business_id): bool
    {
        $subscription = Subscription::active_subscription($business_id);
        $package_details = ! empty($subscription) ? $subscription->package_details : [];

        return ! empty($package_details['show_pump_operators_when_shifts_pending_in_settlement']);
    }

    public function mechanicalMeter($id)
    {
        $business_id = (int) (
            request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
        );

        abort_if(empty($business_id), 403, __('messages.unauthorized_action'));
        abort_unless(
            $this->isHistoricalDirectSettlementRecord((int) $id, $business_id),
            404
        );

        $settlement = Settlement::where('business_id', $business_id)
            ->with(['meter_sales.pump'])
            ->findOrFail($id);

        $meter_sales = $settlement->meter_sales;

        return view('petrodirect::settlement.mechanical_meter_modal')
            ->with(compact('settlement', 'meter_sales'));
    }

    /**
     * Display a listing of the resource.







     * @return Response
     */
    /**
     * PetroDirect must remain accessible to businesses/users that have the
     * standalone PetroDirect permission even when the legacy Petro subscription
     * flag is not present. This prevents the Direct Settlement 403 regression.
     */

    private function hasPetroDirectAccess(string $permission = 'petrodirect.settlements.view'): bool
    {
        $businessId = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
        $user = auth()->user();

        return ($businessId > 0 && $this->moduleUtil->hasThePermissionInSubscription($businessId, 'petro_direct_module'))
            || ($user && ($user->can('petrodirect.view') || $user->can($permission)));
    }

    /**
     * Direct Settlement must use its own permission set. The previous action menu
     * depended only on the legacy Petro `settlement.edit` permission and package
     * flags, which hid Edit / Edit No Change even for authorised PetroDirect users.
     */

    private function canEditDirectSettlements(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('petrodirect.settlements.edit')
            || $user->can('settlement.edit');
    }

    /**
     * Load Other Sale products without making PetroDirect depend on a product's
     * optional module tag. Existing installations contain valid non-fuel products
     * that pre-date the `petro_settlements` tag, so the old dropdown could be empty.
     *
     * @return array<int, string>
     */

    private function getDirectSettlementOtherSaleItems(int $businessId, ?int $fuelCategoryId = null): array
    {
        $items = [];

        try {
            $items = $this->transactionUtil->getProductDropDownArray(
                $businessId,
                $fuelCategoryId,
                'petro_settlements'
            );
        } catch (\Throwable $exception) {
            PetroDirectDebug::info('PetroDirect module-filtered Other Sale dropdown failed; using safe fallback.', [
                'business_id' => $businessId,
                'message' => $exception->getMessage(),
            ]);
        }

        $items = collect($items)->filter(function ($label, $id) {
            return (int) $id > 0 && trim((string) $label) !== '';
        })->all();

        $query = Product::query()
            ->where('business_id', $businessId);

        if (SchemaCapabilityCache::hasColumn('products', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (SchemaCapabilityCache::hasColumn('products', 'is_inactive')) {
            $query->where(function ($activeQuery) {
                $activeQuery->whereNull('is_inactive')
                    ->orWhere('is_inactive', 0);
            });
        }

        if (! empty($fuelCategoryId) && SchemaCapabilityCache::hasColumn('products', 'category_id')) {
            $query->where(function ($categoryQuery) use ($fuelCategoryId) {
                $categoryQuery->whereNull('category_id')
                    ->orWhere('category_id', '!=', $fuelCategoryId);
            });
        }

        $fallbackItems = $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->filter(function ($label, $id) {
                return (int) $id > 0 && trim((string) $label) !== '';
            })
            ->all();

        // Preserve stock/module-aware labels where available, while adding every
        // valid non-fuel product that was previously omitted by the module tag.
        $merged = $items + $fallbackItems;
        natcasesort($merged);

        return $merged;
    }

    /**
     * Accept both the application's displayed date and ISO dates, then store a
     * single Y-m-d value. This keeps the List Direct Settlements date identical
     * to the date selected on the settlement form.
     */

    private function normalizeDirectSettlementDate($value, $fallback = null): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            $value = trim((string) $fallback);
        }

        if ($value === '') {
            return now()->format('Y-m-d');
        }

        foreach (['Y-m-d', 'm/d/Y', 'd/m/Y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $exception) {
                // Try the next supported format.
            }
        }

        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    private function getPumpOperatorOtherSaleTotalForSettlement($settlement)
    {
        // IS1761: Pumper Dashboard / PetroPD other-sales are not PetroDirect settlement rows.
        return 0.0;
    }

    private function getPrintPumpOperatorOtherSales($settlement, int $business_id, array $shift_ids = [])
    {
        // IS1761: keep PetroDirect print data independent from Pumper Dashboard / PetroPD shifts.
        return collect();
    }

    private function extractShiftIdsFromSettlement($settlement)
    {
        $workShift = $settlement->work_shift;

        if (empty($workShift)) {
            return [];
        }

        if (is_array($workShift)) {
            return array_values(array_filter($workShift));
        }

        $decoded = json_decode($workShift, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded));
        }

        return [];
    }

    private function normalizeDirectSettlementShiftLabel($value, ?int $business_id = null): ?string
    {
        $prefix = $this->getDirectSettlementShiftPrefix($business_id);

        if (is_array($value)) {
            foreach ($value as $item) {
                $label = $this->normalizeDirectSettlementShiftLabel($item, $business_id);
                if (! empty($label)) {
                    return $label;
                }
            }

            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        for ($i = 0; $i < 3; $i++) {
            if (preg_match('/' . preg_quote($prefix, '/') . '\s*(\d+)/i', $text, $matches)) {
                return $prefix . $matches[1];
            }

            $decoded = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === $text) {
                break;
            }

            if (is_array($decoded)) {
                return $this->normalizeDirectSettlementShiftLabel($decoded, $business_id);
            }

            $text = trim((string) $decoded);
        }

        return null;
    }

    private function getDirectSettlementCreditSales(Settlement $settlement, int $businessId)
    {
        return SettlementCreditSalePayment::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlement) {
                $query->where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', $settlement->id);
            })
            ->with('product')
            ->orderBy('id')
            ->get();
    }

    /** Return the date on which the credit bill must appear in customer reports. */

    private function getCreditSaleReportDate($settlement, $sale): string
    {
        $date = ! empty($sale->order_date)
            ? $sale->order_date
            : $settlement->transaction_date;

        return \Carbon\Carbon::parse($date)->format('Y-m-d');
    }

    /** Build a stable bill number so multiple credit bills never collapse together. */

    private function getCreditSaleBillNumber($settlement, $sale): string
    {
        foreach ([$sale->bill_number ?? null, $sale->order_number ?? null] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && $candidate !== '0' && $candidate !== '-') {
                return $candidate;
            }
        }

        return (string) $settlement->settlement_no . '-CS-' . (int) $sale->id;
    }

    /**
     * Ensure the AR account row and Customer Ledger debit are present exactly once.
     *
     * Pumper Dashboard can create an early ledger row without transaction_id. That
     * orphan row is not usable by the Customers module's transaction-based reports.
     * Finalization now replaces it with one authoritative row linked to the credit
     * sale transaction, preserving the selected order date and bill number.
     */

    private function ensureCreditSaleCustomerAccounting($settlement, $transaction, $sale, bool $skipAccountBooks = false): void
    {
        $amount = max(0, (float) $sale->amount - (float) $sale->total_discount);
        $reportDate = $this->getCreditSaleReportDate($settlement, $sale);
        $operationDate = $reportDate . ' 00:00:00';
        $billNumber = $this->getCreditSaleBillNumber($settlement, $sale);
        $note = 'Credit Sale Bill No: ' . $billNumber
            . ' / Settlement No: ' . $settlement->settlement_no;

        if (! empty($sale->customer_reference)) {
            $note .= ' / Customer Ref: ' . $sale->customer_reference;
        }

        // S677: resolve Accounts Receivable inside the settlement's business.
        // The generic account lookup can resolve an account from another business in
        // multi-business tenants, which makes the credit sale invisible in this
        // business's Finance -> Accounts Receivable account book.
        $accountId = Account::where('business_id', $transaction->business_id)
            ->where('name', 'Accounts Receivable')
            ->where('is_closed', 0)
            ->value('id');

        if (empty($accountId)) {
            $accountId = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        }

        if (! $skipAccountBooks && ! empty($accountId)) {
            // Rebuild only the AR debit for this exact credit transaction. This is
            // idempotent on retries and does not touch stock/sales account rows.
            AccountTransaction::where('transaction_id', $transaction->id)
                ->where('type', 'debit')
                ->where('sub_type', 'ledger_show')
                ->forceDelete();

            AccountTransaction::createAccountTransaction([
                'business_id' => $transaction->business_id,
                'amount' => $amount,
                'account_id' => $accountId,
                'contact_id' => $sale->customer_id,
                'type' => 'debit',
                'sub_type' => 'ledger_show',
                'operation_date' => $operationDate,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => $note,
            ]);
        }

        // Remove the unlinked real-time row created by Pumper Dashboard. Without
        // this cleanup the same bill can appear twice after finalization.
        if (! empty($sale->collection_form_no)) {
            ContactLedger::where('business_id', $transaction->business_id)
                ->where('contact_id', $sale->customer_id)
                ->whereNull('transaction_id')
                ->where('note', 'like', 'Pumper Dashboard Credit Sale%')
                ->where('note', 'like', '%Form No. ' . $sale->collection_form_no . '%')
                ->forceDelete();
        }

        $ledgerData = [
            'business_id' => $transaction->business_id,
            'contact_id' => $sale->customer_id,
            'amount' => $amount,
            'type' => 'debit',
            'sub_type' => 'sell',
            'operation_date' => $operationDate,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => $note,
        ];

        $linkedRows = ContactLedger::where('transaction_id', $transaction->id)
            ->where('contact_id', $sale->customer_id)
            ->where('type', 'debit')
            ->orderBy('id')
            ->get();

        $first = $linkedRows->first();
        if ($first) {
            ContactLedger::where('id', $first->id)->update($ledgerData);

            $duplicateIds = $linkedRows->slice(1)->pluck('id')->filter()->values();
            if ($duplicateIds->isNotEmpty()) {
                ContactLedger::whereIn('id', $duplicateIds)->forceDelete();
            }
        } else {
            ContactLedger::createContactLedger($ledgerData);
        }
    }

    private function getNextAutoShiftNumber($business_id, $location_id = null): string
    {
        $query = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id);

        if (! empty($location_id)) {
            $query->leftJoin('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
                ->where('pumps.location_id', $location_id);
        }

        $last_shift_number = $query
            ->whereNotNull('pump_operator_assignments.shift_number')
            ->where('pump_operator_assignments.shift_number', '!=', '')
            ->orderBy('pump_operator_assignments.id', 'desc')
            ->value('pump_operator_assignments.shift_number');

        if (empty($last_shift_number)) {
            return '1';
        }

        return $this->incrementShiftNumber((string) $last_shift_number);
    }

    private function createAutoIncrementedShiftForSettlement(Request $request): array
    {
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        if (empty($business_id)) {
            throw new \RuntimeException('Unable to identify the current tenant business.');
        }

        /*
         * IS1839-PENDING-ISOLATION:
         * Generate only Petro Direct's synthetic reference. Never create a
         * petro_shifts or pump_operator_assignments row from this module.
         */
        return [
            'shift_id' => 0,
            'shift_number' => $this->getNextDirectSettlementShiftLabel($business_id),
        ];
    }

    private function resolveAutoShiftLocationId($business_id, $pump_operator_id): ?int
    {
        $pump_operator_location_id = PumpOperator::where('business_id', $business_id)
            ->where('id', $pump_operator_id)
            ->value('location_id');

        if (! empty($pump_operator_location_id)) {
            return (int) $pump_operator_location_id;
        }

        $assigned_pump_location_id = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->leftJoin('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
            ->whereNotNull('pumps.location_id')
            ->orderBy('pump_operator_assignments.id', 'desc')
            ->value('pumps.location_id');

        if (! empty($assigned_pump_location_id)) {
            return (int) $assigned_pump_location_id;
        }

        $pump_location_id = Pump::where('business_id', $business_id)
            ->whereNotNull('location_id')
            ->orderBy('id')
            ->value('location_id');

        if (! empty($pump_location_id)) {
            return (int) $pump_location_id;
        }

        $business_location_id = BusinessLocation::where('business_id', $business_id)
            ->orderBy('id')
            ->value('id');

        return ! empty($business_location_id) ? (int) $business_location_id : null;
    }

    private function incrementShiftNumber(string $shift_number): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $shift_number, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];
            $next_number = (string) (((int) $number) + 1);

            if (strlen($number) > 1 && substr($number, 0, 1) === '0') {
                $next_number = str_pad($next_number, strlen($number), '0', STR_PAD_LEFT);
            }

            return $prefix.$next_number;
        }

        return $shift_number.'1';
    }

    /**
     * Remove the specified resource from storage.

     *
     * @return Response
     */

    public function storeManualShiftNumber(Request $request)
    {
        // Direct Settlement shift numbers are no longer user-entered.  Keeping
        // this endpoint blocked prevents a stale browser or direct URL call from
        // reintroducing arbitrary/haphazard shift numbers.
        return response()->json([
            'success' => false,
            'msg' => __('Direct Settlement shift number is generated automatically as DST1, DST2, DST3 ... and cannot be entered manually.'),
        ], 422);
    }

    /**
     * get details for pump id







     * @return Response
     */

    public function getPumpsByLocation(Request $request)
    {
        try {
            $business_id = $this->getCurrentBusinessIdForDirectSettlement();
            $location_id = $request->input('location_id');

            if (empty($business_id)) {
                return [
                    'success' => true,
                    'pumps' => collect(),
                ];
            }

            return [
                'success' => true,
                'pumps' => $this->getAvailableDirectSettlementPumps(
                    (int) $business_id,
                    ! empty($location_id) ? (int) $location_id : null,
                    $request->input('active_settlement_id') ?: $request->input('settlement_no')
                ),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    //     public function getPumps(Request $request, $id)

    //     {

    //         try {

    //             $business_id = request()

    //                 ->session()

    //                 ->get("business.id");
    //  $shift_id = $request->input('work_shift');

    //             $assigned_pumps = PumpOperatorAssignment::where(

    //                 "pump_operator_id",

    //                 $id

    //             )

    //                 ->where("settlement_id", null)
    //  ->where("shift_id", $shift_id) // 👈 add this filter
    //                 ->whereDate("date_and_time", date("Y-m-d"))

    //                 ->pluck("pump_id");

    //             if (!empty($assigned_pumps) && sizeof($assigned_pumps) > 0) {

    //                 $pumps = Pump::where("business_id", $business_id)

    //                     ->whereIn("id", $assigned_pumps)

    //                     ->pluck("pump_name", "id");

    //             } else {

    //                 $pumps = Pump::where("business_id", $business_id)->pluck(

    //                     "pump_name",

    //                     "id"

    //                 );

    //             }

    //             $output = [

    //                 "success" => true,

    //                 "pumps" => $pumps,

    //             ];

    //         } catch (\Exception $e) {

    //             \Log::emergency(

    //                 "File: " .

    //                     $e->getFile() .

    //                     "Line: " .

    //                     $e->getLine() .

    //                     "Message: " .

    //                     $e->getMessage()

    //             );

    //             $output = [

    //                 "success" => false,

    //                 "msg" => __("messages.something_went_wrong"),

    //             ];

    //         }

    //         return $output;

    //     }

    private function extractRequestedSettlementShiftIds(Request $request): array
    {
        // IS1761: PetroDirect's synthetic DST label is not a Pumper/PetroPD database shift ID.
        return [];
    }

    private function draftSettlementMatchesRequestedShifts(Settlement $settlement, array $shift_ids): bool
    {
        if (empty($shift_ids)) {
            return true;
        }

        $linked_shift_ids = MeterSale::where('settlement_no', $settlement->id)
            ->whereNotNull('shift_id')
            ->pluck('shift_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        if (SchemaCapabilityCache::hasColumn('daily_collections', 'shift_id')) {
            $linked_shift_ids = array_merge(
                $linked_shift_ids,
                DailyCollection::where('settlement_id', $settlement->id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray()
            );
        }

        if (SchemaCapabilityCache::hasColumn('pump_operator_other_sales', 'shift_id') && SchemaCapabilityCache::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            $linked_shift_ids = array_merge(
                $linked_shift_ids,
                PumpOperatorOtherSale::where('settlement_no', $settlement->id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray()
            );
        }

        $linked_shift_ids = array_values(array_unique(array_filter($linked_shift_ids, function ($value) {
            return $value !== null && $value !== '';
        })));

        if (empty($linked_shift_ids)) {
            return true;
        }

        return ! empty(array_intersect($shift_ids, $linked_shift_ids));
    }

    /**
     * print resources







     * @param settlement_id







     * @return Response
     */

    private function getDirectSettlementStoreDropdown(int $businessId, int $locationId = 0)
    {
        $allowedStores = Store::forDropdown($businessId, 0, 1, 'sell');

        if ($locationId <= 0 || $allowedStores->isEmpty()) {
            return $allowedStores;
        }

        $locationStores = Store::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereIn('id', $allowedStores->keys()->all())
            ->orderBy('name')
            ->pluck('name', 'id');

        return $locationStores->isNotEmpty() ? $locationStores : $allowedStores;
    }

    /**
     * Resolve the default Direct Settlement store.
     *
     * Main Store is always preferred when it is available for the business/location.
     * If it does not exist, fall back to the business configured default store and
     * finally to the first available store. This keeps Other Sales immediately usable.
     *
     * @param  mixed  $stores
     */

    private function resolveDirectSettlementDefaultStoreId($stores, int $preferredStoreId = 0, int $locationId = 0): ?int
    {
        $options = collect($stores);

        $allowedStoreIds = $options->keys()->map(function ($storeId) {
            return (int) $storeId;
        })->filter()->values();

        $mainStoreId = null;
        if ($allowedStoreIds->isNotEmpty()) {
            $mainStoreQuery = Store::query()
                ->whereIn('id', $allowedStoreIds->all())
                ->whereRaw('LOWER(TRIM(name)) = ?', ['main store']);

            if ($locationId > 0) {
                $mainStoreQuery->where('location_id', $locationId);
            }

            $mainStoreId = $mainStoreQuery->orderBy('id')->value('id');
        }

        if (! empty($mainStoreId)) {
            return (int) $mainStoreId;
        }

        if ($preferredStoreId > 0 && $options->has($preferredStoreId)) {
            return $preferredStoreId;
        }

        $firstStoreId = $options->keys()->first();

        return $firstStoreId !== null ? (int) $firstStoreId : null;
    }

    public function checkPreviousPumpSettlement()
    {

        try {

            /*
             * IS1839-PENDING-ISOLATION:
             * Never inspect the shared Petro PD/Pumper Dashboard assignment
             * queue from Petro Direct. Direct Settlement can proceed with or
             * without its own optional reference number.
             */
            return response()->json(

                [

                    'status' => true,

                    'msg' => 'No previous unsettled shifts found.',

                ],

                200

            );

        } catch (\Exception $e) {

            // Handle unexpected errors

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

            return response()->json($output, 500);

        }

    }

    private function laterPumpSettlementExists(MeterSale $meter_sale): bool
    {
        $pump = Pump::find($meter_sale->pump_id);

        if (! empty($pump) && ! empty($pump->bulk_tank)) {
            return false;
        }

        return MeterSale::where('business_id', $meter_sale->business_id)
            ->petroDirectOwned()
            ->where('pump_id', $meter_sale->pump_id)
            ->where('id', '>', $meter_sale->id)
            ->where('settlement_no', '!=', $meter_sale->settlement_no)
            ->exists();
    }

    public function getMeterSaleTableHtml($settlement_id, array $shift_ids = [])
    {
        $business_id = (int) (
            request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id
        );

        $active_settlement = Settlement::where('business_id', $business_id)
            ->where('work_shift', 'like', '%' . $this->getDirectSettlementShiftPrefix($business_id) . '%')
            ->where('settlement_no', 'not like', 'PDST%')
            ->find($settlement_id);

        if ($active_settlement) {
            // One source for the Direct Meter Sales table AND Settlement Preview.
            // Ignore caller-supplied PetroPD shift ids; Direct owns its DST scope.
            app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
                ->apply($active_settlement, $business_id);
        }
        
        $business = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $currency_precision = !empty($pos_settings['currency_precision']) ? $pos_settings['currency_precision'] : 2;

        $discount_types = [
            '' => __('petrodirect::lang.none'),
            'fixed' => __('petrodirect::lang.fixed'),
            'percentage' => __('petrodirect::lang.percentage'),
        ];

        $already_pumps = $active_settlement
            ? $active_settlement->meter_sales->pluck('pump_id')->filter()->unique()->values()->toArray()
            : [];

        $pump_nos = Pump::where('business_id', $business_id)
            ->whereNotIn('id', $already_pumps)
            ->pluck('pump_name', 'id');

        $meeter_precision = 3;

        // IS1864: this is the standalone Petro Direct screen. Rendering the
        // legacy settlement_pd partial here reintroduced PD/Pumper markup and
        // allowed operational meter rows to appear after an AJAX refresh.
        return view('petrodirect::settlement.partials.meter_sale', compact('active_settlement', 'currency_precision', 'discount_types', 'pump_nos', 'meeter_precision'))->render();
    }

    /**
     * Link meter_sales created from Real Time / payment Enter Meters (settlement_no NULL) to this draft settlement
     * when pump/shift matches an assignment for the settlement's pump operator.
     */

    protected function attachUnsettledRteMeterSalesToSettlement(?Settlement $settlement): void
    {
        // IS1761: never attach unowned Pumper Dashboard / PetroPD meter rows to PetroDirect.
    }

    private function syncRealTimePaymentsToSettlement(Settlement $settlement, array $shift_ids, int $business_id): void
    {
        // IS1761: PetroDirect payments are entered inside PetroDirect; no Pumper/PetroPD import.
    }

    private function updateSettlementTotalAmount($settlement_id)
    {
        $settlement = Settlement::find($settlement_id);
        if (!$settlement) return;

        app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
            ->apply($settlement, (int) $settlement->business_id);

        $meter_sale_total = $settlement->meter_sales
            ->unique(function ($item) {
                return implode('|', [
                    $item->settlement_no,
                    $item->shift_id,
                    $item->pump_id,
                    $item->starting_meter,
                    $item->closing_meter,
                    $item->qty,
                    $item->price,
                    $item->discount,
                    $item->discount_type,
                ]);
            })
            ->sum('discount_amount');
        
        $other_sale_total_raw = $settlement->other_sales->sum('sub_total');
        $other_sale_discount = $settlement->other_sales->sum('discount_amount');
        $other_sale_total = $other_sale_total_raw - $other_sale_discount;
        
        $other_income_total = $settlement->other_incomes->sum('sub_total');
        $customer_payment_total = $settlement->customer_payments->sum('sub_total');
        
        $total_amount = $meter_sale_total + $other_sale_total + $other_income_total + $customer_payment_total;
        
        $settlement->total_amount = $total_amount;
        $settlement->save();
        
        return $total_amount;
    }
}
