<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Resolve recipient email/UI language and load DB email templates.
 *
 * Priority for language:
 * 1. users.language_id → languages.code
 * 2. users.country_id → countries.locale
 * 3. App default locale
 *
 * Templates are scoped by recipient country_id + language.
 */
class EmailTemplateService
{
    use LocatorAwareTrait;

    /**
     * Locale code (e.g. hu_HU) for a user entity / array-like.
     */
    public static function localeForUser(mixed $user): string
    {
        $languageId = 0;
        $countryId = 0;
        if ($user instanceof EntityInterface) {
            $languageId = (int)($user->get('language_id') ?? 0);
            $countryId = (int)($user->get('country_id') ?? 0);
        } elseif (is_array($user)) {
            $languageId = (int)($user['language_id'] ?? 0);
            $countryId = (int)($user['country_id'] ?? 0);
        }

        if ($languageId > 0) {
            $code = static::languageCodeById($languageId);
            if ($code !== '') {
                return $code;
            }
        }

        if ($countryId > 0) {
            $countryLocale = AdminCountry::localeForCountry($countryId);
            if ($countryLocale !== null && $countryLocale !== '') {
                return $countryLocale;
            }
        }

        return (string)\Cake\Core\Configure::read('App.defaultLocale') ?: 'en_GB';
    }

    /**
     * Country id for a user entity / array-like.
     */
    public static function countryIdForUser(mixed $user): int
    {
        if ($user instanceof EntityInterface) {
            return (int)($user->get('country_id') ?? 0);
        }
        if (is_array($user)) {
            return (int)($user['country_id'] ?? 0);
        }

        return 0;
    }

    public static function languageIdForLocale(string $locale): int
    {
        $locale = trim($locale);
        if ($locale === '') {
            return 0;
        }
        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        $row = $languages->find()
            ->select(['Languages.id'])
            ->where(['Languages.code' => $locale])
            ->first();
        if ($row !== null) {
            return (int)$row->get('id');
        }
        // Fallback: language part only (hu_HU → try hu)
        $lang = substr($locale, 0, 2);
        $row = $languages->find()
            ->select(['Languages.id'])
            ->where(['Languages.code LIKE' => $lang . '_%'])
            ->orderBy(['Languages.pos' => 'ASC', 'Languages.id' => 'ASC'])
            ->first();

        return $row !== null ? (int)$row->get('id') : 0;
    }

    /**
     * Exact `languages.code` → id (no prefix fallback).
     */
    public static function languageIdForCodeExact(string $code): int
    {
        $code = str_replace('-', '_', trim($code));
        if ($code === '') {
            return 0;
        }
        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        $row = $languages->find()
            ->select(['Languages.id'])
            ->where(['Languages.code' => $code])
            ->first();

        return $row !== null ? (int)$row->get('id') : 0;
    }

    /**
     * language_id => label for locales that have email templates (seed / form tabs).
     *
     * @return array<int, string>
     */
    public static function templateLanguageOptions(): array
    {
        $options = [];
        foreach (EmailTemplateDefaults::locales() as $locale) {
            $id = static::languageIdForCodeExact($locale);
            if ($id < 1) {
                continue;
            }
            $options[$id] = AdminLanguage::labelById($id);
        }

        return $options;
    }

    /**
     * Map UI locale to an email-template language_id (en_US → en_GB, not en_US).
     */
    public static function templateLanguageIdForLocale(?string $locale = null): int
    {
        $locale = str_replace(
            '-',
            '_',
            trim($locale !== null && $locale !== ''
                ? $locale
                : (string)\Cake\I18n\I18n::getLocale())
        );
        $byCode = [];
        foreach (EmailTemplateDefaults::locales() as $code) {
            $id = static::languageIdForCodeExact($code);
            if ($id > 0) {
                $byCode[$code] = $id;
            }
        }
        if ($byCode === []) {
            return 0;
        }
        if ($locale !== '' && isset($byCode[$locale])) {
            return $byCode[$locale];
        }
        $lang = $locale !== '' ? substr($locale, 0, 2) : '';
        if ($lang !== '') {
            foreach ($byCode as $code => $id) {
                if (str_starts_with($code, $lang . '_')) {
                    return $id;
                }
            }
        }

        return (int)reset($byCode);
    }

    public static function languageCodeById(int $languageId): string
    {
        if ($languageId < 1) {
            return '';
        }
        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        $row = $languages->find()
            ->select(['Languages.code'])
            ->where(['Languages.id' => $languageId])
            ->first();

        return $row !== null ? trim((string)$row->get('code')) : '';
    }

    /**
     * Load enabled template for user country + locale (with country-locale / en_GB fallback).
     *
     * @return array{subject: string, body_html: string, body_text: string}|null
     */
    public static function renderForUser(mixed $user, string $slug, array $vars): ?array
    {
        $countryId = static::countryIdForUser($user);
        $locale = static::localeForUser($user);
        $languageId = static::languageIdForLocale($locale);
        $template = static::findTemplate($countryId, $languageId, $slug);

        if ($template === null && $countryId > 0) {
            $countryLocale = AdminCountry::localeForCountry($countryId);
            if ($countryLocale !== null && $countryLocale !== '' && $countryLocale !== $locale) {
                $template = static::findTemplate(
                    $countryId,
                    static::languageIdForLocale($countryLocale),
                    $slug
                );
            }
        }
        if ($template === null && $countryId > 0 && $locale !== 'en_GB') {
            $template = static::findTemplate(
                $countryId,
                static::languageIdForLocale('en_GB'),
                $slug
            );
        }
        if ($template === null) {
            return null;
        }

        return [
            'subject' => static::interpolate((string)$template->get('subject'), $vars),
            'body_html' => static::interpolate((string)$template->get('body_html'), $vars),
            'body_text' => static::interpolate((string)$template->get('body_text'), $vars),
        ];
    }

    protected static function findTemplate(int $countryId, int $languageId, string $slug): ?EntityInterface
    {
        if ($languageId < 1 || $slug === '') {
            return null;
        }
        /** @var \App\Model\Table\EmailTemplatesTable $table */
        $table = (new self())->fetchTable('EmailTemplates');
        if ($countryId > 0) {
            return $table->findEnabledByCountryLanguageAndSlug($countryId, $languageId, $slug);
        }

        return $table->findEnabledByLanguageAndSlug($languageId, $slug);
    }

    /**
     * Replace {key} placeholders (also supports {{key}}).
     *
     * @param array<string, scalar|null> $vars
     */
    public static function interpolate(string $text, array $vars): string
    {
        $replace = [];
        foreach ($vars as $key => $value) {
            $str = $value === null ? '' : (string)$value;
            $replace['{' . $key . '}'] = $str;
            $replace['{{' . $key . '}}'] = $str;
        }

        return strtr($text, $replace);
    }
}
