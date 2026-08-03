# Setups — típusos alkalmazásbeállítások

Hordozható specek: új CakePHP Admin projektbe **másold** a fájlokat + ezt a speceket.  
Agent rule: `.cursor/rules/setups-eav.mdc`. Keret: [uj-projekt.md](uj-projekt.md), CRUD: [crud-utmutato.md](crud-utmutato.md).

## Cél

Kulcs–érték **alkalmazásbeállítások** Admin CRUD-dal:

- Minden rekordnak van **típusa** (`type`).
- Az **érték** widgetje, validációja és tárolási formája a típustól függ.
- A kód a **`slug`** alapján olvassa — bárhonnan: `Setup::get('site_title', $default)`.
- Lista / form / view / delete / keresés / lapozás = ugyanaz a keretrendszer-minta, mint a többi Admin CRUD.

## Séma

Fájl: [`config/schema/setups.sql`](../config/schema/setups.sql)  
Létrehozás: futtasd a SQL-t a projekt DB-jén (vagy Cake Connectionen keresztül).

| Mező | Típus | Megjegyzés |
|------|-------|------------|
| `id` | PK | |
| `name` | varchar(150) | Megjelenő név |
| `slug` | varchar(150) UNIQUE | Kulcs a kódhoz: csak `a-z`, `0-9`, `_` |
| `type` | varchar(20) | lásd típusok |
| `value` | mediumtext | Kanonikus string / JSON (EAV — **egy** oszlop) |
| `description` | text NULL | Opcionális magyarázat |
| `visible` | tinyint DEFAULT 1 | DB DEFAULT |
| `pos` | int DEFAULT 1000 | DB DEFAULT — agent ne állítsa |
| `created`, `modified` | datetime | Timestamp behavior |

**Tilos:** külön `value_int` / `value_bool` oszlopok — maradjon EAV `value`.

## Slug (kötelező szabály)

| Szabály | Részlet |
|---------|---------|
| Elválasztó | **`_`** (aláhúzás) — **nem** `-` (kötőjel) |
| Formátum | `/^[a-z0-9]+(?:_[a-z0-9]+)*$/` |
| Példa OK | `site_title`, `max_upload_mb`, `feature_x` |
| Példa tilos | `site-title`, `Site_Title`, `site title`, `_leading` |
| Javaslat | Név → `SetupValue::suggestSlug()` + JS `pages/setups_form.js` |
| Kézi edit | Engedélyezett; manuális módosítás után a név **ne** írja felül |
| Unique | `RulesChecker::isUnique(['slug'])` |
| Hiba msgid | `__('The slug may only contain lowercase letters, numbers and underscores.')` |

## Típusok (`App\Utility\SetupValue`)

| `type` konstans | Widget | Tárolás `value`-ban | Cast (`getValue`) |
|-----------------|--------|---------------------|-------------------|
| `string` | text input | nyers szöveg | `string` |
| `text` | textarea | hosszú szöveg | `string` |
| `integer` | `.js-input-integer` | `"123"` | `int` |
| `float` | `.js-input-decimal` | `"12.34"` | `float` |
| `boolean` | `form-switch` | `"0"` / `"1"` | `bool` |
| `date` | Tempus Dominus date | `Y-m-d` | `string` (SQL) |
| `time` | Tempus time | `H:i:s` | `string` |
| `datetime` | Tempus datetime | `Y-m-d H:i:s` | `string` |
| `json` | monospace textarea | JSON object/array | `array` |
| `array` | egy elem / sor (vagy JSON tömb) | JSON array | `list` |

API:

| Metódus | Szerep |
|---------|--------|
| `typeList()` / `typeOptions()` | Engedélyezett típusok + `__()` címkék |
| `suggestSlug($name)` / `isValidSlug($slug)` | Slug |
| `normalize($type, $raw)` | Form → kanonikus DB string (`ok` / `value` / `error`) |
| `formatForDisplay($type, $value)` | Index / view / modal preview |
| `formatForForm($type, $value)` | Edit form visszatöltés (locale szám/dátum, array sorok) |
| `cast($type, $value)` | PHP érték a kódnak |

