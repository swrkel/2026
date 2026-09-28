<?php
/**
 * ZIP 307 - PetroPD PD Operators Action Buttons Fix
 *
 * This hotfix patches Modules/PetroPD/Http/Controllers/PDOperatorController.php
 * so the PD Operators DataTable action dropdown shows the full set of action
 * buttons like Contact/Customers, without relying on Petro module routes.
 *
 * Run from Laravel project root:
 * php Modules/PetroPD/Hotfixes/ApplyZip307PetroPdOperatorActionsFix.php
 */

$root = dirname(__DIR__, 3);
$controller = $root . '/Modules/PetroPD/Http/Controllers/PDOperatorController.php';

if (!file_exists($controller)) {
    fwrite(STDERR, "PDOperatorController.php not found at: {$controller}\n");
    exit(1);
}

$contents = file_get_contents($controller);
if ($contents === false) {
    fwrite(STDERR, "Unable to read: {$controller}\n");
    exit(1);
}

$backup = $controller . '.backup_zip307_' . date('Ymd_His');
if (!copy($controller, $backup)) {
    fwrite(STDERR, "Unable to create backup: {$backup}\n");
    exit(1);
}

$newActionBlock = <<<'PHP_CODE'
->addColumn(
                        'action',
                        function ($row) {
                            /*
                             * ZIP 307 - PetroPD standalone PD Operators action dropdown.
                             * Purpose:
                             * - Match Contact/Customers style: one Actions dropdown with all useful buttons.
                             * - Do not hide paid/recover/ledger actions only because subscription flags are not returned correctly.
                             * - Do not call Petro module controller actions.
                             * - Use PetroPD URLs only so the module remains standalone.
                             */
                            $operatorId = (int) ($row->pump_operator_id ?? $row->id);
                            $isActive = !empty($row->active);

                            $html = '<div class="btn-group petropd-actions-dropdown">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                                    e(__('messages.actions')) .
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId) . '">
                                        <i class="fa fa-eye" aria-hidden="true"></i> ' . e(__('messages.view')) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '/edit') . '" class="btn-modal" data-container=".view_modal">
                                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' . e(__('messages.edit')) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '/toggle-active') . '" class="toggle_active_button">
                                        <i class="fa ' . ($isActive ? 'fa-times' : 'fa-check') . '" aria-hidden="true"></i> ' .
                                        e($isActive ? __('lang_v1.deactivate') : __('lang_v1.activate')) . '
                                    </a></li>';

                            $html .= '<li class="divider"></li>';

                            $html .= '<li><a href="' . url('/petropd/excess-comission/create?pump_operator_id=' . $operatorId) . '" class="btn-modal" data-container=".view_modal">
                                        <i class="fa fa-plus-circle" aria-hidden="true"></i> ' . e(__('petropd::lang.pay_excess_and_commission')) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/recover-shortage/create?pump_operator_id=' . $operatorId) . '" class="btn-modal" data-container=".view_modal">
                                        <i class="fa fa-minus-circle" aria-hidden="true"></i> ' . e(__('petropd::lang.recover_shortages')) . '
                                    </a></li>';

                            $html .= '<li class="divider"></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '?view=contact_info') . '">
                                        <i class="fa fa-user" aria-hidden="true"></i> ' . e(__('contact.contact_info', ['contact' => __('contact.contact')])) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '?view=ledger') . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i> ' . e(__('lang_v1.ledger')) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '/commission') . '">
                                        <i class="fa fa-list" aria-hidden="true"></i> ' . e(__('petropd::lang.list_commission')) . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $operatorId . '?view=documents_and_notes') . '">
                                        <i class="fa fa-paperclip" aria-hidden="true"></i> ' . e(__('lang_v1.documents_and_notes')) . '
                                    </a></li>';

                            $html .= '</ul></div>';

                            return $html;
                        }
                    )
PHP_CODE;

$pattern = "/->addColumn\(\s*'action',\s*function\s*\(\$row\)\s*\{.*?return\s+\$html;\s*\}\s*\)/s";
$count = 0;
$patched = preg_replace($pattern, $newActionBlock, $contents, 1, $count);

if ($patched === null || $count < 1) {
    fwrite(STDERR, "Could not find the PD Operators action dropdown block to replace. Backup kept at: {$backup}\n");
    exit(1);
}

if (file_put_contents($controller, $patched) === false) {
    fwrite(STDERR, "Unable to write patched controller. Backup kept at: {$backup}\n");
    exit(1);
}

echo "ZIP 307 applied successfully.\n";
echo "Backup created: {$backup}\n";
echo "Now run: php artisan optimize:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear\n";
