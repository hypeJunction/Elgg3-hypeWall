<?php

declare(strict_types=1);

namespace hypeJunction\Wall\Database\Seeds;

use Elgg\Database\Seeds\Seed;
use Elgg\Event;

/**
 * Seed / unseed hypewall plugin entities.
 *
 * Owned entity types: 'hjwall'
 *
 * Register via:
 *   elgg_register_event_handler('seeds', 'database', [Seeder::class, 'addSeed']);
 *
 * Run with:
 *   php elgg-cli database:seed --type=hypewall
 *   php elgg-cli database:unseed --type=hypewall
 */
final class Seeder extends Seed
{
    /**
     * Identifies this seeder to the CLI: --type=hypewall
     */
    public static function getType(): string
    {
        return 'hypewall';
    }

    /**
     * Options passed to elgg_get_entities() when counting existing seeds.
     *
     * @return array<string, mixed>
     */
    protected function getCountOptions(): array
    {
        return [
            'type' => 'object',
            'metadata_name_value_pairs' => [
                ['name' => '__faker', 'value' => true],
            ],
        ];
    }

    /**
     * Create one seeded entity of each owned type.
     */
    public function seed(): void
    {
        // object/hjwall
        $entity = $this->createObject([
            'subtype' => 'hjwall',
        ]);
        $entity->title = $this->faker->sentence(3);
        $entity->description = $this->faker->paragraph();
        $entity->__faker = true;
        $entity->save();
    }

    /**
     * Delete all entities previously created by this seeder (tagged __faker=true).
     */
    public function unseed(): void
    {
        // Unseed object/hjwall
        $entities = elgg_get_entities([
            'type' => 'object',
            'subtype' => 'hjwall',
            'metadata_name_value_pairs' => [['name' => '__faker', 'value' => true]],
            'limit' => false,
        ]);
        foreach ($entities as $e) {
            $e->delete();
        }
    }

    /**
     * Event handler for 'seeds', 'database' — appends this class to the seeds list.
     */
    public static function addSeed(Event $event): array
    {
        $value = $event->getValue() ?? [];
        $value[] = __CLASS__;
        return $value;
    }
}