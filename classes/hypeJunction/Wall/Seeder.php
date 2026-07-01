<?php

declare(strict_types=1);

namespace hypeJunction\Wall;

use Elgg\Database\Seeds\Seed;

/**
 * Seeder for hypewall entities.
 */
class Seeder extends Seed {

	/**
	 * {@inheritDoc}
	 */
	public static function getType(): string {
		return 'hypewall';
	}

	/**
	 * {@inheritDoc}
	 */
	public function seed(): void {
		$this->advance('Seeding hypewall entities...');

		$count = $this->getCount();

		for ($i = 0; $i < $count; $i++) {
			$user = $this->getRandomUser();
			if (!$user) {
				continue;
			}

			// TODO: Create a Post entity with faker data
			// $entity = new \hypeJunction\Wall\Post();
			// $entity->owner_guid = $user->guid;
			// $entity->container_guid = $user->guid;
			// $entity->description = $this->faker()->paragraph();
			// $entity->save();

			// Tag for cleanup:
			// $entity->setMetadata('__faker', true);

			$this->advance();
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function unseed(): void {
		$batch = elgg_get_entities([
			'type' => 'object',
			'subtype' => 'hjwall',
			'metadata_name' => '__faker',
			'metadata_value' => true,
			'limit' => false,
			'batch' => true,
			'batch_inc_offset' => false,
		]);

		foreach ($batch as $entity) {
			$entity->delete();
			$this->advance();
		}
	}

	/**
	 * Register this seeder via the seeds,database event.
	 *
	 * @param \Elgg\Event $event Event
	 * @return mixed
	 */
	public static function addSeed(\Elgg\Event $event): mixed {
		$seeds = $event->getValue();
		$seeds[] = static::class;
		return $seeds;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCountOptions(): array {
		return [
			'type' => 'object',
			'subtype' => 'hjwall',
		];
	}
}
