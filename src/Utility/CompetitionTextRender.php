<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Competition;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;

/**
 * Competition announcement text: {{placeholders}} / {placeholders} → live DB values on every display.
 *
 * Stored HTML keeps tokens (e.g. {{club_logo}}, {{map}}, {{title}}).
 * Empty values leave the token in place. In src/href, logo/image tokens resolve to URLs.
 * {{map}} becomes a slot marker for the view (map widget lives outside html iframe).
 * Dates / numbers / Yes-No use the active UI locale (or an explicit $locale).
 */
class CompetitionTextRender
{
    public const MAP_SLOT = '<!--COMPETITION_MAP_SLOT-->';

    /**
     * Scalar / HTML vars available inside announcement text.
     *
     * @return array<string, string>
     */
    public static function vars(Competition $competition, ?string $locale = null): array
    {
        $locale = $locale ?: I18n::getLocale();
        $previousLocale = I18n::getLocale();
        if ($locale !== $previousLocale) {
            I18n::setLocale($locale);
        }

        try {
            return static::buildVars($competition, $locale);
        } finally {
            if ($locale !== $previousLocale) {
                I18n::setLocale($previousLocale);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function buildVars(Competition $competition, string $locale): array
    {
        $club = $competition->get('club');
        $city = $competition->get('city');
        $country = $competition->get('country');

        $clubId = (int)($competition->get('club_id') ?? 0);
        $countryId = (int)($competition->get('country_id') ?? 0);
        $clubName = '';
        $clubShort = '';
        $clubLogoPath = null;
        if ($club instanceof EntityInterface) {
            $clubName = trim((string)($club->get('name') ?? ''));
            $clubShort = trim((string)($club->get('short_name') ?? ''));
            $clubLogoPath = $club->get('logo') !== null ? (string)$club->get('logo') : null;
        }
        $clubLogoUrl = ClubLogo::publicUrlFor($clubId, $clubLogoPath);
        $logoHtml = static::logoImgHtml(
            $clubLogoUrl,
            $clubName !== '' ? $clubName : __('Club logo'),
            'competition-club-logo'
        );

        $countryLogoPath = null;
        $associationAlt = MembershipFee::nationalAssociationName($countryId > 0 ? $countryId : null);
        if ($country instanceof EntityInterface) {
            $countryLogoPath = $country->get('logo') !== null ? (string)$country->get('logo') : null;
        }
        $nationalLogoUrl = CountryLogo::publicUrlFor($countryId, $countryLogoPath);
        $nationalLogoHtml = static::logoImgHtml(
            $nationalLogoUrl,
            $associationAlt,
            'competition-national-logo'
        );

        $zip = '';
        $cityName = '';
        if ($city instanceof EntityInterface) {
            $zip = trim((string)($city->get('zip') ?? ''));
            $cityName = trim((string)($city->get('name') ?? ''));
        }

        $venueName = trim((string)($competition->get('venue_name') ?? ''));
        $address = trim((string)($competition->get('venue_address') ?? ''));
        $venueParts = array_values(array_filter([
            $venueName,
            trim($zip . ($zip !== '' && $cityName !== '' ? ' ' : '') . $cityName),
            $address,
        ], static fn (string $p): bool => $p !== ''));
        $venue = implode(', ', $venueParts);

        $national = !empty($competition->get('national_competition'));
        $dt = $competition->get('competition_datetime');

        return [
            'name' => (string)($competition->get('name') ?? ''),
            'title' => (string)($competition->get('title') ?? ''),
            'subtitle' => (string)($competition->get('subtitle') ?? ''),
            'subtitle2' => (string)($competition->get('subtitle2') ?? ''),
            'racing_pipe_1_title' => (string)($competition->get('racing_pipe_1_title') ?? ''),
            'racing_pipe_2_title' => (string)($competition->get('racing_pipe_2_title') ?? ''),
            'racing_pipe_3_title' => (string)($competition->get('racing_pipe_3_title') ?? ''),
            'pipe_type' => (string)($competition->get('pipe_type') ?? ''),
            'pipe_parameters' => (string)($competition->get('pipe_parameters') ?? ''),
            'tobacco_type' => (string)($competition->get('tobacco_type') ?? ''),
            'tobacco_weight' => static::formatTobaccoWeight($competition->get('tobacco_weight'), $locale),
            'minimum_team_size' => LocaleNumberParser::format(
                (int)($competition->get('minimum_team_size') ?? 0),
                $locale,
                0,
            ),
            'national_competition' => $national ? __('Yes') : __('No'),
            'first_date_of_application' => static::formatDate($competition->get('first_date_of_application'), $locale),
            'application_deadline' => static::formatDate($competition->get('application_deadline'), $locale),
            'competition_datetime' => static::formatDateTime($dt, $locale),
            'competition_date' => static::formatDate($dt, $locale),
            'competition_time' => static::formatTime($dt, $locale),
            'club_name' => $clubName,
            'club_short_name' => $clubShort,
            'club_logo' => $logoHtml,
            'club_logo_url' => $clubLogoUrl,
            'national_association_logo' => $nationalLogoHtml,
            'national_association_logo_url' => $nationalLogoUrl,
            'association_logo' => $nationalLogoHtml,
            'association_logo_url' => $nationalLogoUrl,
            'city_name' => $cityName,
            'zip' => $zip,
            'address' => $address,
            'venue_name' => $venueName,
            'venue' => $venue,
            'venue_address' => $address,
            'google_maps_url' => trim((string)($competition->get('google_maps_url') ?? '')),
            'map' => self::MAP_SLOT,
        ] + CompetitionFees::displayVars($competition, $locale);
    }

    /**
     * Transparent-friendly logo HTML for announcement text (empty if no URL).
     */
    protected static function logoImgHtml(string $url, string $alt, string $cssClass): string
    {
        if ($url === '') {
            return '';
        }

        return '<div class="' . h($cssClass) . '-wrap" style="text-align:center;margin:0 0 1.25rem;">'
            . '<img class="' . h($cssClass) . '" src="' . h($url) . '" alt="' . h($alt) . '"'
            . ' style="display:inline-block;max-width:160px;max-height:160px;width:auto;height:auto;'
            . 'object-fit:contain;background:transparent;">'
            . '</div>';
    }

    /**
     * Replace {{token}} / {token} placeholders with live values.
     *
     * - Empty values: leave the placeholder (so missing data stays visible).
     * - In src/href attributes: prefer `*_url` (or extract URL from HTML img vars),
       so templates like `<img src="{{club_logo}}">` work.
     * - Elsewhere: use the normal var (HTML block for logos/images, text otherwise).
     * - Single `{token}` only matches identifier tokens (CSS `{ … }` is ignored).
     */
    public static function interpolate(string $text, array $vars): string
    {
        if ($text === '' || !str_contains($text, '{')) {
            return $text;
        }

        $resolveAttrUrl = static function (string $key) use ($vars): string {
            $urlKey = $key . '_url';
            if (array_key_exists($urlKey, $vars)) {
                return trim((string)$vars[$urlKey]);
            }
            if (!array_key_exists($key, $vars)) {
                return '';
            }
            $raw = (string)$vars[$key];
            if ($raw === '') {
                return '';
            }
            if (!str_contains($raw, '<')
                && (str_starts_with($raw, '/') || str_starts_with($raw, 'http'))
            ) {
                return $raw;
            }
            if (preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $raw, $sm)) {
                return trim((string)$sm[2]);
            }

            return '';
        };

        $replaceAttr = static function (array $m) use ($resolveAttrUrl): string {
            $url = $resolveAttrUrl($m[3]);
            if ($url === '') {
                return $m[0];
            }

            return $m[1] . '=' . $m[2] . $url . $m[2];
        };

        // src="{{club_logo}}" / href='{map}' → URL when available
        $text = (string)preg_replace_callback(
            '/\b(src|href)\s*=\s*(["\'])\{\{\s*([a-zA-Z0-9_]+)\s*\}\}\2/i',
            $replaceAttr,
            $text
        );
        $text = (string)preg_replace_callback(
            '/\b(src|href)\s*=\s*(["\'])\{\s*([a-zA-Z0-9_]+)\s*\}\2/i',
            $replaceAttr,
            $text
        );

        $replaceValue = static function (array $m) use ($vars): string {
            $key = $m[1];
            if (!array_key_exists($key, $vars)) {
                return $m[0];
            }
            $value = (string)$vars[$key];
            if ($value === '') {
                return $m[0];
            }

            return $value;
        };

        $text = (string)preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            $replaceValue,
            $text
        );

        // Single-brace tokens only (not CSS blocks / {{already-handled}})
        return (string)preg_replace_callback(
            '/(?<!\{)\{([a-zA-Z][a-zA-Z0-9_]*)\}(?!\})/',
            $replaceValue,
            $text
        );
    }

    /**
     * Interpolate a competition text field for display (name/title/description/…).
     */
    public static function field(Competition $competition, string $field, ?string $locale = null): string
    {
        $raw = (string)($competition->get($field) ?? '');
        $vars = static::vars($competition, $locale);
        // Avoid recursive {{description}} → full HTML loop when rendering description.
        if ($field === 'description') {
            unset($vars['description']);
        }

        return static::interpolate($raw, $vars);
    }

    /**
     * Description HTML split around map slot for view templates.
     *
     * @return array{0: string, 1: bool, 2: string} before, hasMap, after
     */
    public static function descriptionParts(Competition $competition): array
    {
        $html = static::field($competition, 'description');
        if (!str_contains($html, self::MAP_SLOT)) {
            return [$html, false, ''];
        }
        $parts = explode(self::MAP_SLOT, $html, 2);

        return [$parts[0], true, $parts[1] ?? ''];
    }

    /**
     * Embeddable Google Maps URL (iframe src).
     */
    public static function mapEmbedUrl(Competition $competition): string
    {
        $url = trim((string)($competition->get('google_maps_url') ?? ''));
        if ($url !== '') {
            $embed = static::googleMapsToEmbedUrl($url);
            if ($embed !== '') {
                return $embed;
            }
        }

        $city = $competition->get('city');
        if ($city instanceof EntityInterface) {
            $lat = trim((string)($city->get('lat') ?? ''));
            $lng = trim((string)($city->get('lng') ?? ''));
            if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
                return static::googleMapsEmbedQuery($lat . ',' . $lng, 15);
            }
            $label = trim((string)($city->get('zip') ?? '') . ' ' . (string)($city->get('name') ?? ''));
            $venueName = trim((string)($competition->get('venue_name') ?? ''));
            $address = trim((string)($competition->get('venue_address') ?? ''));
            $q = trim(implode(', ', array_filter([$venueName, $label, $address], static fn (string $p): bool => $p !== '')));
            if ($q !== '') {
                return static::googleMapsEmbedQuery($q, 15);
            }
        }

        $venueName = trim((string)($competition->get('venue_name') ?? ''));
        $address = trim((string)($competition->get('venue_address') ?? ''));
        $q = trim(implode(', ', array_filter([$venueName, $address], static fn (string $p): bool => $p !== '')));
        if ($q !== '') {
            return static::googleMapsEmbedQuery($q, 15);
        }

        return '';
    }

