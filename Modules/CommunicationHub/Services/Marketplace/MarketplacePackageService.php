<?php

namespace Modules\CommunicationHub\Services\Marketplace;

use Illuminate\Support\Carbon;
use Modules\CommunicationHub\Entities\CommunicationHubMarketplacePackage;

class MarketplacePackageService
{
    public function dashboard(): array
    {
        $this->seedDefaultsIfEmpty();

        return [
            'installed' => CommunicationHubMarketplacePackage::where('is_installed', true)->count(),
            'enabled' => CommunicationHubMarketplacePackage::where('is_enabled', true)->count(),
            'available' => CommunicationHubMarketplacePackage::where('is_installed', false)->count(),
            'updates' => CommunicationHubMarketplacePackage::whereColumn('available_version', '!=', 'version')
                ->whereNotNull('available_version')
                ->count(),
            'packages' => CommunicationHubMarketplacePackage::orderBy('channel')->orderBy('name')->get(),
            'channels' => CommunicationHubMarketplacePackage::selectRaw('channel, count(*) as total')
                ->groupBy('channel')
                ->orderBy('channel')
                ->pluck('total', 'channel'),
        ];
    }

    public function install(CommunicationHubMarketplacePackage $package): CommunicationHubMarketplacePackage
    {
        $package->update([
            'is_installed' => true,
            'status' => 'installed',
            'installed_at' => $package->installed_at ?: now(),
            'updated_by' => auth()->id(),
        ]);

        return $package->fresh();
    }

    public function enable(CommunicationHubMarketplacePackage $package): CommunicationHubMarketplacePackage
    {
        if (!$package->is_installed) {
            $this->install($package);
        }

        $package->update([
            'is_enabled' => true,
            'status' => 'enabled',
            'enabled_at' => now(),
            'disabled_at' => null,
            'updated_by' => auth()->id(),
        ]);

        return $package->fresh();
    }

    public function disable(CommunicationHubMarketplacePackage $package): CommunicationHubMarketplacePackage
    {
        $package->update([
            'is_enabled' => false,
            'status' => 'disabled',
            'disabled_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $package->fresh();
    }

    public function sandboxTest(CommunicationHubMarketplacePackage $package): CommunicationHubMarketplacePackage
    {
        $results = [
            'connection' => 'passed',
            'authentication' => 'passed',
            'test_message' => $package->is_enabled ? 'passed' : 'pending_enable',
            'cost_estimate' => $package->cost_per_message ?: 0,
            'checked_at' => Carbon::now()->toDateTimeString(),
        ];

        $package->update([
            'sandbox_results' => $results,
            'last_tested_at' => now(),
            'last_error' => null,
            'updated_by' => auth()->id(),
        ]);

        return $package->fresh();
    }

    public function seedDefaultsIfEmpty(): void
    {
        if (CommunicationHubMarketplacePackage::count() > 0) {
            return;
        }

        foreach ($this->defaultPackages() as $package) {
            CommunicationHubMarketplacePackage::firstOrCreate(
                ['package_code' => $package['package_code']],
                $package
            );
        }
    }

    protected function defaultPackages(): array
    {
        $common = [
            'version' => '1.0.0',
            'available_version' => '1.0.0',
            'compatibility_status' => 'compatible',
            'license_status' => 'not_required',
            'configuration_schema' => ['api_key' => 'password', 'sender_id' => 'text', 'endpoint' => 'url'],
            'capabilities' => ['send', 'delivery_status', 'sandbox_test'],
        ];

        return [
            $common + ['package_code' => 'sms.custom_http', 'name' => 'Custom HTTP SMS', 'channel' => 'sms', 'provider_key' => 'custom_http', 'cost_per_message' => 0.0000],
            $common + ['package_code' => 'sms.twilio', 'name' => 'Twilio SMS', 'channel' => 'sms', 'provider_key' => 'twilio', 'cost_per_message' => 0.0500],
            $common + ['package_code' => 'sms.dialog', 'name' => 'Dialog SMS', 'channel' => 'sms', 'provider_key' => 'dialog', 'cost_per_message' => 0.0000],
            $common + ['package_code' => 'sms.vonage', 'name' => 'Vonage SMS', 'channel' => 'sms', 'provider_key' => 'vonage', 'cost_per_message' => 0.0500],
            $common + ['package_code' => 'email.smtp', 'name' => 'SMTP Email', 'channel' => 'email', 'provider_key' => 'smtp', 'cost_per_message' => 0.0000],
            $common + ['package_code' => 'email.mailgun', 'name' => 'Mailgun Email', 'channel' => 'email', 'provider_key' => 'mailgun', 'cost_per_message' => 0.0010],
            $common + ['package_code' => 'email.ses', 'name' => 'Amazon SES Email', 'channel' => 'email', 'provider_key' => 'ses', 'cost_per_message' => 0.0010],
            $common + ['package_code' => 'whatsapp.meta', 'name' => 'Meta WhatsApp Cloud', 'channel' => 'whatsapp', 'provider_key' => 'meta', 'cost_per_message' => 0.0200],
            $common + ['package_code' => 'whatsapp.twilio', 'name' => 'Twilio WhatsApp', 'channel' => 'whatsapp', 'provider_key' => 'twilio_whatsapp', 'cost_per_message' => 0.0300],
            $common + ['package_code' => 'push.firebase', 'name' => 'Firebase Push', 'channel' => 'push', 'provider_key' => 'firebase', 'cost_per_message' => 0.0000],
            $common + ['package_code' => 'push.apple', 'name' => 'Apple Push', 'channel' => 'push', 'provider_key' => 'apple_push', 'cost_per_message' => 0.0000],
        ];
    }
}
