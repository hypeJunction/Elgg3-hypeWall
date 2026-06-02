<?php

declare(strict_types=1);

namespace hypeJunction\Wall\Cli;

use Elgg\Cli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Post-migration data integrity checks for the hypewall plugin.
 *
 * Run with:
 *   php elgg-cli hypewall:doctor
 */
class DoctorCommand extends Command {

    /** @var mixed */
    protected static $defaultName = 'hypewall:doctor';

    /**
     * @return void
     */
    protected function configure(): void {
        $this->setDescription('Post-migration data integrity checks for hypewall');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function command(InputInterface $input, OutputInterface $output): int {
        $exitCode = self::SUCCESS;

        // Count object/hjwall entities
        $count_hjwall = (int) elgg_get_entities([
            'type' => 'object',
            'subtype' => 'hjwall',
            'count' => true,
        ]);
        $output->writeln("  object/hjwall: {$count_hjwall} entities");

        // Verify upgrades completed
        // TODO: check pending Elgg\Upgrade\Batch scripts for this plugin
        // Example: query elgg_entities for type='object' subtype='upgrade' with status != 'completed'

        // Orphan relationship check
        // TODO: check for relationships referencing non-existent entities owned by this plugin

        // Plugin-specific config invariants
        // TODO: verify expected plugin settings are set and valid

        if ($exitCode === self::SUCCESS) {
            $output->writeln('<info>hypewall:doctor complete — no issues found</info>');
        } else {
            $output->writeln('<error>hypewall:doctor found issues — review output above</error>');
        }

        return $exitCode;
    }
}