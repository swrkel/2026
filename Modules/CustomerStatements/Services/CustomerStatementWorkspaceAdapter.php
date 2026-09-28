<?php

namespace Modules\CustomerStatements\Services;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CustomerStatementWorkspaceAdapter
{
    /**
     * Render the tested Customer Statement workspace owned by the Customers
     * module. The standalone module only adds narrowly scoped route/data fixes.
     */
    public function render(Request $request)
    {
        $controllerClass = \Modules\Customers\Http\Controllers\CustomerStandaloneStatementController::class;

        if (! class_exists($controllerClass)) {
            throw new RuntimeException(
                'Customer Statements requires the Customers module controller. '
                . 'Enable the Customers module and clear the application cache.'
            );
        }

        if (! view()->exists('customers::customer_statement.index')) {
            $customerViews = module_path('Customers', 'Resources/views');
            if (is_dir($customerViews)) {
                view()->addNamespace('customers', $customerViews);
            }
        }

        if (! view()->exists('customers::customer_statement.index')) {
            throw new RuntimeException(
                'The complete Customer Statement workspace view is missing from '
                . 'Modules/Customers/Resources/views/customer_statement/index.blade.php.'
            );
        }

        $result = app($controllerClass)->index($request);

        return $this->attachWorkspaceFixes($result, $request);
    }

    /** @param mixed $result */
    private function attachWorkspaceFixes($result, Request $request)
    {
        $fixes = '';

        if (view()->exists('customerstatements::partials.is1790_fixes')) {
            $fixes .= view('customerstatements::partials.is1790_fixes')->render();
        }

        if (view()->exists('customerstatements::partials.s564_workspace_fixes')) {
            $fixes .= view('customerstatements::partials.s564_workspace_fixes', [
                'customerStatementActiveSection' => $this->activeSection($request),
                'customerStatementNumberingShowUrl' => route('customerstatements.numbering-settings.show'),
                'customerStatementNumberingStoreUrl' => route('customerstatements.numbering-settings.store'),
                'customerStatementNextNumberUrl' => route('customerstatements.numbering-settings.next'),
                'customerStatementStoreUrl' => route('customerstatements.data.statements.store'),
                'customerStatementBillListUrl' => route('customerstatements.data.bills.list'),
                'customerStatementListUrl' => route('customerstatements.data.statements.list'),
                'customerStatementSettingsListUrl' => route('customerstatements.data.numbering-settings.list'),
            ])->render();
        }

        /*
         * S633: a User without the Customers 'settings' permission must not be
         * shown tabs whose data endpoints will answer 403 - the workspace was
         * raising a DataTables alert() and two "Failed / Unauthorized action."
         * toasts on page load.
         *
         * The decision is made here, on the server, using the same resolver the
         * routes and the sidebar use, so the page cannot disagree with the
         * middleware. Attached through the existing fixes mechanism rather than
         * by editing the shared Customers view.
         */
        if (view()->exists('customerstatements::partials.s633_permission_ui')) {
            $fixes .= view('customerstatements::partials.s633_permission_ui', [
                'canSeeSettings' => $this->userCanSeeStatementSettings(),
            ])->render();
        }

        /*
         * LA-1166: the Action menu on the statement list was clipped by the
         * theme's overflow:hidden on .box and .table, so only the last entry -
         * Delete - was visible. The menu is lifted out of those containers while
         * it is open. Attached here, through the same mechanism as the fixes
         * above, rather than by editing the shared Customers view.
         */
        if (view()->exists('customerstatements::partials.la1166_action_menu')) {
            $fixes .= view('customerstatements::partials.la1166_action_menu')->render();
        }

        if ($fixes === '') {
            return $result;
        }

        if ($result instanceof ViewContract) {
            return response($this->injectBeforeBodyClose($result->render(), $fixes));
        }

        if ($result instanceof SymfonyResponse) {
            $content = $result->getContent();
            if (is_string($content) && $content !== '') {
                $result->setContent($this->injectBeforeBodyClose($content, $fixes));
            }
        }

        return $result;
    }

    /**
     * S633: does the signed-in user hold the Customers 'settings' permission?
     *
     * Resolved through CustomerPermissionService, the same class the route
     * middleware calls, so what the page renders and what the endpoints allow
     * can never drift apart.
     *
     * Any failure to resolve is treated as "no" on purpose: hiding a tab from
     * someone who was entitled to it is a visible, reportable annoyance, while
     * showing one that then answers 403 is the fault being fixed here.
     */
    private function userCanSeeStatementSettings(): bool
    {
        $serviceClass = \Modules\Customers\Services\CustomerPermissionService::class;

        if (! class_exists($serviceClass)) {
            return false;
        }

        try {
            return (bool) app($serviceClass)->allows('settings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function activeSection(Request $request): string
    {
        $querySection = strtolower((string) $request->query('section', ''));
        $allowed = [
            'customer',
            'list',
            'statement-settings',
            'numbering-settings',
            'print-formats',
            'alert-settings',
        ];

        if (in_array($querySection, $allowed, true)) {
            return $querySection;
        }

        $routeName = (string) optional($request->route())->getName();

        if (str_ends_with($routeName, '.list')) {
            return 'list';
        }

        if (str_ends_with($routeName, '.statement-settings')) {
            return 'statement-settings';
        }

        if (str_ends_with($routeName, '.settings')) {
            return 'numbering-settings';
        }

        // The legacy workspace labelled the old payment pane as Statement Print
        // Formats. Treat every old font/payment URL as the print-format page.
        if (
            str_ends_with($routeName, '.payments')
            || str_ends_with($routeName, '.font-settings')
            || str_ends_with($routeName, '.print-formats')
        ) {
            return 'print-formats';
        }

        if (str_ends_with($routeName, '.alert-settings')) {
            return 'alert-settings';
        }

        return 'customer';
    }

    private function injectBeforeBodyClose(string $html, string $fixes): string
    {
        if (
            str_contains($html, 'id="customer-statements-s569-runtime"')
            || str_contains($html, 'id="customer-statements-s564-fixes"')
        ) {
            return $html;
        }

        $bodyPosition = strripos($html, '</body>');
        if ($bodyPosition === false) {
            return $html . $fixes;
        }

        return substr($html, 0, $bodyPosition)
            . $fixes
            . substr($html, $bodyPosition);
    }
}
