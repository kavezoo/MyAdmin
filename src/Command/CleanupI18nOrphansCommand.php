<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * Delete orphan Translate EAV rows in `i18n` (foreign_key with no parent record).
 *
 * bin/cake cleanup_i18n_orphans
 * bin/cake cleanup_i18n_orphans --dry-run
 */
class CleanupI18nOrphansCommand extends Command
{
    /**
     * Translate `model` alias → physical parent table.
     * Unknown models are deleted entirely (no live parent table).
     *
     * @var array<string, string>
     */
    protected array $modelTables = [
        'Countries' => 'countries',
        'Continents' => 'continents',
    ];

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(
                'Remove orphan i18n rows whose foreign_key no longer exists on the parent table '
                . '(Countries → countries, Continents → continents). Unknown model values are wiped.'
            )
            ->addOption('dry-run', [
                'help' => 'Only report how many rows would be deleted.',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $dryRun = (bool)$args->getOption('dry-run');
        $conn = ConnectionManager::get('default');

        $before = (int)$conn->execute('SELECT COUNT(*) AS c FROM i18n')->fetch('assoc')['c'];
        $io->out(($dryRun ? '[dry-run] ' : '') . "i18n rows before: {$before}");

        $models = $conn->execute(
            'SELECT model, COUNT(*) AS n, COUNT(DISTINCT foreign_key) AS fk_n
             FROM i18n GROUP BY model ORDER BY model'
        )->fetchAll('assoc');

        if ($models === []) {
            $io->success('i18n is empty — nothing to do.');

            return static::CODE_SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($models as $row) {
            $model = (string)$row['model'];
            $io->out('');
            $io->out("Model {$model}: rows={$row['n']}, distinct_fk={$row['fk_n']}");

            if (!isset($this->modelTables[$model])) {
                $count = $this->countOrDelete(
                    $conn,
                    'DELETE FROM i18n WHERE model = :model',
                    ['model' => $model],
                    $dryRun,
                    "SELECT COUNT(*) AS c FROM i18n WHERE model = :model",
                    ['model' => $model]
                );
                $io->warning("  UNKNOWN model → " . ($dryRun ? "would delete {$count}" : "deleted {$count}"));
                $totalDeleted += $count;
                continue;
            }

            $tableName = $this->modelTables[$model];
            $exists = $conn->execute('SHOW TABLES LIKE :t', ['t' => $tableName])->fetchAll();
            if ($exists === []) {
                $count = $this->countOrDelete(
                    $conn,
                    'DELETE FROM i18n WHERE model = :model',
                    ['model' => $model],
                    $dryRun,
                    'SELECT COUNT(*) AS c FROM i18n WHERE model = :model',
                    ['model' => $model]
                );
                $io->warning("  Missing table {$tableName} → " . ($dryRun ? "would delete {$count}" : "deleted {$count}"));
                $totalDeleted += $count;
                continue;
            }

            // Safe identifier: only from our whitelist map
            $sqlDelete = "DELETE i FROM i18n i
                LEFT JOIN `{$tableName}` p ON p.id = i.foreign_key
                WHERE i.model = :model AND p.id IS NULL";
            $sqlCount = "SELECT COUNT(*) AS c FROM i18n i
                LEFT JOIN `{$tableName}` p ON p.id = i.foreign_key
                WHERE i.model = :model AND p.id IS NULL";

            $count = $this->countOrDelete(
                $conn,
                $sqlDelete,
                ['model' => $model],
                $dryRun,
                $sqlCount,
                ['model' => $model]
            );
            $io->out('  Orphans ' . ($dryRun ? "would delete: {$count}" : "deleted: {$count}"));
            $totalDeleted += $count;
        }

        $after = $dryRun
            ? $before - $totalDeleted
            : (int)$conn->execute('SELECT COUNT(*) AS c FROM i18n')->fetch('assoc')['c'];

        $io->out('');
        $io->out('i18n rows after: ' . ($dryRun ? "~{$after}" : (string)$after)
            . ' (' . ($dryRun ? 'would delete' : 'deleted') . " {$totalDeleted})");

        foreach ($this->modelTables as $model => $table) {
            $tableExists = $conn->execute('SHOW TABLES LIKE :t', ['t' => $table])->fetchAll() !== [];
            if (!$tableExists) {
                continue;
            }
            $alive = (int)$conn->execute("SELECT COUNT(*) AS c FROM `{$table}`")->fetch('assoc')['c'];
            $i18nKeys = (int)$conn->execute(
                'SELECT COUNT(DISTINCT foreign_key) AS c FROM i18n WHERE model = :m',
                ['m' => $model]
            )->fetch('assoc')['c'];
            $i18nRows = (int)$conn->execute(
                'SELECT COUNT(*) AS c FROM i18n WHERE model = :m',
                ['m' => $model]
            )->fetch('assoc')['c'];
            $io->out("{$model}: parent={$alive}, i18n_fks={$i18nKeys}, i18n_rows={$i18nRows}");
        }

        if ($totalDeleted === 0) {
            $io->success('No orphan i18n rows found.');
        } else {
            $io->success($dryRun ? 'Dry-run finished.' : 'Orphan cleanup finished.');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Database\Connection $conn
     * @param array<string, mixed> $deleteParams
     * @param array<string, mixed> $countParams
     */
    protected function countOrDelete(
        \Cake\Database\Connection $conn,
        string $deleteSql,
        array $deleteParams,
        bool $dryRun,
        string $countSql,
        array $countParams
    ): int {
        if ($dryRun) {
            return (int)$conn->execute($countSql, $countParams)->fetch('assoc')['c'];
        }

        return $conn->execute($deleteSql, $deleteParams)->rowCount();
    }
}
