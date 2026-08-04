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
            'Rebuild CounterCache fields: Parents.sample_count, Samples.city_count,'
            . ' Cities.sample_count, Countries.user_count.'
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
        $samples = $this->fetchTable('Samples');
        $citiesSamples = $this->fetchTable('CitiesSamples');
        $users = $this->fetchTable('Users');
        $parents = $this->fetchTable('Parents');
        $countries = $this->fetchTable('Countries');
        $cities = $this->fetchTable('Cities');

        // Translate LEFT JOIN makes ORDER BY id ambiguous during CounterCache parent scans.
        $this->withoutTranslate($parents);
        $this->withoutTranslate($countries);
        $this->withoutTranslate($samples);
        $this->withoutTranslate($cities);

        $io->out('Updating Parents.sample_count (Samples → Parents CounterCache)…');
        $samples->getBehavior('CounterCache')->updateCounterCache('Parents');

        $io->out('Updating Samples.city_count + Cities.sample_count (CitiesSamples CounterCache)…');
        $citiesSamples->getBehavior('CounterCache')->updateCounterCache();

        $io->out('Updating Countries.user_count (Users → Countries CounterCache)…');
        $users->getBehavior('CounterCache')->updateCounterCache('Countries');

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
