<?php

namespace Modules\MyHealthMembers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class MyHealthSidebarInjector
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!auth()->check()) {
            return $response;
        }

        if (!$this->isHtmlResponse($response)) {
            return $response;
        }

        if (!$this->sidebarPermissionEnabled()) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '' || strpos($content, 'myhealthmembers-sidebar-menu') !== false) {
            return $response;
        }

        if (strpos($content, 'sidebar-menu') === false && strpos($content, 'main-sidebar') === false) {
            return $response;
        }

        try {
            $sidebarHtml = View::make('myhealthmembers::partials.sidebar')->render();
        } catch (\Throwable $e) {
            return $response;
        }

        if (trim($sidebarHtml) === '') {
            return $response;
        }

        $updated = $this->insertIntoSidebar($content, $sidebarHtml);
        if ($updated !== $content) {
            $response->setContent($updated);
        }

        return $response;
    }

    protected function sidebarPermissionEnabled(): bool
    {
        try {
            $businessId = session('user.business_id') ?? session('business.id') ?? optional(auth()->user())->business_id;
            if (empty($businessId)) {
                return false;
            }

            $subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($businessId);
            if (empty($subscription)) {
                $subscription = \Modules\Superadmin\Entities\Subscription::where('business_id', $businessId)->orderByDesc('id')->first();
            }

            $details = !empty($subscription) ? $subscription->package_details : [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?: [];
            }
            if (is_object($details)) {
                $details = json_decode(json_encode($details), true) ?: [];
            }

            return !empty($details['my_health_module']) || !empty($details['myhealth_module']) || !empty($details['myhealthmembers_module']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function isHtmlResponse(Response $response): bool
    {
        if (!method_exists($response, 'headers') && !property_exists($response, 'headers')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return false;
        }

        return method_exists($response, 'getContent');
    }

    protected function insertIntoSidebar(string $content, string $sidebarHtml): string
    {
        $patterns = [
            '/(<ul[^>]*class=["\'][^"\']*sidebar-menu[^"\']*["\'][^>]*>)/i',
            '/(<ul[^>]*class=["\'][^"\']*nav-sidebar[^"\']*["\'][^>]*>)/i',
            '/(<ul[^>]*class=["\'][^"\']*main-sidebar-menu[^"\']*["\'][^>]*>)/i',
        ];

        foreach ($patterns as $pattern) {
            $newContent = preg_replace($pattern, '$1' . "\n" . $sidebarHtml . "\n", $content, 1, $count);
            if ($count > 0 && is_string($newContent)) {
                return $newContent;
            }
        }

        return $content;
    }
}
