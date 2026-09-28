<?php
namespace Modules\AirlineTicketingNew\Services\Portal;

use Illuminate\Support\Facades\Hash;
use Modules\AirlineTicketingNew\Entities\PortalUser;

class CustomerPortalService
{
    public function createUser(array $data): PortalUser
    {
        return PortalUser::query()->create([
            'business_id' => $data['business_id'],
            'passenger_id' => $data['passenger_id'] ?? null,
            'corporate_customer_id' => $data['corporate_customer_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'portal_type' => $data['portal_type'] ?? 'customer',
            'is_active' => true,
        ]);
    }

    public function dashboard(int $businessId, PortalUser $user): array
    {
        $passengerId = $user->passenger_id;

        return [
            'reservations' => (int) \DB::table('atn_reservations')
                ->where('business_id', $businessId)
                ->when($passengerId, fn ($q) => $q->where('passenger_id', $passengerId))
                ->count(),
            'tickets' => (int) \DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->when($passengerId, fn ($q) => $q->where('passenger_id', $passengerId))
                ->count(),
            'outstanding' => (float) \DB::table('atn_invoices')
                ->where('business_id', $businessId)
                ->when($passengerId, fn ($q) => $q->where('passenger_id', $passengerId))
                ->sum('due_total'),
        ];
    }
}
