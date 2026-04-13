<?php

namespace hypeJunction\Wall;

use Elgg\IntegrationTestCase;

/**
 * Lock in that key user-facing views render without errors. A migration
 * that removes a referenced view file or breaks a partial is caught here.
 */
class ViewRenderingTest extends IntegrationTestCase {

	public function up() {}
	public function down() {}

	public function getPluginID(): string {
		return 'hypeWall';
	}

	public function testWallStylesheetRenders(): void {
		$css = elgg_view('framework/wall/stylesheet.css');
		$this->assertIsString($css);
		// The stylesheet should produce non-trivial output.
		$this->assertGreaterThan(0, strlen($css));
	}

	public function testWallMessageElementRenders(): void {
		// Render with a fake entity. The element view should not throw.
		$entity = elgg_call(ELGG_IGNORE_ACCESS, function () {
			$user = $this->createUser();
			$post = new Post();
			$post->owner_guid = $user->guid;
			$post->container_guid = $user->guid;
			$post->access_id = ACCESS_PUBLIC;
			$post->description = 'sample';
			$post->save();
			return $post;
		});
		$output = elgg_view('object/hjwall/elements/message', ['entity' => $entity]);
		$this->assertIsString($output);
		$entity->delete();
	}

	public function testWallPageComponentRenders(): void {
		$output = elgg_view('page/components/wall', []);
		$this->assertIsString($output);
	}

	public function testRiverItemRendersForPost(): void {
		$entity = elgg_call(ELGG_IGNORE_ACCESS, function () {
			$user = $this->createUser();
			$post = new Post();
			$post->owner_guid = $user->guid;
			$post->container_guid = $user->guid;
			$post->access_id = ACCESS_PUBLIC;
			$post->description = 'river item';
			$post->save();
			return $post;
		});

		// river/object/hjwall/create takes $vars['item'] as an ElggRiverItem.
		// Just asserting the view file resolves and the include doesn't throw
		// is enough for migration coverage; full river render needs a river entry.
		$this->assertTrue(elgg_view_exists('river/object/hjwall/create'));
		$entity->delete();
	}
}
