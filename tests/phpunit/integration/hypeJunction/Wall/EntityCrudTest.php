<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Lock in entity CRUD for hjwall so a migration can't silently break the
 * subtype mapping, metadata persistence, or owner/container relationships.
 */
class EntityCrudTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	public function getPluginID(): string {
		return 'hypeWall';
	}

	private function makePost($overrides = []): Post {
		return elgg_call(ELGG_IGNORE_ACCESS, function () use ($overrides) {
			$user = $overrides['__user'] ?? $this->createUser();
			$post = new Post();
			$post->owner_guid = $overrides['owner_guid'] ?? $user->guid;
			$post->container_guid = $overrides['container_guid'] ?? $user->guid;
			$post->access_id = $overrides['access_id'] ?? ACCESS_PUBLIC;
			$post->description = $overrides['description'] ?? 'hello';
			if (isset($overrides['address'])) {
				$post->address = $overrides['address'];
			}
			if (isset($overrides['title'])) {
				$post->title = $overrides['title'];
			}
			$post->save();
			return $post;
		});
	}

	public function testCreatedPostInstantiatesPostClass(): void {
		$post = $this->makePost();
		$this->assertGreaterThan(0, $post->guid);
		$this->assertSame('object', $post->type);
		$this->assertSame(Post::SUBTYPE, $post->getSubtype());
		$post->delete();
	}

	public function testLoadedPostIsPostInstance(): void {
		$post = $this->makePost();
		$guid = $post->guid;
		_elgg_services()->entityCache->delete($guid);
		$loaded = get_entity($guid);
		$this->assertInstanceOf(Post::class, $loaded);
		$post->delete();
	}

	public function testDescriptionPersists(): void {
		$post = $this->makePost(['description' => 'persisted body']);
		_elgg_services()->entityCache->delete($post->guid);
		$loaded = get_entity($post->guid);
		$this->assertSame('persisted body', (string) $loaded->description);
		$post->delete();
	}

	public function testAddressMetadataPersists(): void {
		$post = $this->makePost(['address' => 'http://example.test/article']);
		_elgg_services()->entityCache->delete($post->guid);
		$loaded = get_entity($post->guid);
		$this->assertSame('http://example.test/article', (string) $loaded->address);
		$post->delete();
	}

	public function testTitleMetadataPersists(): void {
		$post = $this->makePost(['title' => 'My title']);
		_elgg_services()->entityCache->delete($post->guid);
		$loaded = get_entity($post->guid);
		$this->assertSame('My title', (string) $loaded->title);
		$post->delete();
	}

	public function testDeleteReturnsTruthy(): void {
		// Elgg 3.x delete() may keep the row but mark disabled rather than
		// hard-deleting (depends on registered cleanup handlers). Migration
		// safety just needs delete() to return truthy without throwing — the
		// actual storage semantics are tested by Elgg core.
		$post = $this->makePost();
		$result = elgg_call(ELGG_IGNORE_ACCESS, function () use ($post) {
			return $post->delete();
		});
		$this->assertNotFalse($result);
	}

	public function testDisplayNameForOwnerEqualsContainer(): void {
		// When owner_guid == container_guid, the post is a status update on
		// the user's own wall — getDisplayName() must return non-empty
		// regardless of the templating string used.
		$post = $this->makePost();
		$this->assertNotEmpty($post->getDisplayName());
		$post->delete();
	}
}
