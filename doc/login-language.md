# Login nyelvválasztó + `languages` tábla

Login: **nyelv** Select2 (nem ország). Regisztráció: továbbra is **ország**.

Kapcsolódó: [users-auth.md](users-auth.md), [i18n.md](i18n.md).  
Schema: [`config/schema/languages.sql`](../config/schema/languages.sql).  
Seed: `php tmp/seed_languages.php` (`AdminLanguage::syncFromCountries`).

## Viselkedés

| Képernyő | Select | UI locale |
|----------|--------|-----------|
| Login | nyelv (`?locale=`) | kiválasztott nyelv; alap: session/cookie → **böngésző** Accept-Language |
| Register | ország (`?country_id=`) | ország primary locale (mint eddig) |
| **Bejelentkezés után (UI)** | — | **ugyanaz a login nyelv** (session/cookie); fallback: user `country_id` locale |

- A nyelvlista **feliratai** a jelenlegi UI nyelven jönnek (`Languages.name` Translate → `i18n`).
- Alatta: **Selected language: …** + locale kód.
- Login oldal szövegei: kiválasztott nyelv `.po` (egyelőre EN + HU).

## i18n seed

Minden `languages` sorhoz ICU `Locale::getDisplayName` minden elérhető cél-locale-ra (kötelező: `en_GB`, `hu_HU`).

## Kód

| API | Szerep |
|-----|--------|
| `AdminLanguage::options($uiLocale)` | select options |
| `AdminLanguage::displayName($code, $in)` | egy címke |
| `AdminLanguage::syncFromCountries()` | tábla + i18n feltöltés |
| `BrowserLocale::availableLocales()` | login-visible country locale-ok |
| `users_auth_locale.js` | Select2 + reload |
