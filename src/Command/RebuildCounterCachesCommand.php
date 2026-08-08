<?php
declare(strict_types=1);

namespace App\Command;

use App\Utility\CounterCaches;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Rebuild CounterCache columns after import / schema drift.
 *
 * bin/cake rebuild_counter_caches
 */
class RebuildCounterCachesCommand extends Command
{
    /**
     * @param \Cake\Console\ConsoleOptionParser $parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Rebuild all CounterCache / summary columns (users, clubs, cities, counties, competitions).'
        );

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('Rebuilding CounterCache fields…');
        foreach (CounterCaches::rebuildAll() as $step) {
            $io->out('  • ' . $step);
        }
        $io->success('CounterCache rebuild finished.');

        return static::CODE_SUCCESS;
    }
}
