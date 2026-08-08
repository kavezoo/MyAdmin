<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Admin text search helpers (index + global header).
 * Field map: config/admin_search.php → Configure key AdminSearch.
 *
 * Translate-aware: for tables with Translate behavior, LIKE runs on
 * `translationField()` (UI locale) and also the canonical column (fallback).
 */
class AdminSearch
{
    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        $cfg = Configure::read('AdminSearch');

        return is_array($cfg) ? $cfg : [];
    }

    public static function queryParam(): string
    {
        $param = static::config()['queryParam'] ?? 'q';

        return is_string($param) && $param !== '' ? $param : 'q';
    }

    /**
     * @return array<string, array{label: string, controller: string, titleField: string, fields: list<string>}>
     */
    public static function models(): array
    {
        $models = static::config()['models'] ?? [];

        return is_array($models) ? $models : [];
    }

    /**
     * @return list<string>
     */
    public static function fieldsFor(string $modelAlias): array
    {
        $models = static::models();
        $fields = $models[$modelAlias]['fields'] ?? [];
        if (!is_array($fields)) {
            return [];
        }

        return array_values(array_filter($fields, static fn ($f) => is_string($f) && $f !== ''));
    }

    /**
     * Apply OR LIKE filters on the model's configured text fields.
     * Translated fields: UI locale content (+ canonical fallback).
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public static function applyToQuery(SelectQuery $query, Table $table, string $term): SelectQuery
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $alias = $table->getAlias();
        $fields = static::fieldsFor($alias);
        if ($fields === []) {
            return $query;
        }

        AdminTranslate::applyLocale($table);

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $or = [];
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                $or[$field . ' LIKE'] = $like;
                continue;
            }
            if (AdminTranslate::isTranslatedField($table, $field)) {
                $translated = $table->getBehavior('Translate')->translationField($field);
                $canonical = $table->aliasField($field);
                $or[$translated . ' LIKE'] = $like;
                if ($translated !== $canonical) {
                    $or[$canonical . ' LIKE'] = $like;
                }
                continue;
            }
            $or[$alias . '.' . $field . ' LIKE'] = $like;
        }

        return $query->where(['OR' => $or]);
    }

    /**
     * Global search across configured models (combined list for pagination).
     *
     * Country-scoped tables: non-superuser → own country; superuser → working country
     * (AdminCountry::id). Models without country_id are unchanged.
     *
     * @return list<array{model: string, label: string, controller: string, labelsKey: string, id: int|string, title: string}>
     */
    public static function searchAll(string $term, ?int $limitPerModel = null): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $cfg = static::config();
        $limit = $limitPerModel ?? (int)($cfg['globalLimitPerModel'] ?? 200);
        if ($limit < 1) {
            $limit = 200;
        }
        $maxTotal = (int)($cfg['globalMaxResults'] ?? 1000);
        if ($maxTotal < 1) {
            $maxTotal = 1000;
        }

        $results = [];
        $locator = TableRegistry::getTableLocator();
        $request = Router::getRequest();
        $scopeCountryId = AdminCountryScope::canChangeCountry($request)
            ? AdminCountry::id($request)
            : AdminCountryScope::ownCountryId($request);

        foreach (static::models() as $alias => $meta) {
            if (!is_array($meta)) {
                continue;
            }
            if (array_key_exists('includeInGlobal', $meta) && $meta['includeInGlobal'] === false) {
                continue;
            }
            try {
                $table = $locator->get($alias);
            } catch (\Throwable $e) {
                continue;
            }

            if ($table->hasBehavior('Translate')) {
                AdminTranslate::applyLocale($table);
            }

            $query = static::applyToQuery($table->find(), $table, $term);
            if ($alias === 'Countries') {
                $cond = AdminCountryScope::countriesIndexCondition($request);
                if ($cond !== []) {
                    $query->where($cond);
                }
            } elseif ($table->getSchema()->hasColumn('country_id') && $scopeCountryId > 0) {
                $query->where([$table->aliasField('country_id') => $scopeCountryId]);
            }

            $titleField = (string)($meta['titleField'] ?? 'name');
            $controller = (string)($meta['controller'] ?? $alias);
            $label = (string)($meta['label'] ?? $alias);
            $labelsKey = (string)($meta['labelsKey'] ?? '');
            $pk = $table->getPrimaryKey();
            if (!is_string($pk) || $pk === '') {
                continue;
            }

            foreach ($query->limit($limit)->all() as $entity) {
                $rawId = $entity->get($pk);
                if ($rawId === null || $rawId === '') {
                    continue;
                }
                $id = is_numeric($rawId) ? (int)$rawId : (string)$rawId;
                if (is_int($id) && $id < 1) {
                    continue;
                }
                $title = (string)$entity->get($titleField);
                if ($title === '') {
                    $title = '#' . $id;
                }
                $results[] = [
                    'model' => $alias,
                    'label' => $label,
                    'controller' => $controller,
                    'labelsKey' => $labelsKey,
                    'id' => $id,
                    'title' => $title,
                ];
                if (count($results) >= $maxTotal) {
                    return $results;
                }
            }
        }

        return $results;
    }

    public static function globalPageLimit(): int
    {
        $limit = (int)(static::config()['globalPageLimit'] ?? 20);

        return $limit > 0 ? $limit : 20;
    }
}