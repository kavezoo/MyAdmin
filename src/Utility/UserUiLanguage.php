<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Persist the UI language chosen at login onto `users.language_id`
 * (used for membership emails and profile display).
 */
final class UserUiLanguage
{
    use LocatorAwareTrait;

    /**
     * After successful login: store session/cookie/current UI locale as users.language_id.
     *
     * @return int Saved language_id (0 if unchanged / unavailable)
     */
    public static function syncFromLoginRequest(string $userId, ?ServerRequest $request): int
    {
        $userId = trim($userId);
        if ($userId === '') {
            return 0;
        }

        $locale = '';
        if ($request !== null) {
            $fromSession = $request->getSession()->read(BrowserLocale::SESSION_KEY);
            if (is_string($fromSession) && $fromSession !== '') {
                $locale = $fromSession;
            }
            if ($locale === '') {
                $fromCookie = $request->getCookie(BrowserLocale::COOKIE_NAME);
                if (is_string($fromCookie) && $fromCookie !== '') {
                    $locale = $fromCookie;
                }
            }
        }
        if ($locale === '') {
            $locale = (string)I18n::getLocale();
        }

        $canonical = BrowserLocale::canonicalize($locale);
        if ($canonical !== null) {
            $locale = $canonical;
        }

        $languageId = AdminLanguage::idForLocale($locale);
        if ($languageId < 1) {
            return 0;
        }

        try {
            /** @var \App\Model\Table\UsersTable $users */
            $users = (new self())->fetchTable('Users');
            $user = $users->get($userId);
            if ((int)($user->get('language_id') ?? 0) === $languageId) {
                return $languageId;
            }
            $user->set('language_id', $languageId);
            $users->save($user, [
                'checkRules' => false,
                'accessibleFields' => [
                    'language_id' => true,
                    'modified' => true,
                ],
            ]);

            return $languageId;
        } catch (\Throwable $e) {
            Log::warning('Failed to sync users.language_id on login: ' . $e->getMessage(), [
                'scope' => ['auth'],
            ]);

            return 0;
        }
    }
}