Futásidő — **bárhol** a programban: lásd **[MyAdminUsage.md](MyAdminUsage.md)** (`Setup::get`).

```php
use App\Utility\Setup;

$title = Setup::get('site_title', 'My Admin');
```

## Fájlok (greenfield checklist)

| Réteg | Útvonal |
|-------|---------|
| Schema | `config/schema/setups.sql` |
| Utility | `src/Utility/SetupValue.php` (típusok), `src/Utility/Setup.php` (`Setup::get`) |
| Model | `src/Model/Table/SetupsTable.php`, `src/Model/Entity/Setup.php` |
| Controller | `src/Controller/Admin/SetupsController.php` |
| Templates | `templates/Admin/Setups/{index,form,view}.php` |
| JS | `webroot/js/pages/setups_form.js` |
| CSS | `webroot/css/pages/setups_form.css` |
| Keresés | `config/admin_search.php` → `Setups` (`name`, `slug`, `description`, `labelsKey` = `setup`) |
| Search modal labels | `templates/Admin/Search/index.php` → `entityFieldLabels.setup` |
| Menü | sidebar: **Settings** csoport → **Setups** |
| i18n | `hu_HU/default.po` (Setups, Slug, Type, Value, típusnevek, hibák) |
| Rule | `.cursor/rules/setups-eav.mdc` |

## Controller minta

`SetupsController` követi a keretet:

- `applyIndexListState('Setups')` → Response redirect kezelés
- `applyIndexSearch` + `resolveIndexPageForLastVisited` → Response
- `paginate` + `setLastVisitedForIndex`
- add/edit: `newEntityWithSchemaDefaults` / `patchEntity` / try-catch Flash / `redirectToIndexList('Setups')`
- `recordGet` JSON: type címke + `SetupValue::formatForDisplay` a `value`-ra
- `setFormOptions`: `setupTypeOptions`, `setupValueForm`, `dateFormat`, `numberFormat`

## Form UX

1. `name` (autofocus) → slug javaslat (`_`)
2. `slug` (szerkeszthető)
3. `type` select → `#setup-value-widgets` panel váltás (`data-setup-type`)
4. Aktív panel mezője kapja a `name="value"`-t (JS submit előtt)
5. Boolean kikapcsolva → hidden `value=0`
6. `description`
7. `visible` → `pos` + `<hr>` (Samples minta)
8. Asset: `pages/form` + Tempus + inputmask + `pages/setups_form`

`SetupsTable::beforeMarshal`: slug lowercase; boolean hiányzó value → `0`; sikeres `normalize` → kanonikus `value`.  
Validáció: typed value rule (dinamikus hibaüzenet) + slug format + unique.

## Index / view

- Oszlopok: name, slug (`<code>`), type (badge), value preview, pos, visible, timestamps, actions
- Boolean preview: FA pipa / X
- JSON/array view: `<pre>` pretty
- Modal: `recordFieldLabels` (id, name, slug, type, value, description, …)

## Új típus hozzáadása

1. `SetupValue::TYPE_*` + `typeList()` + `typeOptions()`
2. `normalize` / `formatForDisplay` / `formatForForm` / `cast` ágak
3. Form panel: `.setup-value-panel` + `data-setup-type="…"` + `.js-setup-value`
4. `.po` msgid a típuscímkéhez (+ hu fordítás)
5. `doc/setups.md` tábla frissítése

## Gyakori hibák

| Hiba | Helyes |
|------|--------|
| Slug kötőjellel (`site-title`) | `site_title` |
| Type váltás után rossz mező megy el | `setups_form.js` sync `name="value"` |
| JSON skalár (`"foo"`) | Csak object vagy array |
| `getValue` invisible rekordra | Visszaadja a `$default`-ot |
| `pos` PHP-ból növelgetve | DB DEFAULT + trait |

## Kapcsolódó specek

- [MyAdminUsage.md](MyAdminUsage.md) — `Setup::get()` a kódban
- [admin-konvenciok.md](admin-konvenciok.md) — Setups szekció
- [struktura.md](struktura.md) — element / controller inventory
- [i18n.md](i18n.md) — `__()` + .po
- [middleware.md](middleware.md) — szám / dátum normalizálás a form mentéshez
