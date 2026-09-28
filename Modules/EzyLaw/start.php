<?php
/* EzyLaw standalone bootstrap. Keeps module/provider registration inside EzyLaw. */
if (! app()->bound('ezylaw.service_provider_registered') && class_exists(\Modules\EzyLaw\Providers\EzyLawServiceProvider::class)) {
    app()->instance('ezylaw.service_provider_registered', true);
    if (! app()->getProvider(\Modules\EzyLaw\Providers\EzyLawServiceProvider::class)) {
        app()->register(\Modules\EzyLaw\Providers\EzyLawServiceProvider::class);
    }
}
if (! app()->bound('ezylaw.route_provider_registered') && class_exists(\Modules\EzyLaw\Providers\RouteServiceProvider::class)) {
    app()->instance('ezylaw.route_provider_registered', true);
    if (! app()->getProvider(\Modules\EzyLaw\Providers\RouteServiceProvider::class)) {
        app()->register(\Modules\EzyLaw\Providers\RouteServiceProvider::class);
    }
}
