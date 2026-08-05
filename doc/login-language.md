# Login nyelvválasztó + `languages` tábla

Login: **nyelv** Select2 (nem ország). Regisztráció: továbbra is **ország**.

Kapcsolódó: [users-auth.md](users-auth.md), [i18n.md](i18n.md).  
Schema: [`config/schema/languages.sql`](../config/schema/languages.sql).  
Seed: `php tmp/seed_languages.php` (`AdminLanguage::syncFromCountries`).

## Viselkedés

| Képernyő | Select | UI locale |
|----------|--------|-----------|
| Login | nyelv (`?locale=`) | `languages.visible=true` (európai locale-ok + **en_US** + **en_CA**); címke: UI nyelv + (endoním) |
| Register | ország (`?country_id=`) | lista: **csak `visible` + `endonim_name`**; UI locale = ország primary locale |
| **Bejelentkezés után (UI)** | — | **ugyanaz a login nyelv** (session/cookie); fallback: user `country_id` locale |

**Megjegyzés (≥ 1 év):** az utoljára használt UI nyelv `AppUiLocale` cookie-ban él (`BrowserLocale::COOKIE_LIFETIME = '+1 year'`). Minden válasz megújítja (sliding). Login POST rejtett `locale` mezővel is elmenti.

- A nyelvlista forrása: `languages` (`visible=1` = Európa + en_US + en_CA); felirat: aktuális UI nyelven + zárójelben `endonim_name`.
- Seed: `php tmp/seed_languages.php` (`AdminLanguage::syncFromCountries` — minden country locale + endonim).
- Alatta: **Selected language: …** + locale kód.
- Login oldal szövegei: kiválasztott nyelv `.po` (egyelőre EN + HU).

## i18n seed

Minden `languages` sorhoz ICU `Locale::getDisplayName` minden elérhető cél-locale-ra (kötelező: `en_GB`, `hu_HU`).

## Kód

| API | Szerep |
|-----|--------|
| `AdminLanguage::loginOptions($uiLocale)` | login Select2: `{UI nyelv} ({endoním})`; `languages.visible=1` |
| `AdminLanguage::fallbackLoginOptions()` | ha a tábla üres / hiba: `BrowserLocale::availableLocales()` + ICU |
| `AdminCountry::registerOptions()` | register Select2: endonim_name, visible only |
| `AdminLanguage::options($uiLocale)` | fordított nevek (egyéb UI) |
| `AdminLanguage::displayName($code, $in)` | egy címke |
| `AdminLanguage::syncFromCountries()` | tábla + i18n feltöltés |
| `BrowserLocale::availableLocales()` | login-visible locale-ok (`languages` vagy fallback: visible countries) |

## Üres login nyelvlista

Ha a Select2 üres: `languages` tábla üres vagy hiányzik az `endonim_name` oszlop → `AdminLanguage::loginOptions` üres listát ad (fallback: ICU). **Javítás:** `php bin/cake migrations migrate` + `php tmp/seed_languages.php`. Migráció visszavonás (`migrate -t`) törli a táblát — utána seed szükséges.
| `users_auth_locale.js` | Select2 + reload |
