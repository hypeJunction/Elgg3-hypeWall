<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Lock in the owner-vs-non-owner permission boundary on hjwall posts and
 * the container_permissions_check hook handler exposed by Permissions.
 */
class PermissionsTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	public function getPluginID(): string {
		return 'hypeWall';
	}

	private function makePost($owner): Post {
		return elgg_call(ELGG_IGNORE_ACCESS, function () use ($owner) {
			$post = new Post();
			$post->owner_guid = $owner->guid;
			$post->container_guid = $owner->guid;
			$post->access_id = ACCESS_PUBLIC;
			$post->description = 'mine';
			$post->save();
			return $post;
		});
	}

	public function testOwnerCanEditOwnPost(): void {
		$owner = $this->createUser();
		$post = $this->makePost($owner);
		$this->assertTrue($post->canEdit($owner->guid));
		$post->delete();
	}

	public function testNonOwnerCannotEditPost(): void {
		$owner = $this->createUser();
		$other = $this->createUser();
		$post = $this->makePost($owner);
		$this->assertFalse($post->canEdit($other->guid));
		$post->delete();
	}

	public function testAdminCanEditAnyPost(): void {
		$owner = $this->createUser();
		$admin = $this->createUser();
		$admin->makeAdmin();
		$post = $this->makePost($owner);
		$this->assertTrue($post->canEdit($admin->guid));
		$post->delete();
	}

	public function testContainerPermissionsCheckIsStatic(): void {
		// containerPermissionsCheck is registered as a hook handler — must
		// remain a public static method that accepts the hook signature.
		$reflection = new \ReflectionMethod(Permissions::class, 'containerPermissionsCheck');
		$this->assertTrue($reflection->isStatic());
		$this->assertTrue($reflection->isPublic());
	}
}
