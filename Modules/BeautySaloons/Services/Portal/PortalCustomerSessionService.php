<?php

namespace Modules\BeautySaloons\Services\Portal;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Modules\BeautySaloons\Entities\BeautyCustomer;

class PortalCustomerSessionService
{
    public const SESSION_KEY = 'beauty_saloons_portal_customer_id';

    public function attempt(string $mobileOrEmail, string $password): bool
    {
        $customer = BeautyCustomer::query()
            ->where('mobile', $mobileOrEmail)
            ->orWhere('email', $mobileOrEmail)
            ->first();

        if (! $customer) {
            return false;
        }

        $hash = $customer->portal_password ?? $customer->password ?? null;
        if (! $hash || ! Hash::check($password, $hash)) {
            return false;
        }

        Session::put(self::SESSION_KEY, $customer->id);
        Session::regenerate();

        return true;
    }

    public function logout(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function customer(): ?BeautyCustomer
    {
        $id = Session::get(self::SESSION_KEY);
        return $id ? BeautyCustomer::find($id) : null;
    }
}
