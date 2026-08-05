# Setups — típusos alkalmazásbeállítások

Hordozható specek: új CakePHP Admin projektbe **másold** a fájlokat + ezt a speceket.  
Agent rule: `.cursor/rules/setups-eav.mdc`. Keret: [uj-projekt.md](uj-projekt.md), CRUD: [crud-utmutato.md](crud-utmutato.md).  
Programozói használat: [MyAdminUsage.md](MyAdminUsage.md).

## Cél

Kulcs–érték **alkalmazásbeállítások** Admin CRUD-dal, **országonként**:

- Minden rekordnak van **típusa** (`type`) és **országa** (`country_id`).
- Unique: `(country_id, slug)` — ugyanaz a slug több országban létezhet.
- **Új felvitel:** minden **látható** országhoz létrejön egy rekord (ugyanaz a slug / típus / kezdő érték / `edit_by`).
- **Index:** csak az aktuális Admin „working country” sorai (Select2 a fejlécben).
- Alapértelmezett ország: **Magyarország (`HU`)**, ha nincs session/cookie.
- Olvasás: `Setup::get('site_title', $default)` → aktuális ország + típusos cast.

## Séma

Fájl: [`config/schema/setups.sql`](../config/schema/setups.sql)

| Mező | Típus | Megjegyzés |
|------|-------|------------|
| `id` | PK | |
| `country_id` | FK → `countries.id` | Working country |
| `name` | varchar(255) | Megjelenő név — **Translate** (`en_GB` kanonikus + `i18n` EAV); UI = oldal locale |
| `slug` | varchar(255) | Kulcs: `a-z0-9_`; unique **országonként** |
| `type` | varchar(20) | lásd típusok (+ `secret`) |
| `edit_by` | varchar(20) DEFAULT `admin` | `superuser` \| `admin` \| `president` |
| `value` | text NOT NULL | Kanonikus / titkosított string |
| `visible` | tinyint DEFAULT 1 | DB DEFAULT — **nincs** Admin UI |
| `pos` | int DEFAULT 1000 | DB DEFAULT — **nincs** Admin UI; agent ne állítsa |
| `created`, `modified` | datetime | Timestamp |

**Tilos:** külön `value_int` / `value_bool` oszlopok.

## Working country (`App\Utility\AdminCountry`)

| Forrás (sorrend) | Megjegyzés |
|------------------|------------|
| Session `Admin.workingCountryId` | |
| Cookie `AdminWorkingCountryId` (≥ 1 év / `+400 days`) | regisztráció + Setups country switch; login után a user `country_id` is forrás lehet |
| Default | `Countries.iso2 = HU` (látható) |

**Országnév a listákban:** mindig az **oldal nyelve** szerint (Translate), nem külön nyelvváltó.

| Szabály | Részlet |
|---------|---------|
| API | `AdminCountry::options()` / `Countries->find('visibleTranslated')` |
| Szűrő | csak `Countries.visible = true` |
| Név | UI locale (`I18n` / `App.adminLocale`); alias pl. `en_UK`→`en_GB` |
| Sorrend | fordított név ABC |
| Select2 | keresés a lefordított neveken (`setups_index.js`) |

Index: Select2 → `?country_id=` → session+cookie. Címsor: „Listing settings for {ország}”.

## Szerepkörök (`App\Auth\AppRoles`)

Kulcsok (Users.role): `superuser`, `admin`, `president`, `vicepresident`, `clubpresident`, `editor`, `member`, `new`.

Megjelenítés: `AppRoles::labeled($role)` → `admin — Admin` (msgid angol, fordítás `.po`).

| API | Megjegyzés |
|-----|------------|
| `AppRoles::label()` | csak a lefordított név |
| `AppRoles::labeled()` / `options()` | `kulcs — Név` |
| `CurrentUser::role()` | identity → `Configure` `App.devRole` → debug=`superuser`, különben `new` |
| `SetupAccess` | Setups menü / URL / create / country / edit value |

**Setups hozzáférés:** modul (menü / URL / CRUD) **csak superuser** (`SetupAccess::canAccessModule` / `CurrentUser::isSuperuser`). Ország Select2, új rekord, törlés, meta mezők: szintén csak `superuser`. Érték szerkesztés `edit_by` szerint (pl. Event logs kapcsolók) — nem a teljes Setups modul.

## `edit_by` (`App\Utility\SetupEditBy`)

| Érték | Ki módosíthatja az értéket |
|-------|----------------------------|
| `superuser` | csak Superuser (rendszerkritikus) |
| `admin` | Admin, Superuser |
| `president` | President, Vice president, Admin, Superuser |

Régi DB érték `officers` → `president` (`SetupEditBy::normalizeStored`).  
Select opciók: `SetupEditBy::options()` → `AppRoles::labeled()`.

## Slug

| Szabály | Részlet |
|---------|---------|
| Elválasztó | **`_`** — tilos `-` |
| Formátum | `/^[a-z0-9]+(?:_[a-z0-9]+)*$/` |
| Unique | `(country_id, slug)` |
| Új rekord | `SetupsTable::createForAllCountries()` |

## Típusok (`App\Utility\SetupValue`)

| `type` | Widget | Tárolás | Cast |
|--------|--------|---------|------|
| `string` / `text` | text / textarea | szöveg | `string` |
| `integer` / `float` | inputmask | szám string | `int` / `float` |
| `boolean` | switch | `0`/`1` | `bool` |
| `date` / `time` / `datetime` | Tempus | SQL forma | `string` |
| `json` / `array` | textarea | JSON | `array` / `list` |
| **`secret`** | maszkolt input | **Security::encrypt** (base64) | **dekriptált string** |

### Secret szabályok

- Form: maszkolt mező; lista/view: `••••••••`
- Szerkesztéskor üres mező = **marad** a régi titkosított érték
- `Setup::get('api_token')` → plaintext (csak szerveren használd)

## Futásidő

```php
use App\Utility\Setup;

$title = Setup::get('site_title', 'My Admin'); // aktuális ország
$pwd = Setup::get('smtp_password', '');        // secret → dekriptálva
```

### Tevékenységnapló (példa slug-ok)

| Slug | Típus | Jelentés |
|------|-------|----------|
| `activity_logging_enabled` | boolean | Naplózás be/ki (új `event_logs` sorok) |
| `users_activity_log_visible` | boolean | User látja a saját tevékenységét |

`name` mező: angol msgid + `SetupNameI18n` → `i18n` fordítások; megjelenítés UI locale szerint.

Részlet: [event-logs.md](event-logs.md). Seed: `php tmp/seed_activity_log_setups.php`. Fordítások: `php tmp/seed_setup_name_i18n.php`.

## Fájlok

| Réteg | Útvonal |
|-------|---------|
| Schema | `config/schema/setups.sql` |
| Utility | `SetupValue`, `Setup`, `SetupEditBy`, `SetupNameI18n`, `AdminCountry` |
| Auth | `AppRoles`, `CurrentUser`, `SetupAccess` |
| Model | `SetupsTable` / `Setup` |
| Controller | `SetupsController` |
| Templates | `Admin/Setups/*`, `element/admin/working_country_select` |
| JS/CSS | `pages/setups_form`, `pages/setups_index` |
| Rule | `.cursor/rules/setups-eav.mdc` |

## Kapcsolódó

- [MyAdminUsage.md](MyAdminUsage.md) — `Setup::get()` példák
- [admin-konvenciok.md](admin-konvenciok.md)
- [i18n.md](i18n.md)
