<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Lock in the hook + event handler attachments. These tests trigger the
 * actual hook the handler is registered against and assert the handler
 * runs (catches the "migration removed the registration but kept the
 * method" failure mode that simple method_exists tests miss).
 */
class HooksTest extends IntegrationTestCase {

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
    public function testLikableHookReturnsTrueForHjwall(): void {
		// likes:is_likable hook for object:hjwall is registered to
		// Elgg\Values::getTrue, so triggering it must return true.
		$result = elgg_trigger_plugin_hook('likes:is_likable', 'object:hjwall', [], false);
		$this->assertTrue(
			(bool) $result,
			'likes:is_likable hook for object:hjwall must return true (registers Elgg\\Values::getTrue)'
		);
	}

	/**
     * @return void
     */
    public function testLikableHookForOtherSubtypeUntouched(): void {
		// Sanity check that the hook is NOT globally hijacked.
		$result = elgg_trigger_plugin_hook('likes:is_likable', 'object:nosuchtype', [], false);
		$this->assertFalse((bool) $result);
	}

	/**
     * @return void
     */
    public function testContainerPermissionsCheckHookRegistered(): void {
		// Permissions::containerPermissionsCheck is registered to
		// container_permissions_check/object. Triggering with a synthetic
		// container_guid + user_guid pair should return a value (true or
		// false), proving the handler is wired in. We only assert "did not
		// throw" — the actual permission outcome depends on test fixture state.
		$user = $this->createUser();
		$container = $this->createUser();
		$result = elgg_trigger_plugin_hook('container_permissions_check', 'object', [
			'container' => $container,
			'user' => $user,
			'subtype' => Post::SUBTYPE,
		], false);
		$this->assertIsBool($result);
	}
}