    /**
     * Convert a pasted Google Maps share / place / embed / iframe snippet into an iframe src.
     * Never puts the full https URL into q= (that shows the wrong place).
     */
    public static function googleMapsToEmbedUrl(string $raw): string
    {
        $raw = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($raw === '') {
            return '';
        }

        // Pasted <iframe src="…">
        if (
            preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $raw, $m)
            || (
                !preg_match('#^https?://#i', $raw)
                && preg_match('/\bsrc\s*=\s*["\']([^"\']*google[^"\']*)["\']/i', $raw, $m)
            )
        ) {
            $raw = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if ($raw === '') {
            return '';
        }

        // Already an embed URL
        if (preg_match('#google\.[^/\s]+/maps/embed#i', $raw) || str_contains($raw, 'output=embed')) {
            return static::ensureAbsoluteHttpUrl($raw);
        }

        // Short links → follow redirects then parse
        if (preg_match('#https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $raw)) {
            $resolved = static::resolveHttpRedirect($raw);
            if ($resolved !== '') {
                $raw = $resolved;
            }
        }

        // Place pin coords in data param (!3dLAT!4dLNG) — preferred over map center
        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $raw, $m)) {
            return static::googleMapsEmbedQuery($m[1] . ',' . $m[2], 16);
        }

