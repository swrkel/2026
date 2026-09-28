<?php
/**
 * MyHealthMembers sidebar installer helper.
 *
 * Run from Laravel project root:
 * php Modules/MyHealthMembers/Tools/install_sidebar_patch.php
 *
 * The script inserts @includeIf('myhealthmembers::partials.sidebar') into a common
 * ERP sidebar blade file. It is idempotent and creates a .bak backup before writing.
 */

$root = realpath(__DIR__ . '/../../..');
if (!$root) {
    fwrite(STDERR, "Unable to detect Laravel root.\n");
    exit(1);
}

$include = "@includeIf('myhealthmembers::partials.sidebar')";
$candidates = [
    'resources/views/layouts/partials/sidebar.blade.php',
    'resources/views/layouts/partials/left_sidebar.blade.php',
    'resources/views/layouts/partials/side_bar.blade.php',
    'resources/views/layouts/partials/sidebar-menu.blade.php',
    'resources/views/layouts/partials/menu.blade.php',
    'resources/views/layouts/sidebar.blade.php',
    'resources/views/partials/sidebar.blade.php',
    'resources/views/partials/left_sidebar.blade.php',
    'resources/views/home/partials/sidebar.blade.php',
];

$patched = false;
foreach ($candidates as $relative) {
    $file = $root . DIRECTORY_SEPARATOR . $relative;
    if (!is_file($file) || !is_writable($file)) {
        continue;
    }

    $contents = file_get_contents($file);
    if ($contents === false) {
        continue;
    }

    if (strpos($contents, $include) !== false) {
        echo "Already installed in {$relative}\n";
        $patched = true;
        break;
    }

    $newContents = null;
    if (preg_match('/<ul[^>]*(sidebar-menu|main-sidebar-menu|nav-sidebar)[^>]*>/i', $contents, $match, PREG_OFFSET_CAPTURE)) {
        $insertAt = $match[0][1] + strlen($match[0][0]);
        $newContents = substr($contents, 0, $insertAt) . "\n    " . $include . "\n" . substr($contents, $insertAt);
    } elseif (preg_match('/<ul[^>]*>/i', $contents, $match, PREG_OFFSET_CAPTURE)) {
        $insertAt = $match[0][1] + strlen($match[0][0]);
        $newContents = substr($contents, 0, $insertAt) . "\n    " . $include . "\n" . substr($contents, $insertAt);
    }

    if ($newContents !== null) {
        copy($file, $file . '.bak_myhealth_' . date('YmdHis'));
        file_put_contents($file, $newContents);
        echo "Installed MyHealth sidebar include in {$relative}\n";
        $patched = true;
        break;
    }
}

if (!$patched) {
    echo "No writable common sidebar blade was found. Add this line manually inside the main sidebar <ul>:\n";
    echo "    {$include}\n";
    exit(2);
}

exit(0);
