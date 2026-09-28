<?php

namespace Modules\CommunicationHub\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Modules\CommunicationHub\Services\Audit\CommunicationHubAuditService;
use Modules\CommunicationHub\Services\Delivery\DeliveryTracker;
use Modules\CommunicationHub\Services\Messaging\EnterpriseMessagingEngine;
use Modules\CommunicationHub\Services\Providers\ChannelProviderManager;
use Modules\CommunicationHub\Services\Providers\ProviderDriverRegistry;
use Modules\CommunicationHub\Services\Providers\ProviderHealthService;
use Modules\CommunicationHub\Services\Routing\IntelligentRoutingService;
use Modules\CommunicationHub\Services\Cost\CommunicationCostEstimator;
use Modules\CommunicationHub\Services\Queue\MessageQueueService;
use Modules\CommunicationHub\Services\Queue\StandaloneQueueProcessor;
use Modules\CommunicationHub\Services\Reports\CommunicationHubReportService;
use Modules\CommunicationHub\Services\Settings\CommunicationHubSettingsService;
use Modules\CommunicationHub\Contracts\CommunicationHubWalletConnectorInterface;
use Modules\CommunicationHub\Services\Wallet\NullWalletConnector;
use Modules\CommunicationHub\Services\Wallet\WalletChargeService;
use Modules\CommunicationHub\Services\Support\CommunicationHubSidebarRegistrar;
use Modules\CommunicationHub\Services\Template\StandaloneTemplateRenderer;
use Modules\CommunicationHub\Services\Marketplace\MarketplacePackageService;

class CommunicationHubServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CommunicationHub';
    protected string $moduleNameLower = 'communicationhub';

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->registerSidebarMenu();

        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
    }

    public function register(): void
    {
        // Register routes from the primary provider as well. Some host ERP/module
        // loaders only bootstrap the first provider listed by the module. Laravel
        // safely ignores a second registration when RouteServiceProvider was
        // already loaded from module.json.
        $this->app->register(RouteServiceProvider::class);

        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/menu.php'), $this->moduleNameLower . '_menu');
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/permissions.php'), $this->moduleNameLower . '_permissions');

        $this->app->singleton(ProviderDriverRegistry::class, fn () => new ProviderDriverRegistry());
        $this->app->singleton(IntelligentRoutingService::class, fn () => new IntelligentRoutingService());
        $this->app->singleton(CommunicationCostEstimator::class, fn () => new CommunicationCostEstimator());
        $this->app->singleton(ProviderHealthService::class, fn ($app) => new ProviderHealthService($app->make(ProviderDriverRegistry::class)));
        $this->app->singleton(ChannelProviderManager::class, fn ($app) => new ChannelProviderManager($app->make(ProviderDriverRegistry::class), $app->make(IntelligentRoutingService::class), $app->make(CommunicationCostEstimator::class)));
        $this->app->singleton(StandaloneTemplateRenderer::class, fn () => new StandaloneTemplateRenderer());
        $this->app->singleton(DeliveryTracker::class, fn () => new DeliveryTracker());
        $this->app->singleton(CommunicationHubAuditService::class, fn () => new CommunicationHubAuditService());
        $this->app->singleton(MessageQueueService::class, fn ($app) => new MessageQueueService($app->make(StandaloneTemplateRenderer::class), $app->make(CommunicationHubAuditService::class), $app->make(CommunicationCostEstimator::class), $app->make(\App\Services\Messaging\GlobalWhatsAppService::class), $app->make(\App\Services\Messaging\GlobalEmailService::class)));
        $this->app->singleton(StandaloneQueueProcessor::class, fn ($app) => new StandaloneQueueProcessor($app->make(ChannelProviderManager::class), $app->make(DeliveryTracker::class), $app->make(CommunicationHubAuditService::class), $app->make(WalletChargeService::class), $app->make(\App\Services\Messaging\GlobalWhatsAppService::class), $app->make(\App\Services\Messaging\GlobalEmailService::class)));
        $this->app->singleton(EnterpriseMessagingEngine::class, fn ($app) => new EnterpriseMessagingEngine($app->make(MessageQueueService::class), $app->make(StandaloneQueueProcessor::class), $app->make(CommunicationHubAuditService::class)));
        $this->app->singleton(CommunicationHubReportService::class, fn () => new CommunicationHubReportService());
        $this->app->singleton(CommunicationHubSettingsService::class, fn () => new CommunicationHubSettingsService());
        $this->app->singleton(CommunicationHubWalletConnectorInterface::class, fn () => new NullWalletConnector());
        $this->app->singleton(WalletChargeService::class, fn ($app) => new WalletChargeService($app->make(CommunicationHubWalletConnectorInterface::class), $app->make(CommunicationHubSettingsService::class)));
        $this->app->singleton(MarketplacePackageService::class, fn () => new MarketplacePackageService());
    }

    protected function registerSidebarMenu(): void
    {
        Blade::include($this->moduleNameLower . '::partials.sidebar', 'communicationHubSidebar');

        $menu = CommunicationHubSidebarRegistrar::menu();
        $partial = CommunicationHubSidebarRegistrar::sidebarPartial();

        View::share('communicationHubMenu', $menu);
        View::share('communicationhubMenu', $menu);
        View::share('communicationHubSidebarPartial', $partial);

        View::composer('*', function ($view) use ($menu, $partial) {
            $moduleMenus = $view->offsetExists('module_menus') ? (array) $view->offsetGet('module_menus') : [];
            $moduleMenus['communicationhub'] = $menu;

            $partials = $view->offsetExists('module_sidebar_partials') ? (array) $view->offsetGet('module_sidebar_partials') : [];
            $partials['communicationhub'] = $partial;

            $view->with('communicationHubMenu', $menu);
            $view->with('communicationhubMenu', $menu);
            $view->with('communicationHubSidebarPartial', $partial);
            $view->with('module_menus', $moduleMenus);
            $view->with('module_sidebar_partials', $partials);
        });
    }
}
