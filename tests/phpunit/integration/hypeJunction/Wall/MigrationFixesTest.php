<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Elgg 7.x migration-fix regression guards for hypeWall.
 *
 * Each test locks in the FIXED behavior of a specific migration commit so a
 * future edit that reintroduces the removed/renamed symbol fails a test rather
 * than silently fataling at page render on the 7.x runtime. Commit refs in the
 * method docblocks map to the plugin's migrate/elgg-7.x history.
 */
class MigrationFixesTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	/**
	 * @return string
	 */
	public function getPluginID(): string {
		return 'hypewall';
	}

	/**
	 * Walk up from this test file to the plugin root (dir holding elgg-plugin.php).
	 *
	 * @return string
	 */
	private function pluginDir(): string {
		$dir = __DIR__;
		for ($i = 0; $i < 6; $i++) {
			if (is_file($dir . '/elgg-plugin.php')) {
				return $dir;
			}
			$dir = dirname($dir);
		}
		$this->fail('could not locate plugin root from ' . __DIR__);
	}

	/**
	 * ref 55a6ef1 — container.mjs imported the removed 'elgg/notify' ESM module.
	 * Elgg 7 has no such bare specifier, so the import aborted the whole module
	 * (form submit / url preview / geolocation all dead). It was renamed to
	 * 'elgg/system_messages' (same success/error API).
	 *
	 * @return void
	 */
	public function testContainerModuleImportsSystemMessagesNotNotify(): void {
		$src = (string) file_get_contents($this->pluginDir() . '/views/default/framework/wall/container.mjs');
		$this->assertNotSame('', $src, 'container.mjs must exist and be non-empty');
		$this->assertStringContainsString(
			"from 'elgg/system_messages'",
			$src,
			'container.mjs must import elgg/system_messages (the 7.x replacement for elgg/notify)'
		);
		$this->assertStringNotContainsString(
			'elgg/notify',
			$src,
			'container.mjs must NOT import the removed elgg/notify module — it fails to resolve on Elgg 7'
		);
	}

	/**
	 * ref bc65bf3 — container + attachments were ESM-shaped but saved as .js, so
	 * the Elgg 7 importmap never registered them and the inline type=module
	 * import resolved to nothing. They must exist as .mjs (and the stale .js
	 * copies must be gone).
	 *
	 * @return void
	 */
	public function testEsmModulesRenamedToMjs(): void {
		$root = $this->pluginDir();
		$this->assertFileExists($root . '/views/default/framework/wall/container.mjs');
		$this->assertFileExists($root . '/views/default/output/wall/attachments.mjs');
		$this->assertFileDoesNotExist(
			$root . '/views/default/framework/wall/container.js',
			'legacy container.js must be removed — Elgg 7 importmap only registers .mjs'
		);
		$this->assertFileDoesNotExist(
			$root . '/views/default/output/wall/attachments.js',
			'legacy attachments.js must be removed — Elgg 7 importmap only registers .mjs'
		);
	}

	/**
	 * ref 6187b9c — usersettings.php passed $user->guid where $vars carries an
	 * integer user_guid, so a null guid hit elgg_get_plugin_user_setting's strict
	 * int param and TypeError'd on Elgg 7. It now extracts user_guid (defaulting
	 * to the logged-in guid). Rendering with a user_guid var must not throw and
	 * must emit the river_access_id access field.
	 *
	 * @return void
	 */
	public function testUsersettingsRendersWithUserGuidVar(): void {
		$user = $this->createUser();
		$output = elgg_view('plugins/hypewall/usersettings', ['user_guid' => (int) $user->guid]);
		$this->assertIsString($output);
		$this->assertStringContainsString(
			'river_access_id',
			$output,
			'usersettings must render the river_access_id access field'
		);
	}

	/**
	 * ref 5466fcc — resources/wall/owner.php used the removed
	 * ElggSession::getLoggedInUser(); replaced with elgg_get_logged_in_user_entity().
	 * ref ab4d4b1 — remove_tag.php reads $relationship->id, so it must call
	 * getRelationship() (returns the ElggRelationship) not hasRelationship() (bool).
	 *
	 * @return void
	 */
	public function testResourceAndActionUse7xEntityApis(): void {
		$root = $this->pluginDir();

		$owner = (string) file_get_contents($root . '/views/default/resources/wall/owner.php');
		$this->assertStringContainsString('elgg_get_logged_in_user_entity(', $owner);
		$this->assertStringNotContainsString(
			'getLoggedInUser(',
			$owner,
			'owner.php must not call the removed ElggSession::getLoggedInUser()'
		);

		$remove_tag = (string) file_get_contents($root . '/actions/wall/remove_tag.php');
		$this->assertStringContainsString(
			'getRelationship(',
			$remove_tag,
			'remove_tag.php reads $relationship->id and needs the ElggRelationship object'
		);
		$this->assertStringNotContainsString(
			'hasRelationship(',
			$remove_tag,
			'hasRelationship() returns a bool — ->id would be undefined on it'
		);
	}

	/**
	 * ref f11e3be — Post.php referenced the moved \hypeJunction\Graph\Graph
	 * (now \hypeJunction\Data\Graph), and Menus::setupQuickLinks used the removed
	 * get_registered_entity_types() (now elgg_entity_types_with_capability()).
	 *
	 * @return void
	 */
	public function testPostAndMenusUse7xReplacements(): void {
		$root = $this->pluginDir();

		$post = (string) file_get_contents($root . '/classes/hypeJunction/Wall/Post.php');
		$this->assertStringContainsString('\hypeJunction\Data\Graph', $post);
		$this->assertStringNotContainsString(
			'hypeJunction\Graph\Graph',
			$post,
			'Post.php must reference the relocated \hypeJunction\Data\Graph namespace'
		);

		$menus = (string) file_get_contents($root . '/classes/hypeJunction/Wall/Menus.php');
		$this->assertStringContainsString('elgg_entity_types_with_capability(', $menus);
		$this->assertStringNotContainsString(
			'get_registered_entity_types(',
			$menus,
			'Menus must use elgg_entity_types_with_capability() — get_registered_entity_types() is removed in 7.x'
		);
	}

	/**
	 * ref 5ce62a2 — Seeder::getType() was made static to match the Elgg 6.1+
	 * abstract Seed signature (a non-static override fatals at class load).
	 *
	 * @return void
	 */
	public function testSeederGetTypeIsStaticAndReturnsPluginId(): void {
		$reflection = new \ReflectionMethod(Seeder::class, 'getType');
		$this->assertTrue($reflection->isStatic(), 'Seed::getType() is static since Elgg 6.1');
		$this->assertSame('hypewall', Seeder::getType());
	}

	/**
	 * Seeder::addSeed is wired to the seeds/database event in Bootstrap::init, so
	 * triggering that event must return the Seeder class appended to the list.
	 *
	 * @return void
	 */
	public function testSeedsDatabaseEventRegistersSeeder(): void {
		$seeds = elgg_trigger_event_results('seeds', 'database', [], []);
		$this->assertIsArray($seeds);
		$this->assertContains(
			Seeder::class,
			$seeds,
			'Seeder must append itself via the seeds/database event handler'
		);
	}

	/**
	 * ref 0dd200bc — Menus::entityMenuSetup dropped its ?array return type so the
	 * shared handler can return the null path (from riverMenuSetup) and still
	 * satisfy the Elgg 6.1+ menu event signature. Re-adding a declared return
	 * type would reintroduce the incompatibility.
	 *
	 * @return void
	 */
	public function testMenuHandlersHaveNoDeclaredReturnType(): void {
		foreach (['entityMenuSetup', 'riverMenuSetup'] as $method) {
			$reflection = new \ReflectionMethod(Menus::class, $method);
			$this->assertFalse(
				$reflection->hasReturnType(),
				"Menus::{$method}() must not declare a return type (menu event signature compatibility)"
			);
		}
	}

	/**
	 * Behavior: Permissions::containerPermissionsCheck grants write access to a
	 * user's wall when the wall owner (container) has a 'friend' relationship to
	 * the writer. Triggered through the real container_permissions_check event
	 * with a false default, so a true result is attributable to our handler.
	 *
	 * @return void
	 */
	public function testContainerCheckGrantsWriteForFriend(): void {
		$writer = $this->createUser();
		$owner = $this->createUser();

		elgg_call(ELGG_IGNORE_ACCESS, function () use ($owner, $writer) {
			$owner->addRelationship($writer->guid, 'friend');
		});

		$result = elgg_trigger_event_results('container_permissions_check', 'object', [
			'container' => $owner,
			'user' => $writer,
			'subtype' => Post::SUBTYPE,
		], false);

		$this->assertTrue(
			(bool) $result,
			'a friend of the wall owner must be granted write access to the wall'
		);
	}
}
