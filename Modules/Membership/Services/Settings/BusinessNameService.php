<?php

namespace Modules\Membership\Services\Settings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Membership\Entities\MembershipBusinessName;
use Modules\Membership\Entities\MembershipBusinessType;

class BusinessNameService
{
    public function businessTypeOptions(int $businessId): Collection
    {
        return MembershipBusinessType::whereIn('business_id', [0, $businessId])
            ->orderByRaw('business_id = 0 DESC')
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower((string) $type->business_type);
            })
            ->pluck('business_type', 'id');
    }

    public function queryForBusiness(int $businessId)
    {
        return MembershipBusinessName::where('business_id', $businessId)
            ->with(['businessType:id,business_type', 'createdBy:id,username,first_name,last_name'])
            ->select('id', 'business_id', 'membership_business_type_id', 'business_name', 'created_by', 'created_at');
    }

    public function findForBusiness(int $id, int $businessId): MembershipBusinessName
    {
        return MembershipBusinessName::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();
    }

    public function createMany(int $businessId, int $typeId, array $names, int $createdBy): array
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));

        if (empty($names)) {
            return ['created' => 0, 'skipped' => 0, 'empty' => true];
        }

        $existingNames = MembershipBusinessName::where('business_id', $businessId)
            ->whereIn(DB::raw('LOWER(business_name)'), array_map('mb_strtolower', $names))
            ->pluck('business_name')
            ->map(function ($name) {
                return mb_strtolower((string) $name);
            })
            ->toArray();

        $rows = [];
        $skipped = 0;
        $now = now();

        foreach ($names as $name) {
            if (in_array(mb_strtolower($name), $existingNames, true)) {
                $skipped++;
                continue;
            }

            $rows[] = [
                'business_id' => $businessId,
                'membership_business_type_id' => $typeId,
                'business_name' => $name,
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            MembershipBusinessName::insert($rows);
        }

        return ['created' => count($rows), 'skipped' => $skipped, 'empty' => false];
    }

    public function updateWithAdditionalNames(int $id, int $businessId, int $typeId, string $primaryName, array $additionalNames, int $createdBy): array
    {
        $businessName = $this->findForBusiness($id, $businessId);
        $primaryName = trim($primaryName);

        $duplicateExists = MembershipBusinessName::where('business_id', $businessId)
            ->where('id', '!=', $id)
            ->whereRaw('LOWER(business_name) = ?', [mb_strtolower($primaryName)])
            ->exists();

        if ($duplicateExists) {
            return ['updated' => false, 'duplicate' => true, 'created' => 0, 'skipped' => 0];
        }

        $businessName->update([
            'membership_business_type_id' => $typeId,
            'business_name' => $primaryName,
        ]);

        $created = 0;
        $skipped = 0;
        $additionalNames = array_values(array_unique(array_filter(array_map('trim', $additionalNames))));

        foreach ($additionalNames as $name) {
            $exists = MembershipBusinessName::where('business_id', $businessId)
                ->whereRaw('LOWER(business_name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            MembershipBusinessName::create([
                'business_id' => $businessId,
                'membership_business_type_id' => $typeId,
                'business_name' => $name,
                'created_by' => $createdBy,
            ]);
            $created++;
        }

        return ['updated' => true, 'duplicate' => false, 'created' => $created, 'skipped' => $skipped];
    }
}
