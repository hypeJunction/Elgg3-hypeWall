<?php
/**
 * PHPUnit bootstrap for hypeWall — Elgg 4.x.
 *
 * Loads Elgg + the test class autoloader, ensures the plugin is
 * enabled + active, and triggers init() so registrations wired through
 * elgg-plugin.php declarative config are actually attached.
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
    $boot_plugin = elgg_get_plugin_from_id('hypewall');
    if ($boot_plugin) {
        if (!$boot_plugin->isEnabled()) {
            $boot_plugin->enable();
        }
        if (!$boot_plugin->isActive()) {
            try { $boot_plugin->activate(); } catch (\Throwable $e) {}
        }
        // Trigger plugin init lifecycle so actions/routes/hooks/events/widgets/
        // group_tools/view_extensions declared in elgg-plugin.php are registered
        // before any test runs. IntegrationTestCase doesn't auto-init plugins
        // when reusing a shared app.
        try {
            $boot_plugin->init();
        } catch (\Throwable $e) {}
    }
}
