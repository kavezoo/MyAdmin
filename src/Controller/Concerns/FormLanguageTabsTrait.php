<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\FormLanguages;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * Language tabs for Translate EAV forms (Admin + role panels).
 *
 * Tabs = active country’s `country_visibilities` (own + additional languages).
 */
trait FormLanguageTabsTrait
{
    /**
     * @param int|null $activeCountryId Country whose visibility languages drive the tabs
     * @return void
     */
    protected function setFormLanguageTabs(?int $activeCountryId = null): void
    {
        $tabs = FormLanguages::tabs($activeCountryId);
        $this->set('formLanguageTabs', $tabs);
        $this->set('formDefaultLocale', FormLanguages::defaultLocaleForForm($activeCountryId));
    }

    /**
     * Load entity with all Translate EAV rows (edit form).
     * Root fields use the form default locale for that country
     * (must match TranslateBehavior `defaultLocale` / main-table locale).
     *
     * @param \Cake\ORM\Table $table
     * @param mixed $id
     * @param array<string, mixed>|list<string> $contain
     * @param int|null $activeCountryId
     * @return \Cake\Datasource\EntityInterface
     */
    protected function getWithTranslations(
        Table $table,
        mixed $id,
        array $contain = [],
        ?int $activeCountryId = null
    ): EntityInterface {
        if (!$table->hasBehavior('Translate')) {
            return $table->get($id, contain: $contain);
        }

        $defaultLocale = FormLanguages::defaultLocaleForForm($activeCountryId);
        $this->setTranslateLocale($table, $defaultLocale);

        $pk = $table->aliasField($table->getPrimaryKey());
        $query = $table->find('translations')->where([$pk => $id]);
        if ($contain !== []) {
            $query->contain($contain);
        }

        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $query->firstOrFail();

        return $entity;
    }

    /**
     * Set Translate locale before patch/save so root fields hit the main table
     * (EAV defaultLocale) and `_translations.*` hit i18n.
     *
     * @param \Cake\ORM\Table $table
     * @param int|null $activeCountryId
     * @return string Locale applied
     */
    protected function setFormTranslateLocale(Table $table, ?int $activeCountryId = null): string
    {
        $locale = FormLanguages::defaultLocaleForForm($activeCountryId);
        $this->setTranslateLocale($table, $locale);

        return $locale;
    }

    /**
     * Drop blank `_translations.{locale}.{field}` values so Cake does not persist
     * empty i18n rows that would hide the main-table (default locale) text.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function scrubEmptyTranslations(array $data): array
    {
        if (!isset($data['_translations']) || !is_array($data['_translations'])) {
            return $data;
        }

        foreach ($data['_translations'] as $locale => $fields) {
            if (!is_array($fields)) {
                unset($data['_translations'][$locale]);
                continue;
            }
            foreach ($fields as $field => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $plain = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($plain === '') {
                    unset($data['_translations'][$locale][$field]);
                }
            }
            if ($data['_translations'][$locale] === []) {
                unset($data['_translations'][$locale]);
            }
        }
        if ($data['_translations'] === []) {
            unset($data['_translations']);
        }

        return $data;
    }

    /**
     * @param \Cake\ORM\Table $table
     * @param string $locale
     * @return void
     */
    protected function setTranslateLocale(Table $table, string $locale): void
    {
        if (!$table->hasBehavior('Translate')) {
            return;
        }

        $table->getBehavior('Translate')->setLocale($locale);
    }
}
