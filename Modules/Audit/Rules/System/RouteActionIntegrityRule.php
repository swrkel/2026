<?php

namespace Modules\Audit\Rules\System;

use Illuminate\Support\Facades\Route;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class RouteActionIntegrityRule extends BaseAuditRule
{
    protected $module = 'System';
    protected $severity = 'critical';
    protected $description = 'Checks loaded module routes for missing controller files or action methods without autoloading every controller class.';

    public function code(): string { return 'SYS-ROUTE-001'; }
    public function title(): string { return 'Module route/controller integrity'; }

    public function run(AuditContext $context): array
    {
        $findings = [];
        $parsed = [];
        $scanned = 0;
        $maxRoutes = max(100, (int) config('audit.route_integrity.max_routes', 5000));
        $maxFindings = max(10, (int) config('audit.route_integrity.max_findings', 250));

        foreach (Route::getRoutes() as $route) {
            $action = (string) $route->getActionName();
            if ($action === '' || $action === 'Closure' || strpos($action, 'Modules\\') !== 0 || strpos($action, '@') === false) {
                continue;
            }

            $scanned++;
            if ($scanned > $maxRoutes) {
                break;
            }

            [$class, $method] = explode('@', $action, 2);
            $controllerFile = $this->controllerFile($class);

            if (!$controllerFile || !is_file($controllerFile)) {
                $findings[] = $this->finding(
                    'routes',
                    $route->uri(),
                    'Module route controller file is missing',
                    'Route '.$route->uri().' points to '.$class.' but the expected controller file was not found.',
                    'Controller file exists',
                    'Controller file missing',
                    ['route_name' => $route->getName(), 'action' => $action, 'expected_file' => $controllerFile]
                );
            } else {
                if (!array_key_exists($controllerFile, $parsed)) {
                    $parsed[$controllerFile] = $this->parseControllerFile($controllerFile);
                }

                $info = $parsed[$controllerFile];
                if (!$info['valid_class']) {
                    $findings[] = $this->finding(
                        'routes',
                        $route->uri(),
                        'Module controller namespace/class mismatch',
                        'Route '.$route->uri().' points to '.$class.' but that class declaration was not found in '.$controllerFile.'.',
                        $class,
                        $info['declared_class'] ?: 'Class declaration not detected',
                        ['route_name' => $route->getName(), 'action' => $action, 'controller_file' => $controllerFile]
                    );
                } elseif (!in_array($method, $info['methods'], true) && class_exists($class, false) && !method_exists($class, $method)) {
                    // Never autoload controller classes from an audit rule. If the class is
                    // already loaded, method_exists() is safe and can confirm the defect.
                    // Otherwise an inherited method cannot be verified safely by static scan,
                    // so the rule deliberately avoids a false-positive finding.
                    $findings[] = $this->finding(
                        'routes',
                        $route->uri(),
                        'Module route action is missing',
                        'Route '.$route->uri().' points to '.$action.' but the action method is not available on the already-loaded controller class.',
                        'Controller method exists',
                        'Method missing',
                        ['route_name' => $route->getName(), 'action' => $action, 'controller_file' => $controllerFile]
                    );
                }
            }

            if (count($findings) >= $maxFindings) {
                break;
            }
        }

        return $findings;
    }

    protected function controllerFile(string $class): ?string
    {
        if (strpos($class, 'Modules\\') !== 0) {
            return null;
        }

        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        return base_path($relative);
    }

    protected function parseControllerFile(string $file): array
    {
        $result = ['valid_class' => false, 'declared_class' => null, 'methods' => []];

        try {
            $code = file_get_contents($file);
            if ($code === false) {
                return $result;
            }

            $tokens = token_get_all($code);
            $namespace = '';
            $class = '';
            $count = count($tokens);

            for ($i = 0; $i < $count; $i++) {
                $token = $tokens[$i];
                if (!is_array($token)) {
                    continue;
                }

                if ($token[0] === T_NAMESPACE) {
                    $namespace = $this->readName($tokens, $i + 1);
                    continue;
                }

                if ($token[0] === T_CLASS && $class === '' && !$this->isClassConstant($tokens, $i)) {
                    $class = $this->readClassName($tokens, $i + 1);
                    continue;
                }

                if ($token[0] === T_FUNCTION) {
                    $method = $this->readFunctionName($tokens, $i + 1);
                    if ($method) {
                        $result['methods'][] = $method;
                    }
                }
            }

            $declared = trim($namespace.'\\'.$class, '\\');
            $result['declared_class'] = $declared ?: null;
            $expected = trim(str_replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'], ['', '\\', ''], $file), '\\');
            $result['valid_class'] = $declared !== '' && strcasecmp($declared, $expected) === 0;
            $result['methods'] = array_values(array_unique($result['methods']));
        } catch (\Throwable $e) {
        }

        return $result;
    }


    protected function isClassConstant(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return is_array($token) && $token[0] === T_DOUBLE_COLON;
        }
        return false;
    }

    protected function readName(array $tokens, int $start): string
    {
        $name = '';
        $count = count($tokens);
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === ';' || $token === '{') {
                break;
            }
            if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR, defined('T_NAME_QUALIFIED') ? T_NAME_QUALIFIED : -1], true)) {
                $name .= $token[1];
            }
        }
        return trim($name, '\\');
    }

    protected function readClassName(array $tokens, int $start): string
    {
        $count = count($tokens);
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }
            if ($token === '{') {
                break;
            }
        }
        return '';
    }

    protected function readFunctionName(array $tokens, int $start): string
    {
        $count = count($tokens);
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === '(') {
                return '';
            }
            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }
        }
        return '';
    }
}
