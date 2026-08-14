# Middleware — szám- és dátumnormalizálás

Keretrendszer-rész (minden projektben): a formról érkező **locale szerinti** számok és dátumok egységes DB-formátumra hozása.  
Greenfield: [uj-projekt.md](uj-projekt.md) → middleware lépés. UI: [minta-tanulsagok.md](minta-tanulsagok.md) §5–6.

## Cél

| Middleware | Bemenet (példa) | Kimenet a controller / ORM felé |
|------------|-----------------|----------------------------------|
| `NormalizeLocalizedDateMiddleware` | `2024.03.12.`, `2024. 03. 12.`, `12.03.2024`, `2024. 03. 12. 09:15:00`, `08:00` | `2024-03-12`, `2024-03-12 09:15:00`, `08:00:00` |
| `NormalizeLocalizedNumberMiddleware` | `1 234,56` (hu), `1,234.56` (en) | `1234.56` |

Mentéshez **mindig** SQL kanonikus formátum kell (`Y-m-d` / `Y-m-d H:i:s` / `H:i:s`).  
A megjelenítés locale szerinti lehet (`LocaleDateParser::format()` + Tempus `dateFormat`).

## Fájlok

| Fájl | Szerep |
|------|--------|
| `src/Middleware/NormalizeLocalizedDateMiddleware.php` | Request body → dátum normalizálás |
| `src/Middleware/NormalizeLocalizedNumberMiddleware.php` | Request body → szám normalizálás |
| `src/Utility/LocaleDateParser.php` | Parse + `format()` + `jsConfig()` |
| `src/Utility/LocaleNumberParser.php` | Parse + `format()` / `jsConfig()` |
| `src/Middleware/LocaleMiddleware.php` | Locale **előbb** (Admin → `hu_HU`; auth → `BrowserLocale`) |
| `src/Middleware/SanitizeAuthRedirectMiddleware.php` | CakeDC `/login?redirect=/login…` loop — [users-auth.md](users-auth.md) |
| `src/Application.php` | Queue: SanitizeAuth → … → Locale → BodyParser → **Date** → Number → CSRF |

## Mikor fut?

- Csak `POST` / `PUT` / `PATCH` / `DELETE`
- Rekurzívan a `getData()` tömbön
- Kihagyott kulcsok: `_csrfToken`, `_Token`, `_method`, jelszó mezők, `_` prefix, **`phone`**, **`phone_prefix`** (E.164: `+36` ne legyen „szám”)

## Dátum (`LocaleDateParser`)

| Locale | Elsődleges sorrend | Megjelenítés (példa) |
|--------|-------------------|----------------------|
| `hu_HU` | YMD (+ DMY / MDY fallback) | `2024.03.15.` / `2024.03.15. 14:30:00` |
| `de_DE`, `sk_SK` | DMY | `15.03.2024` |
| `fr_FR`, `en_GB` | DMY | `15/03/2024` |
| `en_US` | MDY | `03/15/2024` |

Elfogadott bemenetek (nem teljes lista):

- `2024-03-15`, `2024-03-15T14:30:00`
- `2024.03.15.` / `2024.03.15 14:30:00` (JeffAdmin5 / moment)
- `2024. 03. 15.` / `2024. 03. 15. 14:30:00` (**Tempus 6.0 Intl hu** — szóközös!)
- `15.03.2024`, `15. 03. 2024`, `03/15/2024`
- `14:30` / `14:30:00`

Kimenet mentéshez: `Y-m-d` | `Y-m-d H:i:s` | `H:i:s`.

```php
// megjelenítés (form value)
LocaleDateParser::format($entity->datum, 'date');
LocaleDateParser::format($entity->datumido, 'datetime');
LocaleDateParser::format($entity->ido, 'time');

// index / view / modal (mp nélkül)
LocaleDateParser::format($entity->created, 'datetime_short');
LocaleDateParser::format($entity->ido, 'time_short');

// form JS
$config['dateFormat'] = LocaleDateParser::jsConfig();
// → {
//   locale: 'hu_HU', intl: 'hu-HU', moment: 'hu', startOfTheWeek: 1,
//   date: 'YYYY.MM.DD.', datetime: 'YYYY.MM.DD. HH:mm:ss', time: 'HH:mm:ss'
// }
// en_US: intl en-US, startOfTheWeek 0, date MM/DD/YYYY
```

**Ne** hardkódolj `->format('Y.m.d.')` / `H:i` templateben vagy `recordGet`-ben.

**Tempus Dominus 6.0.0:** a beépített `formatInput` Intl-t használ (hu → szóközös dátum). A `pages/form.js` **felülírja** moment formátummal (`MyAdmin.config.dateFormat`). A naptár hónap/napnevei: `localization.locale` = `dateFormat.intl`. A hét első napja: `Intl.Locale.weekInfo` (ha van), különben `dateFormat.startOfTheWeek` (`en_US`→vasárnap, egyéb→hétfő). Idő: `useTwentyFourHour` — `en_US` 12h **AM/PM**, hu/EU **24h** (ne DE/DU az angol UI-n). A middleware a locale szerinti és a szóközös Intl / AM-PM formátumot is menti.

A szám middleware **nem** nyúl dátumszerű stringekhez (szóközös hu dátum sem).

## Szám (`LocaleNumberParser`)

| Locale | Tizedes | Ezres |
|--------|---------|-------|
| `hu_HU`, `sk_SK`, `fr_FR` | `,` | szóköz (alt: NBSP, `.`) |
| `de_DE` | `,` | `.` |
| `en_US`, `en_GB` | `.` | `,` |

**Feladat:** POST body minden „számnak kinéző” stringjét kanonikusra (`1234` / `1234.56`), locale szerint (ezres + tizedes). Egész mezőknél nincs tizedes; decimal/numeric mezőknél a tört rész megmarad.

Példák (`hu_HU`):

| Bemenet | Kimenet |
|---------|---------|
| `1 234` / `1 234 567` | `1234` / `1234567` |
| `1 234,56` / `1.234,56` | `1234.56` |
| `12,5` | `12.5` |

Dátum/idő stringeket **nem** nyúl (Date middleware után ISO; a parser a `.`/`/`/`-` dátumokat kihagyja — a tiszta szóközös ezres csoport **nem** dátum).  
Telefon / hívószám (`phone`, `phone_prefix`): **kihagyva** — pl. `+36` ne váljon `36`-tá (különben a Countries validáció elbukik).

Kimenet: `1234.56`. Form: `numberFormat` + `.js-input-decimal` / `.js-input-integer` — **ne** `inputmode=decimal|numeric`; inputmask `autoUnmask` + middleware fallback.

## Új locale

1. Bővítsd a `$formats` tömböt mindkét parserben (`dateOrder` + `display*`).
2. `php tmp/test_dates.php` (és szám teszt, ha van).
3. Frissítsd ezt a fájlt.

## Teszt

```bash
php tmp/test_dates.php
```
