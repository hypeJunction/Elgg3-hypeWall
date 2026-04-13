<?php
/**
 * PHPUnit bootstrap for hypeWall.
 *
 * Loads Elgg core + the test class autoloader, then ensures the plugin
 * is enabled, active, and lifecycle-init'd so its actions/hooks/widgets/
 * group_tools/view_extensions are registered for the test run.
 */

$elggRoot = '/var/www/html';

require_once $elggRoot . '/vendor/autoload.php';

// Elgg test classes (IntegrationTestCase, BaseTestCase, Seeding trait).
$testClassesDir = $elggRoot . '/vendor/elgg/elgg/engine/tests/classes';
spl_autoload_register(function ($class) use ($testClassesDir) {
    $file = $testClassesDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

\Elgg\Application::getInstance()->bootCore();

if (function_exists('_elgg_services')) {
    _elgg_services()->plugins->generateEntities();
    $boot_plugin = elgg_get_plugin_from_id('hypeWall');
    if ($boot_plugin) {
        if (!$boot_plugin->isEnabled()) {
            $boot_plugin->enable();
        }
        if (!$boot_plugin->isActive()) {
            try { $boot_plugin->activate(); } catch (\Throwable $e) {}
        }
        // 3.x plugins boot via start.php returning a closure, which Elgg invokes
        // during the system 'init' event. Trigger it manually so tests see the
        // post-init state (registered hooks/actions/views).
        try {
            $closure = require $boot_plugin->getPath() . 'start.php';
            if ($closure instanceof \Closure) {
                $closure();
            }
        } catch (\Throwable $e) {}
        try {
            elgg_trigger_event('init', 'system');
        } catch (\Throwable $e) {}
    }
}
