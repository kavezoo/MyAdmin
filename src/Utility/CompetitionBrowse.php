<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;

/**
 * Browse country + “active competition” filters for Member / Clubpresident lists.
 *
 * Active = visible and not ended (`end_datetime` NULL or ≥ now).
 * Browse country defaults to the user’s / club’s country; Select2 can switch to any visible country
 * so members may apply abroad and club presidents can manage those applications.
 */
final class CompetitionBrowse
{
    public const SESSION_MEMBER = 'Member.Competitions.browseCountryId';

    public const SESSION_CLUBPRESIDENT_APPLICANTS = 'Clubpresident.CompetitionApplicants.browseCountryId';

    public const SESSION_CLUBPRESIDENT_TEAMS = 'Clubpresident.CompetitionTeams.browseCountryId';

    /**
     * WHERE fragment: visible + not ended.
     *
     * @return array<string, mixed>
     */
    public static function activeConditions(string $alias = 'Competitions', DateTime|string|null $now = null): array
    {
        $nowStr = $now instanceof DateTime
            ? $now->format('Y-m-d H:i:s')
            : (is_string($now) && $now !== '' ? $now : DateTime::now()->format('Y-m-d H:i:s'));

        return [
            $alias . '.visible' => true,
            'OR' => [
                $alias . '.end_datetime IS' => null,
                $alias . '.end_datetime >=' => $nowStr,
            ],
        ];
    }

    /**
     * Resolve browse country: ?country_id= → session → default (home country).
     */
    public static function resolveCountryId(
        ServerRequest $request,
        int $defaultCountryId,
        string $sessionKey,
    ): int {
        $session = $request->getSession();
        $query = $request->getQuery('country_id');

        if ($query !== null) {
            $raw = is_array($query) ? end($query) : $query;
            $id = (int)$raw;
            if ($id > 0 && AdminCountry::isValidCountryId($id)) {
                $session->write($sessionKey, $id);

                return $id;
            }
            $session->delete($sessionKey);

            return max(0, $defaultCountryId);
        }

        $saved = $session->read($sessionKey);
        if ($saved !== null && is_numeric($saved)) {
            $id = (int)$saved;
            if ($id > 0 && AdminCountry::isValidCountryId($id)) {
                return $id;
            }
            $session->delete($sessionKey);
        }

        return max(0, $defaultCountryId);
    }

    /**
     * Visible countries for the browse Select2.
     *
     * @return array<int, string>
     */
    public static function countryOptions(?string $locale = null): array
    {
        return AdminCountry::options($locale);
    }
}
