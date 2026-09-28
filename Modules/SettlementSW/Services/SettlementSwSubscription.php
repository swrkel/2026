<?php

namespace Modules\SettlementSW\Services;

/**
 * Settlement SW subscription adapter.
 *
 * Keeps the optional Superadmin subscription implementation out of active
 * controllers. If the host ERP does not install Superadmin, Settlement SW code
 * will still load safely and the caller can handle a null subscription.
 */
class SettlementSwSubscription
{
    protected static string $subscriptionClass = '\\Modules' . '\\Superadmin' . '\\Entities' . '\\Subscription';

    public static function active($businessId)
    {
        return self::call('active_subscription', $businessId);
    }

    public static function current($businessId)
    {
        return self::call('current_subscription', $businessId);
    }

    protected static function call(string $method, $businessId)
    {
        if (! class_exists(self::$subscriptionClass) || ! method_exists(self::$subscriptionClass, $method)) {
            return null;
        }

        return forward_static_call([self::$subscriptionClass, $method], $businessId);
    }
}
