<?php

namespace Modules\AutoService\Services\Central;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicle;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleOwner;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleVerification;

class CentralVehicleRegistryService
{
    public function registerVehicle(array $data, ?int $businessId = null, ?string $tenantKey = null): AutoServiceCentralVehicle
    {
        $connection = config('autoservice.central_connection', config('database.default'));

        return DB::connection($connection)->transaction(function () use ($data, $businessId, $tenantKey) {
            $vehicle = AutoServiceCentralVehicle::where(function ($q) use ($data) {
                    if (!empty($data['registration_no'])) { $q->orWhere('registration_no', $data['registration_no']); }
                    if (!empty($data['vin'])) { $q->orWhere('vin', $data['vin']); }
                    if (!empty($data['chassis_no'])) { $q->orWhere('chassis_no', $data['chassis_no']); }
                })
                ->first();

            if (!$vehicle) {
                $vehicle = AutoServiceCentralVehicle::create([
                    'registration_no' => $data['registration_no'] ?? null,
                    'vin' => $data['vin'] ?? null,
                    'chassis_no' => $data['chassis_no'] ?? null,
                    'engine_no' => $data['engine_no'] ?? null,
                    'make' => $data['make'] ?? null,
                    'model' => $data['model'] ?? null,
                    'variant' => $data['variant'] ?? null,
                    'year' => $data['year'] ?? null,
                    'colour' => $data['colour'] ?? null,
                    'fuel_type' => $data['fuel_type'] ?? null,
                    'transmission' => $data['transmission'] ?? null,
                    'created_by_business_id' => $businessId,
                    'created_by_tenant' => $tenantKey,
                    'status' => 'active',
                ]);
            } else {
                $vehicle->fill(array_filter([
                    'engine_no' => $data['engine_no'] ?? null,
                    'make' => $data['make'] ?? null,
                    'model' => $data['model'] ?? null,
                    'variant' => $data['variant'] ?? null,
                    'year' => $data['year'] ?? null,
                    'colour' => $data['colour'] ?? null,
                    'fuel_type' => $data['fuel_type'] ?? null,
                    'transmission' => $data['transmission'] ?? null,
                ], function ($v) { return $v !== null && $v !== ''; }));
                $vehicle->save();
            }

            AutoServiceCentralVehicleOwner::where('central_vehicle_id', $vehicle->id)->update([
                'is_current_owner' => 0,
                'owned_to' => now()->toDateString(),
            ]);

            $owner = AutoServiceCentralVehicleOwner::create([
                'central_vehicle_id' => $vehicle->id,
                'owner_name' => $data['owner_name'] ?? null,
                'mobile' => $this->normalizeMobile($data['mobile'] ?? ''),
                'email' => $data['email'] ?? null,
                'nic_no' => $data['nic_no'] ?? null,
                'address' => $data['address'] ?? null,
                'owned_from' => $data['owned_from'] ?? now()->toDateString(),
                'is_current_owner' => 1,
                'is_verified' => 0,
                'created_by_business_id' => $businessId,
                'created_by_tenant' => $tenantKey,
            ]);

            $vehicle->last_registered_owner_id = $owner->id;
            $vehicle->save();

            $this->createOtp($vehicle->id, $owner->id, $owner->mobile, 'owner_registration');

            return $vehicle->fresh(['currentOwner']);
        });
    }

    public function findVehicleForLogin(string $keyword): ?AutoServiceCentralVehicle
    {
        $like = '%' . trim($keyword) . '%';
        return AutoServiceCentralVehicle::with('currentOwner')
            ->where('registration_no', 'like', $like)
            ->orWhere('vin', 'like', $like)
            ->orWhere('chassis_no', 'like', $like)
            ->orWhere('engine_no', 'like', $like)
            ->orWhereIn('id', function ($q) use ($like) {
                $q->select('central_vehicle_id')->from('auto_service_central_vehicle_owners')
                    ->where('mobile', 'like', $like)
                    ->where('is_current_owner', 1);
            })
            ->orderByDesc('id')
            ->first();
    }

    public function createOtp(?int $vehicleId, ?int $ownerId, string $mobile, string $purpose = 'vehicle_login'): string
    {
        $mobile = $this->normalizeMobile($mobile);
        $otp = (string) random_int(100000, 999999);

        AutoServiceCentralVehicleVerification::create([
            'central_vehicle_id' => $vehicleId,
            'central_owner_id' => $ownerId,
            'mobile' => $mobile,
            'otp_hash' => Hash::make($otp),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes((int) config('autoservice.central_vehicle_otp_minutes', 10)),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);

        // Hook point for the existing ERP SMS utility. We do not hard-depend on it so this module remains standalone.
        if (function_exists('send_sms')) {
            @send_sms($mobile, 'Your Auto Service vehicle verification code is ' . $otp);
        }

        return $otp;
    }

    public function verifyOtp(string $mobile, string $otp, ?int $vehicleId = null): bool
    {
        $mobile = $this->normalizeMobile($mobile);
        $record = AutoServiceCentralVehicleVerification::where('mobile', $mobile)
            ->when($vehicleId, function ($q) use ($vehicleId) { $q->where('central_vehicle_id', $vehicleId); })
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->first();

        if (!$record) { return false; }

        $record->attempts = ((int) $record->attempts) + 1;
        $record->save();

        if (!Hash::check($otp, $record->otp_hash)) { return false; }

        $record->verified_at = now();
        $record->save();

        if ($record->central_owner_id) {
            AutoServiceCentralVehicleOwner::where('id', $record->central_owner_id)->update([
                'is_verified' => 1,
                'verified_at' => now(),
            ]);
        }

        return true;
    }

    public function normalizeMobile(string $mobile): string
    {
        return preg_replace('/[^0-9+]/', '', trim($mobile));
    }
}
