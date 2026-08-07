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
     * Load enabled template for user locale (with en_GB / first enabled fallback).
     *
     * @return array{subject: string, body_html: string, body_text: string}|null
     */
    public static function renderForUser(mixed $user, string $slug, array $vars): ?array
    {
        $locale = static::localeForUser($user);
        $languageId = static::languageIdForLocale($locale);
        $template = static::findTemplate($languageId, $slug);
        if ($template === null && $locale !== 'en_GB') {
            $template = static::findTemplate(static::languageIdForLocale('en_GB'), $slug);
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

    protected static function findTemplate(int $languageId, string $slug): ?EntityInterface
    {
        if ($languageId < 1) {
            return null;
        }
        /** @var \App\Model\Table\EmailTemplatesTable $table */
        $table = (new self())->fetchTable('EmailTemplates');

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
