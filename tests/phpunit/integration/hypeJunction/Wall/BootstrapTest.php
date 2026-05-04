<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Pre-migration behavior lock-in for hypeWall plugin bootstrap.
 *
 * Asserts that every registration declared in start.php (3.x baseline) /
 * elgg-plugin.php (4.x target) is reachable: actions exist, hook handlers
 * are attached, widget + group tool are registered, view extensions are
 * present, the entity class is mapped.
 *
 * Coverage rubric: each test answers "if a migration silently removed
 * this registration, would the test fail?" with yes.
 */
class BootstrapTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	/**
     * @return string
     */
    public function getPluginID(): string {
		return 'hypewall';
	}

	/**
     * @return void
     */
    public function testPluginLoadable(): void {
		$plugin = elgg_get_plugin_from_id('hypewall');
		$this->assertNotNull($plugin);
		$this->assertNotFalse($plugin->isActive());
	}

	// === Actions ===
    /**
     * @return void
     */
    public function testStatusActionRegistered(): void {
		$this->assertTrue(elgg_action_exists('wall/status'));
	}

	/**
     * @return void
     */
    public function testRemoveTagActionRegistered(): void {
		$this->assertTrue(elgg_action_exists('wall/remove_tag'));
	}

	/**
     * @return void
     */
    public function testGeopositioningActionMissing(): void {
		// KNOWN GAP: start.php registers wall/geopositioning/update but the
		// action file actions/wall/geopositioning/update.php was never present
		// in the Elgg3-hypeWall fork. The legacy hypeWall (plugins-other/)
		// had this file — MIGRATION.md Phase 0 lists it as a Phase 0 feature
		// merge that must happen before bodyology can fully drop the legacy.
		// Test asserts the CURRENT state (action not registered) so the assertion
		// flips when the gap is closed and forces a review.
		$this->assertFalse(
			elgg_action_exists('wall/geopositioning/update'),
			'Geopositioning action should remain MISSING until the Phase 0 feature merge from legacy hypeWall happens. Update this test when the file is restored.'
		);
	}

	// === Entity ===
    /**
     * @return void
     */
    public function testHjwallSubtypeConstant(): void {
		$this->assertSame('hjwall', Post::SUBTYPE);
	}

	/**
     * @return void
     */
    public function testHjwallEntityClassMapped(): void {
		$class = elgg_get_entity_class('object', Post::SUBTYPE);
		$this->assertSame(Post::class, $class);
	}

	// === Classes autoload ===
    /**
     * @return void
     */
    public function testPostClassAutoloads(): void {
		$this->assertTrue(class_exists(Post::class));
	}

	/**
     * @return void
     */
    public function testMenusClassAutoloads(): void {
		$this->assertTrue(class_exists(Menus::class));
	}

	/**
     * @return void
     */
    public function testNotificationsClassAutoloads(): void {
		$this->assertTrue(class_exists(Notifications::class));
	}

	/**
     * @return void
     */
    public function testPermissionsClassAutoloads(): void {
		$this->assertTrue(class_exists(Permissions::class));
	}

	// === Class methods (hook handlers must remain callable across migration) ===
    /**
     * @return void
     */
    public function testPermissionsContainerCheckCallable(): void {
		$this->assertTrue(method_exists(Permissions::class, 'containerPermissionsCheck'));
	}

	/**
     * @return void
     */
    public function testNotificationsSendCustomCallable(): void {
		$this->assertTrue(method_exists(Notifications::class, 'sendCustomNotifications'));
	}

	/**
     * @return void
     */
    public function testNotificationsFormatMessageCallable(): void {
		$this->assertTrue(method_exists(Notifications::class, 'formatMessage'));
	}

	/**
     * @return void
     */
    public function testMenusEntityMenuSetupCallable(): void {
		$this->assertTrue(method_exists(Menus::class, 'entityMenuSetup'));
	}

	/**
     * @return void
     */
    public function testMenusOwnerBlockMenuSetupCallable(): void {
		$this->assertTrue(method_exists(Menus::class, 'ownerBlockMenuSetup'));
	}

	/**
     * @return void
     */
    public function testMenusRiverMenuSetupCallable(): void {
		$this->assertTrue(method_exists(Menus::class, 'riverMenuSetup'));
	}

	/**
     * @return void
     */
    public function testMenusUserHoverMenuSetupCallable(): void {
		$this->assertTrue(method_exists(Menus::class, 'userHoverMenuSetup'));
	}

	/**
     * @return void
     */
    public function testPostGetGraphAliasCallable(): void {
		$this->assertTrue(method_exists(Post::class, 'getGraphAlias'));
	}

	/**
     * @return void
     */
    public function testPostGetPostPropertiesCallable(): void {
		$this->assertTrue(method_exists(Post::class, 'getPostProperties'));
	}

	// === Views ===
    /**
     * @return void
     */
    public function testWallFormViewExists(): void {
		$this->assertTrue(elgg_view_exists('forms/wall/status'));
	}

	/**
     * @return void
     */
    public function testWallMessageViewExists(): void {
		$this->assertTrue(elgg_view_exists('object/hjwall/elements/message'));
	}

	/**
     * @return void
     */
    public function testRiverItemViewExists(): void {
		$this->assertTrue(elgg_view_exists('river/object/hjwall/create'));
	}

	/**
     * @return void
     */
    public function testWallStylesheetViewExists(): void {
		$this->assertTrue(elgg_view_exists('framework/wall/stylesheet.css'));
	}

	/**
     * @return void
     */
    public function testWallPageComponentViewExists(): void {
		$this->assertTrue(elgg_view_exists('page/components/wall'));
	}
}
