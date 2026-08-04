<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Form language tabs from visible Countries (by pos), English first.
 *
 * One tab per language short code (EN, HU, DE, SK, …) — first visible country
 * for that language (lowest `pos`) wins; its `locale` is the Translate key.
 */
class FormLanguages
{
    use LocatorAwareTrait;

    /**
     * @return list<array{locale: string, code: string, iso2: string, country_id: int}>
     */
    public static function tabs(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.iso2', 'Countries.locale', 'Countries.pos'])
            ->where([
                'Countries.visible' => true,
                'Countries.locale IS NOT' => null,
                'Countries.locale !=' => '',
            ])
            ->orderBy(['Countries.pos' => 'ASC', 'Countries.id' => 'ASC'])
            ->all();

        $byCode = [];
        foreach ($rows as $row) {
            $locale = trim((string)$row->get('locale'));
            if ($locale === '') {
                continue;
            }
            $code = static::shortCode($locale);
            if ($code === '' || isset($byCode[$code])) {
                continue;
            }
            $byCode[$code] = [
                'locale' => $locale,
                'code' => $code,
                'iso2' => strtoupper(trim((string)$row->get('iso2'))),
                'country_id' => (int)$row->get('id'),
                '_english' => static::isEnglish($locale, $code),
                '_pos' => (int)$row->get('pos'),
            ];
        }

        $tabs = array_values($byCode);
        usort($tabs, static function (array $a, array $b): int {
            if ($a['_english'] !== $b['_english']) {
                return $a['_english'] ? -1 : 1;
            }
            if ($a['_pos'] !== $b['_pos']) {
                return $a['_pos'] <=> $b['_pos'];
            }

            return strcmp($a['code'], $b['code']);
        });

        return array_map(static function (array $tab): array {
            unset($tab['_english'], $tab['_pos']);

            return $tab;
        }, $tabs);
    }

    /**
     * @return list<string>
     */
    public static function locales(): array
    {
        return array_column(static::tabs(), 'locale');
    }

    public static function shortCode(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $primary = explode('_', $locale, 2)[0] ?? $locale;

        return strtoupper($primary);
    }

    public static function isEnglish(string $locale, ?string $code = null): bool
    {
        $code ??= static::shortCode($locale);

        return $code === 'EN';
    }
}
