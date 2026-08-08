<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Routing\Router;

/**
 * Admin list country scope:
 * - regular admin: always own Users.country_id (no country switcher)
 * - superuser: working country + Select2; options = countries that have ≥1 row on the current table
 */
final class AdminCountryScope
{
    /**
     * Superuser may switch working country; other Admin roles may not.
     */
    public static function canChangeCountry(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    /**
     * Logged-in user's country (0 if unset).
     */
    public static function ownCountryId(?ServerRequest $request = null): int
    {
        return CurrentUser::countryId($request);
    }

    /**
     * Distinct country_id values that appear on the table (child / scoped rows).
     *
     * @return list<int>
     */
    public static function countryIdsWithRecords(Table $table, string $field = 'country_id'): array
    {
        if (!$table->getSchema()->hasColumn($field)) {
            return [];
        }

        $alias = $table->getAlias();
        $col = $alias . '.' . $field;

        try {
            $rows = $table->find()
                ->select([$field => $col])
                ->distinct([$col])
                ->where([
                    $col . ' IS NOT' => null,
                    $col . ' >' => 0,
                ])
                ->disableHydration()
                ->all();
        } catch (\Throwable) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row[$field] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    /**
     * Country Select2 options for the current table (only countries with rows).
     *
     * @return array<int, string>
     */
    public static function optionsWithRecords(Table $table, string $field = 'country_id'): array
    {
        return AdminCountry::filterOptionsByCountryIds(
            AdminCountry::options(),
            self::countryIdsWithRecords($table, $field)
        );
    }

    /**
     * Resolve the country id that must scope Admin index / search for this table.
     *
     * Non-superuser: own country (even if that country has zero rows — empty list).
     * Superuser: query (if in countries-with-rows) → working country (if in list) → first with rows → 0.
     */
    public static function resolveCountryId(
        ?ServerRequest $request,
        Table $table,
        string $field = 'country_id',
    ): int {
        $request ??= Router::getRequest();

        if (!self::canChangeCountry($request)) {
            return self::ownCountryId($request);
        }

        $options = self::optionsWithRecords($table, $field);
        $allowed = array_map('intval', array_keys($options));
        if ($allowed === []) {
            return 0;
        }

        $queryId = self::queryCountryId($request);
        if ($queryId > 0 && in_array($queryId, $allowed, true)) {
            return $queryId;
        }

        $working = AdminCountry::id($request);
        if ($working > 0 && in_array($working, $allowed, true)) {
            return $working;
        }

        return $allowed[0];
    }

    /**
     * @return array{
     *   countryId: int,
     *   canChange: bool,
     *   options: array<int, string>,
     *   label: string
     * }
     */
    public static function scopeForTable(
        ?ServerRequest $request,
        Table $table,
        string $field = 'country_id',
    ): array {
        $request ??= Router::getRequest();
        $canChange = self::canChangeCountry($request);
        $countryId = self::resolveCountryId($request, $table, $field);
        $options = $canChange ? self::optionsWithRecords($table, $field) : [];

        return [
            'countryId' => $countryId,
            'canChange' => $canChange,
            'options' => $options,
            'label' => $countryId > 0 ? AdminCountry::label($countryId) : '',
        ];
    }

    /**
     * Whether an entity is inside the allowed country for this Admin user.
     */
    public static function entityAllowed(
        EntityInterface $entity,
        ?ServerRequest $request = null,
        string $field = 'country_id',
    ): bool {
        $request ??= Router::getRequest();
        if (self::canChangeCountry($request)) {
            return true;
        }
        $own = self::ownCountryId($request);
        if ($own < 1) {
            return false;
        }

        return (int)$entity->get($field) === $own;
    }

    /**
     * Countries table: non-superuser may only see / open their own country row.
     */
    public static function countriesIndexCondition(?ServerRequest $request = null): array
    {
        $request ??= Router::getRequest();
        if (self::canChangeCountry($request)) {
            return [];
        }
        $own = self::ownCountryId($request);
        if ($own < 1) {
            return ['Countries.id' => 0];
        }

        return ['Countries.id' => $own];
    }

    public static function queryCountryId(?ServerRequest $request = null): int
    {
        $request ??= Router::getRequest();
        if ($request === null) {
            return 0;
        }
        $raw = $request->getQuery('country_id');
        if (is_array($raw)) {
            $raw = end($raw);
        }
        $id = (int)$raw;

        return $id > 0 ? $id : 0;
    }
}
