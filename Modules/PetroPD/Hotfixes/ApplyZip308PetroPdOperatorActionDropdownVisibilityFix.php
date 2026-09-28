<?php
/**
 * ZIP 308 - PetroPD PD Operators Action Dropdown Visibility Fix
 *
 * Purpose:
 * - Fix PD Operators / Pump Operators Actions dropdown being partly covered by the table/card.
 * - Match Contact/Customers action behavior by floating the dropdown above the table overflow.
 * - Keep this fix inside Modules/PetroPD only.
 *
 * Run from Laravel project root:
 * php Modules/PetroPD/Hotfixes/ApplyZip308PetroPdOperatorActionDropdownVisibilityFix.php
 */

$root = dirname(__DIR__, 3);
$partialSource = __DIR__ . '/../Resources/views/partials/pd_operator_action_dropdown_visibility_fix.blade.php';
$partialTarget = $root . '/Modules/PetroPD/Resources/views/partials/pd_operator_action_dropdown_visibility_fix.blade.php';

if (!file_exists($partialSource)) {
    fwrite(STDERR, "Partial source missing: {$partialSource}\n");
    exit(1);
}

if (!is_dir(dirname($partialTarget)) && !mkdir(dirname($partialTarget), 0755, true)) {
    fwrite(STDERR, "Unable to create partial folder: " . dirname($partialTarget) . "\n");
    exit(1);
}

if (!copy($partialSource, $partialTarget)) {
    fwrite(STDERR, "Unable to copy partial to: {$partialTarget}\n");
    exit(1);
}

$viewCandidates = [
    $root . '/Modules/PetroPD/Resources/views/pd_operators/index.blade.php',
    $root . '/Modules/PetroPD/Resources/views/pd_operators/action.blade.php',
    $root . '/Modules/PetroPD/Resources/views/pd_operators/list.blade.php',
];

$marker = "{{-- ZIP 308 PetroPD action dropdown visibility fix --}}";
$include = $marker . "\n@include('petropd::partials.pd_operator_action_dropdown_visibility_fix')\n";
$patchedAny = false;

foreach ($viewCandidates as $view) {
    if (!file_exists($view)) {
        continue;
    }

    $contents = file_get_contents($view);
    if ($contents === false) {
        continue;
    }

    if (strpos($contents, $marker) !== false || strpos($contents, "pd_operator_action_dropdown_visibility_fix") !== false) {
        echo "Already patched: {$view}\n";
        $patchedAny = true;
        continue;
    }

    $backup = $view . '.backup_zip308_' . date('Ymd_His');
    copy($view, $backup);

    $patched = null;

    // Prefer inserting inside an existing javascript section if available.
    $lastEndsectionPos = strripos($contents, '@endsection');
    if ($lastEndsectionPos !== false && preg_match("/@section\s*\(\s*['\"]javascript['\"]\s*\)/i", $contents)) {
        $patched = substr($contents, 0, $lastEndsectionPos)
            . "\n" . $include . "\n"
            . substr($contents, $lastEndsectionPos);
    } else {
        $patched = rtrim($contents) . "\n\n@section('javascript')\n" . $include . "@endsection\n";
    }

    if (file_put_contents($view, $patched) === false) {
        fwrite(STDERR, "Unable to patch view: {$view}. Backup: {$backup}\n");
        exit(1);
    }

    echo "Patched view: {$view}\nBackup: {$backup}\n";
    $patchedAny = true;
    break;
}

if (!$patchedAny) {
    fwrite(STDERR, "Could not find PD Operators index/list view to patch. Partial was copied, but view include was not inserted.\n");
    fwrite(STDERR, "Please check Modules/PetroPD/Resources/views/pd_operators/ for the current list page name.\n");
    exit(1);
}

// Safety patch controller action dropdown CSS class if ZIP 307 was not applied or class missing.
$controller = $root . '/Modules/PetroPD/Http/Controllers/PDOperatorController.php';
if (file_exists($controller)) {
    $controllerContents = file_get_contents($controller);
    if ($controllerContents !== false && strpos($controllerContents, 'petropd-actions-dropdown') === false) {
        $controllerBackup = $controller . '.backup_zip308_' . date('Ymd_His');
        copy($controller, $controllerBackup);
        $controllerContents = str_replace(
            '<div class="btn-group">',
            '<div class="btn-group petropd-actions-dropdown">',
            $controllerContents
        );
        $controllerContents = str_replace(
            "<div class='btn-group'>",
            "<div class='btn-group petropd-actions-dropdown'>",
            $controllerContents
        );
        file_put_contents($controller, $controllerContents);
        echo "Safety patched controller class. Backup: {$controllerBackup}\n";
    }
}

echo "ZIP 308 applied successfully.\n";
echo "Now run: php artisan optimize:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear\n";
