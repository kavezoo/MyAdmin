# Admin ország-szűrés (saját ország / superuser select)

**Örök:** Admin listákon a `country_id`-s táblák **mindig** országra szűrtek. Nincs „All countries”.

| Szerep | Viselkedés |
|--------|------------|
| **admin** (nem superuser) | Csak a saját `Users.country_id` rekordjai; **nincs** országválasztó |
| **superuser** | Ország Select2 a lista fejlécében; opciók = országok, ahol **van legalább egy sor** az adott táblán |

## Utility / trait

| Elem | Szerep |
|------|--------|
| `App\Utility\AdminCountryScope` | resolve, `optionsWithRecords`, entity ACL, Countries saját-sor feltétel |
| `AdminCountryScopeTrait` | `beginAdminCountryScopedIndex()`, `applyAdminCountryWhere()`, `denyIfOutsideAdminCountryScope()`, `constrainAdminCountryData()` |
| `Admin\AppController` | trait behúzva |

Working country session/cookie továbbra is `AdminCountry` (superuser váltáskor frissül).

## UI

- Element: `admin/index_country_scope` → `admin/working_country_select` (csak ha `canChangeCountry`)
- JS: `pages/setups_index.js` (query paramok megmaradnak, `page=1`)
- Form add/edit: admin országmező lockolt a saját országra; superuser → teljes `AdminCountry::options()` (új rekord üres országban is létrehozható)

## Táblák

| Tábla | Scope |
|-------|--------|
| Users, Clubs, Competitions, Cities, Counties, Setups, EventLogs, **EmailTemplates** | `country_id` |
| Countries | admin: csak saját `Countries.id`; superuser: teljes lista (+ visible_only) |
| Languages | nincs ország — változatlan |

**EmailTemplates** egyediség: `(country_id, language_id, slug)`. Küldéskor a címzett országa + nyelve. President CRUD: mindig az officer `country_id`.

Globális keresés (`AdminSearch::searchAll`): ugyanaz a country scope.

## Spec / rule

`doc/admin-konvenciok.md`, `doc/countries-admin.md`, `doc/valtozasok.md`.  
Agent: új Admin CRUD `country_id`-vel → `beginAdminCountryScopedIndex` + index element.
