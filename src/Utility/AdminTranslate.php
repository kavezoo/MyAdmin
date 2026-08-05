<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\I18n;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Locale-aware Translate helpers for Admin index search / sort.
 *
 * Cake Translate overlays entity fields for display, but WHERE/ORDER on
 * `Alias.name` still hits the canonical (usually English) column.
 *
 * Sort uses `Alias_field_translation.content` then `Alias.field` (fallback).
 * Do **not** use COALESCE(…) as an order key — Cake Paginator `_prefix()`
 * splits on `.` and mangles multi-dot expressions.
 */
class AdminTranslate
{
    /**
     * Normalized locale matching `i18n.locale` keys (e.g. hu_HU, en_GB).
     */
    public static function locale(?string $locale = null): string
    {
        return AdminCountry::normalizeTranslateLocale($locale ?? I18n::getLocale());
    }

    /**
     * Set Translate behavior locale on a table (no-op if no Translate).
     */
    public static function applyLocale(Table $table, ?string $locale = null): void
    {
        if (!$table->hasBehavior('Translate')) {
            return;
        }
        $table->getBehavior('Translate')->setLocale(static::locale($locale));
    }

    /**
     * Apply UI locale to all known Translate tables (Countries, Continents, Samples, Parents).
     *
     * @param list<string>|null $aliases
     */
    public static function applyLocales(?array $aliases = null, ?string $locale = null): void
    {
        $aliases ??= ['Countries', 'Continents', 'Samples', 'Parents', 'Setups'];
        $locator = TableRegistry::getTableLocator();
        foreach ($aliases as $alias) {
            if (!is_string($alias) || $alias === '') {
                continue;
            }
            try {
                static::applyLocale($locator->get($alias), $locale);
            } catch (\Throwable) {
                // Table may be missing in a stripped project
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function translatedFieldNames(Table $table): array
    {
        if (!$table->hasBehavior('Translate')) {
            return [];
        }
        $fields = $table->getBehavior('Translate')->getConfig('fields');
        if (!is_array($fields)) {
            return [];
        }

        return array_values(array_filter($fields, static fn ($f) => is_string($f) && $f !== ''));
    }

    public static function isTranslatedField(Table $table, string $field): bool
    {
        return in_array($field, static::translatedFieldNames($table), true);
    }

    /**
     * SQL ORDER BY identifiers for a field (translation content, then canonical).
     *
     * @return list<string>
     */
    public static function orderFieldList(Table $table, string $field): array
    {
        static::applyLocale($table);
        if (!static::isTranslatedField($table, $field)) {
            return [$table->aliasField($field)];
        }

        $translated = $table->getBehavior('Translate')->translationField($field);
        $canonical = $table->aliasField($field);
        if ($translated === $canonical) {
            return [$canonical];
        }

        return [$translated, $canonical];
    }

    /**
     * Primary SQL identifier (translated content when applicable).
     */
    public static function orderField(Table $table, string $field): string
    {
        return static::orderFieldList($table, $field)[0];
    }

    /**
     * Remap paginator `sortableFields` so URL keys stay logical (`name`)
     * but ORDER BY uses translated content (+ canonical fallback).
     *
     * @param list<string> $fields Logical sort keys (as in Paginator->sort())
     * @param array<string, \Cake\ORM\Table> $associationTables e.g. ['Parents' => $parentsTable]
     * @return array<int|string, list<string>|string>
     */
    public static function sortableFields(Table $table, array $fields, array $associationTables = []): array
    {
        static::applyLocale($table);
        foreach ($associationTables as $assocTable) {
            if ($assocTable instanceof Table) {
                static::applyLocale($assocTable);
            }
        }

        $out = [];
        foreach ($fields as $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            $sqlFields = static::resolveLogicalFieldList($field, $table, $associationTables);
            if ($sqlFields === [$field]) {
                $out[] = $field;
            } else {
                $out[$field] = $sqlFields;
            }
        }

        return $out;
    }

    /**
     * Remap default `order` clause keys to locale-aware SQL identifiers.
     *
     * @param array<string, string> $order
     * @param array<string, \Cake\ORM\Table> $associationTables
     * @return array<string, string>
     */
    public static function remapOrder(Table $table, array $order, array $associationTables = []): array
    {
        static::applyLocale($table);
        foreach ($associationTables as $assocTable) {
            if ($assocTable instanceof Table) {
                static::applyLocale($assocTable);
            }
        }

        $out = [];
        foreach ($order as $field => $dir) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            foreach (static::resolveLogicalFieldList($field, $table, $associationTables) as $sql) {
                $out[$sql] = $dir;
            }
        }

        return $out;
    }

    /**
     * @param array<string, \Cake\ORM\Table> $associationTables
     * @return list<string>
     */
    public static function resolveLogicalFieldList(string $field, Table $table, array $associationTables = []): array
    {
        $alias = $table->getAlias();

        if (str_contains($field, '.')) {
            [$prefix, $fieldName] = explode('.', $field, 2);
            if ($prefix === $alias) {
                if (static::isTranslatedField($table, $fieldName)) {
                    return static::orderFieldList($table, $fieldName);
                }

                return [$field];
            }

            $assocTable = null;
            if (isset($associationTables[$field]) && $associationTables[$field] instanceof Table) {
                $assocTable = $associationTables[$field];
            } elseif (isset($associationTables[$prefix]) && $associationTables[$prefix] instanceof Table) {
                $assocTable = $associationTables[$prefix];
            } elseif ($table->hasAssociation($prefix)) {
                $assocTable = $table->getAssociation($prefix)->getTarget();
            }
            if ($assocTable instanceof Table && static::isTranslatedField($assocTable, $fieldName)) {
                return static::orderFieldList($assocTable, $fieldName);
            }

            return [$field];
        }

        if (static::isTranslatedField($table, $field)) {
            return static::orderFieldList($table, $field);
        }

        return [$field];
    }

    /**
     * @param array<string, \Cake\ORM\Table> $associationTables
     */
    public static function resolveLogicalField(string $field, Table $table, array $associationTables = []): string
    {
        return static::resolveLogicalFieldList($field, $table, $associationTables)[0];
    }
}