        // /@lat,lng,zoomz
        if (preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,(\d+(?:\.\d+)?)z)?/i', $raw, $m)) {
            $zoom = isset($m[3]) && $m[3] !== '' ? (int)round((float)$m[3]) : 15;

            return static::googleMapsEmbedQuery($m[1] . ',' . $m[2], $zoom);
        }

        $parts = parse_url($raw);
        if (is_array($parts) && !empty($parts['query'])) {
            parse_str((string)$parts['query'], $qs);
            foreach (['q', 'query', 'destination', 'll'] as $key) {
                if (!empty($qs[$key]) && is_string($qs[$key])) {
                    $q = trim($qs[$key]);
                    if ($q !== '' && !preg_match('#^https?://#i', $q)) {
                        return static::googleMapsEmbedQuery($q, 15);
                    }
                }
            }
        }

        // /place/Name/…
        if (preg_match('#/maps/place/([^/]+)/#u', $raw, $m)) {
            $place = trim(rawurldecode(str_replace('+', ' ', $m[1])));
            if ($place !== '') {
                return static::googleMapsEmbedQuery($place, 15);
            }
        }

        // /maps/search/Query
        if (preg_match('#/maps/search/([^/?#]+)#u', $raw, $m)) {
            $place = trim(rawurldecode(str_replace('+', ' ', $m[1])));
            if ($place !== '') {
                return static::googleMapsEmbedQuery($place, 15);
            }
        }

        // Bare "lat,lng"
        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $raw, $m)) {
            return static::googleMapsEmbedQuery($m[1] . ',' . $m[2], 15);
        }

        // Plain address text (not a Maps URL)
        if (!preg_match('#^https?://#i', $raw)) {
            return static::googleMapsEmbedQuery($raw, 15);
        }

        // Unrecognized google.com/maps URL — do not encode the whole URL as q=
        return '';
    }

    /**
     * @param int $zoom 1–21
     */
    public static function googleMapsEmbedQuery(string $query, int $zoom = 15): string
    {
        $query = trim($query);
        if ($query === '') {
            return '';
        }
        $zoom = max(1, min(21, $zoom));

        return 'https://www.google.com/maps?q=' . rawurlencode($query)
            . '&z=' . $zoom
            . '&output=embed';
    }

    protected static function ensureAbsoluteHttpUrl(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        return $url;
    }

    /**
     * Follow redirects for maps.app.goo.gl / goo.gl short links (best-effort).
     */
    protected static function resolveHttpRedirect(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return '';
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return '';
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 8,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MyAdminMap/1.0)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_RANGE => '0-0',
            ]);
            curl_exec($ch);
            $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);
            if (is_string($final) && $final !== '' && !str_starts_with($final, $url)) {
                return $final;
            }
            if (is_string($final) && preg_match('#google\.[^/]+/maps#i', $final)) {
                return $final;
            }
        }

        return '';
    }

    /**
     * Placeholders for template / description editors (token + short label + help).
     *
     * @return list<array{token: string, label: string, help: string}>
     */
    public static function placeholderHelp(): array
    {
        return [
            [
                'token' => '{{name}}',
                'label' => __('Name'),
                'help' => __('Competition short name (lists, Select2, titles).'),
            ],
            [
                'token' => '{{title}}',
                'label' => __('Title'),
                'help' => __('Main announcement title.'),
            ],
            [
                'token' => '{{subtitle}}',
                'label' => __('Subtitle'),
                'help' => __('First subtitle line.'),
            ],
            [
                'token' => '{{subtitle2}}',
                'label' => __('Subtitle 2'),
                'help' => __('Second subtitle line.'),
            ],
            [
                'token' => '{{club_name}}',
                'label' => __('Club name'),
                'help' => __('Organizer club full name.'),
            ],
            [
                'token' => '{{club_short_name}}',
                'label' => __('Club short name'),
                'help' => __('Organizer club short name.'),
            ],
            [
                'token' => '{{club_logo}}',
                'label' => __('Club logo'),
                'help' => __('Organizer club logo. Standalone → HTML image; in src="…" → image URL.'),
            ],
            [
                'token' => '{{national_association_logo}}',
                'label' => __('National association logo'),
                'help' => __('National pipe association logo. Standalone → HTML image; in src="…" → image URL.'),
            ],
            [
                'token' => '{{city_name}}',
                'label' => __('City'),
                'help' => __('Venue city name.'),
            ],
            [
                'token' => '{{zip}}',
                'label' => __('ZIP'),
                'help' => __('Venue city postal code.'),
            ],
            [
                'token' => '{{venue_name}}',
                'label' => __('Venue name'),
                'help' => __('Building / place name (e.g. culture house).'),
            ],
            [
                'token' => '{{address}}',
                'label' => __('Address'),
                'help' => __('Venue street address (venue_address).'),
            ],
            [
                'token' => '{{venue}}',
                'label' => __('Venue'),
                'help' => __('Combined venue: place name, ZIP, city and address.'),
            ],
            [
                'token' => '{{map}}',
                'label' => __('Map'),
                'help' => __('Embedded Google Map widget at this position.'),
            ],
            [
                'token' => '{{google_maps_url}}',
                'label' => __('Maps URL'),
                'help' => __('Raw Google Maps URL (link text).'),
            ],
            [
                'token' => '{{competition_datetime}}',
                'label' => __('Competition datetime'),
                'help' => __('When the competition takes place (locale date + time).'),
            ],
            [
                'token' => '{{competition_date}}',
                'label' => __('Competition date'),
                'help' => __('Competition date only (locale format).'),
            ],
            [
                'token' => '{{competition_time}}',
                'label' => __('Competition time'),
                'help' => __('Competition time only (locale format).'),
            ],
            [
                'token' => '{{first_date_of_application}}',
                'label' => __('Application from'),
                'help' => __('Application window start date (locale format).'),
            ],
            [
                'token' => '{{application_deadline}}',
                'label' => __('Application deadline'),
                'help' => __('Application window end date (locale format).'),
            ],
            [
                'token' => '{{minimum_team_size}}',
                'label' => __('Min. team size'),
                'help' => __('Minimum members required per sub-team (locale number).'),
            ],
            [
                'token' => '{{national_competition}}',
                'label' => __('National competition'),
                'help' => __('Yes / No — whether it is a national competition.'),
            ],
            [
                'token' => '{{currency}}',
                'label' => __('Currency'),
                'help' => __('ISO 4217 currency code of the competition.'),
            ],
            [
                'token' => '{{entry_fee_member}}',
                'label' => __('Entry fee (member)'),
                'help' => __('Entry fee when national association fee is paid this year.'),
            ],
            [
                'token' => '{{entry_fee_non_member}}',
                'label' => __('Entry fee (non-member)'),
                'help' => __('Entry fee when national association fee is not paid this year.'),
            ],
            [
                'token' => '{{lunch_description}}',
                'label' => __('Lunch description'),
                'help' => __('What lunch is served (translated).'),
            ],
            [
                'token' => '{{lunch_price}}',
                'label' => __('Lunch price'),
                'help' => __('Price per extra lunch (companions / attendants).'),
            ],
            [
                'token' => '{{racing_pipe_1_title}}',
                'label' => __('Racing pipe {0}', 1),
                'help' => __('Label of racing pipe type 1.'),
            ],
            [
                'token' => '{{racing_pipe_1_price_member}}',
                'label' => __('Racing pipe {0} price (member)', 1),
                'help' => __('Unit price of racing pipe 1 for national members.'),
            ],
            [
                'token' => '{{racing_pipe_1_price_non_member}}',
                'label' => __('Racing pipe {0} price (non-member)', 1),
                'help' => __('Unit price of racing pipe 1 for non-members.'),
            ],
            [
                'token' => '{{racing_pipe_1_image}}',
                'label' => __('Racing pipe {0} photo', 1),
                'help' => __('Uploaded photo of racing pipe 1 (HTML img).'),
            ],
            [
                'token' => '{{racing_pipe_2_title}}',
                'label' => __('Racing pipe {0}', 2),
                'help' => __('Label of racing pipe type 2.'),
            ],
            [
                'token' => '{{racing_pipe_2_price_member}}',
                'label' => __('Racing pipe {0} price (member)', 2),
                'help' => __('Unit price of racing pipe 2 for national members.'),
            ],
            [
                'token' => '{{racing_pipe_2_price_non_member}}',
                'label' => __('Racing pipe {0} price (non-member)', 2),
                'help' => __('Unit price of racing pipe 2 for non-members.'),
            ],
            [
                'token' => '{{racing_pipe_2_image}}',
                'label' => __('Racing pipe {0} photo', 2),
                'help' => __('Uploaded photo of racing pipe 2 (HTML img).'),
            ],
            [
                'token' => '{{racing_pipe_3_title}}',
                'label' => __('Racing pipe {0}', 3),
                'help' => __('Label of racing pipe type 3.'),
            ],
            [
                'token' => '{{racing_pipe_3_price_member}}',
                'label' => __('Racing pipe {0} price (member)', 3),
                'help' => __('Unit price of racing pipe 3 for national members.'),
            ],
            [
                'token' => '{{racing_pipe_3_price_non_member}}',
                'label' => __('Racing pipe {0} price (non-member)', 3),
                'help' => __('Unit price of racing pipe 3 for non-members.'),
            ],
            [
                'token' => '{{racing_pipe_3_image}}',
                'label' => __('Racing pipe {0} photo', 3),
                'help' => __('Uploaded photo of racing pipe 3 (HTML img).'),
            ],
            [
                'token' => '{{pipe_type}}',
                'label' => __('Pipe type'),
                'help' => __('Competition pipe type used in the announcement.'),
            ],
            [
                'token' => '{{pipe_parameters}}',
                'label' => __('Pipe parameters'),
                'help' => __('Pipe parameters (announcement text).'),
            ],
            [
                'token' => '{{tobacco_type}}',
                'label' => __('Tobacco type'),
                'help' => __('Competition tobacco type.'),
            ],
            [
                'token' => '{{tobacco_weight}}',
                'label' => __('Tobacco weight'),
                'help' => __('Tobacco weight in grams (locale number + g).'),
            ],
        ];
    }

    protected static function formatTobaccoWeight(mixed $value, ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_numeric($value) || (float)$value <= 0) {
            return '';
        }
        $formatted = LocaleNumberParser::format($value, $locale, 2);

        return trim($formatted . ' ' . __('g'));
    }

    protected static function formatDate(mixed $value, ?string $locale = null): string
    {
        if ($value instanceof Date || $value instanceof DateTime || $value instanceof \DateTimeInterface) {
            return LocaleDateParser::format($value, 'date', $locale);
        }

        return $value !== null && $value !== '' ? (string)$value : '';
    }

    protected static function formatDateTime(mixed $value, ?string $locale = null): string
    {
        if ($value instanceof DateTime || $value instanceof \DateTimeInterface) {
            return LocaleDateParser::format($value, 'datetime', $locale);
        }

        return $value !== null && $value !== '' ? (string)$value : '';
    }

    protected static function formatTime(mixed $value, ?string $locale = null): string
    {
        if ($value instanceof DateTime || $value instanceof \DateTimeInterface) {
            return LocaleDateParser::format($value, 'time_short', $locale);
        }

        return $value !== null && $value !== '' ? (string)$value : '';
    }
}
