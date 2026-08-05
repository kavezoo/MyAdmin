# Countries Admin — lista / szűrő / oszlopok

Referencia-adat: `countries` + `continents` + Translate.  
ACL: [users-auth.md](users-auth.md) / `CountryAccess` — **superuser** teljes CRUD; **admin** csak `visible` + `pos`.

Mezők: `name` (angol kanonikus + Translate), **`endonim_name`** (endoním / saját írásrendszer — nem fordított), `locale`, …

Kapcsolódó: [i18n.md](i18n.md) (országnevek DB — seedelt fordítások), **[country-visibilities.md](country-visibilities.md)** (plusz nyelvek / TAB), [admin-konvenciok.md](admin-konvenciok.md) (index / count / Swal), [admin-oldal.md](admin-oldal.md).  
Rule: `.cursor/rules/admin-countries-index.mdc`

`endonim_name` seed: `php tmp/seed_country_endonim_names.php` (ICU `Locale::getDisplayRegion`).

---

## 0. Ország→ország láthatóság

Form TAB / login: **`country_visibilities`** — [country-visibilities.md](country-visibilities.md).  
Countries form: **Additional languages** Select2 (saját nyelv mindig tárolva, listában nem).  
Countries formon **nincs** nyelvi TAB a `name`-hez — a többnyelvű országnevek a seedelt `i18n` fordítások; a formon egyetlen Name mező.  
Form placeholder / help példák (`iso2`, `name`, `endonim`, `locale`, `timezone`): belépett user regisztrált ország (`Users.country_id`) — `registeredCountryExamples` view var / `AdminCountry::registeredCountryExamples()`.
Index modal: **Additional languages** kapcsolt linklista (`recordGet` → `additional_languages`).

---

## 1. Jogok (rövid)

| Művelet | Superuser | Admin |
|---------|-----------|-------|
| Index / view | igen | igen (meta edit jog) |
| Add / delete | igen | nem |
| Edit összes mező | igen | nem |
| Edit `visible` + `pos` | igen | igen |

`CurrentUser::isSuperuser()` = `role === superuser` **vagy** CakeDC `is_superuser` (szigorú `1`/`true`).

---

## 2. Index — card header

Sorrend (jobb oldal, `d-flex`):

1. **Only visible countries** switch (`form-switch`)
2. **Elválasztó** — `<span class="index-header-sep">|</span>` (nagyobb bal/jobb margó)
3. `admin/table_search`
4. `admin/index_pagination`

### Visible-only szűrő

| | |
|--|--|
| UI | `__('Only visible countries')` → hu: **Csak a látható országok** |
| Query | `visible_only=1\|0` (GET; lap → 1; sort/q megmarad) |
| Session | `Admin.countriesVisibleOnly` |
| Default | **bekapcsolva** (`true`) — sok ország miatt |
| WHERE | bekapcsolva: `Countries.visible = true` |
| Controller | `CountriesController::resolveCountriesVisibleOnly()` — **előbb**, mint `applyIndexListState` (a `visible_only` nincs az index URL state kulcsai között) |

```php
$countriesVisibleOnly = $this->resolveCountriesVisibleOnly();
// … applyIndexListState …
if ($countriesVisibleOnly) {
    $query->where(['Countries.visible' => true]);
}
```

Ne keverd a Setups working-country Select2-vel — ez **csak** a Countries listára vonatkozik.

---

## 3. Index — oszlopsorrend

Kötelező sorrend (thead = tbody):

| # | Osztály | Mező | Szélesség |
|---|---------|------|-----------|
| (opt.) | `number id` | `id` | `4.75rem` |
| 1 | `string continent` | `Continents.name` | **fix** `10.5rem` |
| 2 | `string name` | `name` | **rugalmas** |
| 3 | `string endonim` | `endonim_name` | **fix** `12rem` |
| 4 | `string iso2` | `iso2` | **fix** `5rem` (középre) |
| 5 | `string locale` | `locale` | **fix** `8.5rem` |
| (opt.) | `boolean visible` | `visible` | `7.5rem` |
| 6 | `number pos` | `pos` | `5.5rem` |
| (opt.) | `number count` | `user_count` | **`min-width: 15rem`** (`width: 1%`) |
| (opt.) | `datetime` | created / modified | `10.5rem` |
| 7 | `actions` | — | — |

Alap rendezés (controller): `Continents.name ASC`, `Countries.name ASC`.

Címkék (msgid → hu példa):

| Msgid | hu |
|-------|-----|
| `Continent` | Földrész |
| `Name` | Név |
| `Endonym` | Endoním |
| `ISO` | ISO |
| `Locale` | **Nyelvi kód** |
| `Number of users` | **Felhasználók száma** |
| `Only visible countries` | **Csak a látható országok** |

---

## 4. CSS — count és Countries-specifikus oszlopok

### Általános `.count` (minden index)

Tábla celláknál a sima `width` / `max-width` **gyakran összezsugorodik**. Kötelező minta:

```css
.table th.count,
.table td.count {
  width: 1%;
  min-width: 15rem;
  max-width: none;
  white-space: nowrap;
}
.table th.count > a {
  width: max-content !important;
  max-width: none !important;
  min-width: max-content;
  flex-shrink: 0;
}
```

Ugyanez `!important`-tal: `webroot/css/pages/index.css` → `.index-data-table th.count`.  
Countries: hosszú címke miatt a `th`/`td`-n `style="min-width: 15rem"` is megengedett.

Fájlok: `webroot/css/style.css`, `webroot/css/pages/index.css`.

### Countries string oszlopok (kötött)

| Osztály | Szélesség | Megjegyzés |
|---------|-----------|------------|
| `.iso2` | `5rem` | 2 betű + „ISO” + sort |
| `.locale` | `8.5rem` | pl. `hu_HU` + „Nyelvi kód” + sort; **középre** |
| `.continent` | `10.5rem` | pl. Észak-Amerika / Földrész + sort |

`.index-header-sep`: `|` karakter, `margin: 0 0.85rem`, szürke.

---

## 5. Keresés / rendezés (UI locale)

Translate mezők (`name`, …): keresés és sort az **aktuális UI nyelven** — [i18n.md](i18n.md), `AdminTranslate`, `indexPaginateOptionsFor`.

---

## 6. CounterCache

`UsersTable` → `Countries.user_count`.  
Index: `LocaleNumberParser::formatCount($country->user_count)`.  
Törlésvédelem: `CountriesTable::canDelete()` (users + setups).  
Eltérés esetén: `bin/cake rebuild_counter_caches`.

---

## 7. Mentetlen form

Countries add/edit is `#form-horizontal` + `pages/form.js` → automatikus dirty leave Swal.  
Lásd: [admin-konvenciok.md](admin-konvenciok.md) „Mentetlen form”; rule: `admin-form-unsaved.mdc`.

---

## 8. Fájlok

| Fájl | Szerep |
|------|--------|
| `src/Controller/Admin/CountriesController.php` | CRUD, `resolveCountriesVisibleOnly`, index query |
| `src/Auth/CountryAccess.php` | jogok |
| `templates/Admin/Countries/index.php` | lista UI |
| `webroot/css/style.css` | `.count`, `.iso2`, `.locale`, `.continent`, `.index-header-sep` |
| `webroot/css/pages/index.css` | `.index-data-table th.count` erősítés |
| `resources/locales/hu_HU/default.po` | fordítások |
