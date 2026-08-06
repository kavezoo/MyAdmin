<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Table;

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
            'Rebuild CounterCache fields: Countries.user_count, Clubs.user_count.'
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
        $users = $this->fetchTable('Users');
        $countries = $this->fetchTable('Countries');

        // Translate LEFT JOIN makes ORDER BY id ambiguous during CounterCache parent scans.
        $this->withoutTranslate($countries);

        $io->out('Updating Countries.user_count (Users → Countries CounterCache)…');
        $users->getBehavior('CounterCache')->updateCounterCache('Countries');

        $io->out('Updating Clubs.user_count (Users → Clubs CounterCache)…');
        $users->getBehavior('CounterCache')->updateCounterCache('Clubs');

        $io->success('CounterCache rebuild finished.');

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\ORM\Table $table
     * @return void
     */
    protected function withoutTranslate(Table $table): void
    {
        if ($table->hasBehavior('Translate')) {
            $table->removeBehavior('Translate');
        }
    }
}
