# Projektstruktúra (Admin keretrendszer)

Új projektben ezeket a könyvtárakat / fájlokat kell létrehozni.  
Greenfield: [uj-projekt.md](uj-projekt.md). Kinézet/működés: [admin-oldal.md](admin-oldal.md).

## CakePHP Admin prefix

| Réteg | Útvonal |
|-------|---------|
| Controllerek | `src/Controller/Admin/` |
| Templatek | `templates/Admin/{Controller}/` |
| Layout | `templates/layout/admin.php` |
| Elementek | `templates/element/admin/` |
| URL | `/admin/{controller}/{action}` |

Admin `AppController`:

```php
I18n::setLocale('hu_HU');
$this->viewBuilder()->setLayout('admin');
```

## Element inventory (`templates/element/admin/`)

| Element | Kötelező? | Szerep |
|---------|-----------|--------|
| `header.php` | igen | Felső sáv (**nyelvválasztó nélkül**) |
| `sidebar.php` | igen | Menü |
| `breadcrumb.php` | igen | Cím + eszköztár gombok |
| `footer.php` | igen | Lábléc |
| `index_pagination.php` | igen (index / search) | FA «‹›» lapozó (disabled állapotokkal) |
| `index_counter.php` | igen (index / search footer) | Bake `Paginator::counter` összesítő |
| `index_footer.php` | igen (index / search) | Card lábléc: `index_counter` + `index_pagination` |
| `table_search.php` | igen (index) | Tábla szöveges kereső + nagyító |
| `header_search.php` | igen | Globális kereső (összes model) → `/admin/search` |
| `modal_record_view.php` | igen (index) | Rekord modal |
| `modal_linked_record_view.php` | ha van kapcsolt link | Linked modal |
| `view_related_tabs.php` | igen (view + gyerek) | Gyerek tab sheet-ek |
| `field_error.php` | igen (form, összetett mező) | Validációs hiba a Tempus/Select2+/checkbox wrapper **után** |
| `header_profile.php` stb. | opcionális | Header részek |
| `header_language.php` | fájl lehet | **Ne** include-old az admin headerben |

## Controllerek

### Keretrendszer

| Controller | Szerep |
|------------|--------|
| `Admin\AppController` | Layout + `App.adminLocale` + index state / search / lastVisited |
| `Admin\DashboardController` | `/admin` kezdőlap |
| `Admin\SearchController` | Globális keresés (`/admin/search`) |

### Domain / demó (példa, nem kötelező az új projektben)

| Controller | Tanulság |
|------------|----------|
| `SetupsController` | Típusos beállítások CRUD (`SetupValue`) — [setups.md](setups.md) |
| `CitiesController` | Teljes CRUD + `recordGet` + HABTM `samples._ids` (CounterCache tartja a `sample_count`-ot) |
| `CountriesController` | Lista / view / edit; **csak** `visible` + `pos`; contain Continents; i18n csak megjelenítés |
| `ContinentsTable` | Földrészek (seed); Translate → `i18n`; hasMany Countries |
| `SamplesController` | Teljes CRUD + `recordGet` (Cities ASC) + `select2Create` / `select2CreateCity` + `setFormOptions` (Parent: visible + pos/name; Cities list) + `parentGet` |
| `ParentsController` | CRUD + `recordGet` + gyerekvédelem |

Új modulnál a **viselkedést** másold (nem a mezőlistát) — [crud-utmutato.md](crud-utmutato.md).

## Modellek — tipikus buktatók

| Helyzet | Megoldás |
|---------|----------|
| Reserved entity (`Parent`) | Átnevezés pl. `ParentRecord` + `Table::setEntityClass` |
| HABTM through kötelező join mező | `beforeSave` / `beforeMarshal` default |
| `CitiesSamplesTable` | HABTM through + **CounterCache** (`Samples.city_count`, `Cities.sample_count`) |
| `SamplesTable` | CounterCache → `Parents.sample_count`; HABTM Cities + `cascadeCallbacks` |
| `ParentsTable` / `CitiesTable` | `PreventsDeleteWithChildrenTrait` + `relatedChildrenCountField()` |
| `CountriesTable` | ISO `iso2` + primary `locale` + `continent_id` → Continents; Translate → `i18n` (`name`); Admin: csak `visible`/`pos`; continent seed: `php tmp/seed_continents.php` |
| `I18nTable` | CakePHP Translate EAV (`config/schema/i18n.sql`) |
| Számláló mező (`*_count`) | CounterCache tartja; create-kor `0` ha NOT NULL + nincs DB DEFAULT; megjelenítés: `formatCount` (0/null → üres) |
| `pos` / `visible` / `logikai` | **DB DEFAULT** — PHP-ban **ne** hardkódolj; `UsesDatabaseColumnDefaultsTrait` + `newEntityWithSchemaDefaults()`. Üres form → unset. `beforeMarshal` `$data` = `ArrayObject` → `getArrayCopy()`. **`pos`:** az agent soha ne állítsa (rule: `pos-db-default.mdc`) |
| Pénznem UI | `LocaleNumberParser::formatCurrency()` — HUF, ICU (hu: `Ft` utótag; en: `HUF` előtag) |
| Admin keresés | `config/admin_search.php` + `App\Utility\AdminSearch` — index / globális szöveges mezők; Search Google UI + lapozás |
| Lapozó | `admin/index_pagination` FA «‹›»; `App\View\Helper\PaginatorHelper` (`page=1` az URL-ben) |
| Index állapot | URL könyvjelzőzhető (sort/page/q); üres index → session redirect |
| Setups (EAV) | `setups` + `SetupValue` + `Setup::get()`; `pages/setups_form.js|css`; [setups.md](setups.md) |

## Template típusok

| Fájl | Tartalom |
|------|----------|
| `index.php` | Lista (teljes minta) |
| `form.php` | Add + edit közös |
| `view.php` | Adatlap + related tabs |

Sablon → CakePHP leképezés (ha van demó HTML sablon):

| Sablon | CakePHP |
|--------|---------|
| layout / `index.php` váz | `templates/layout/admin.php` |
| list | `templates/Admin/.../index.php` |
| edit | `templates/Admin/.../form.php` |
| (nincs) | `templates/Admin/.../view.php` |
| elements | `templates/element/admin/*` |
| `assets/js/app.js` | `webroot/js/app.js` |
| page JS/CSS | `webroot/js|css/pages/*` |
| ajax | Controller JSON actionök |

## Webroot

```
webroot/
  css/           # bootstrap, style.css, pages/index.css, pages/form.css
  js/            # jquery, bootstrap, pikeadmin, app.js, pages/*.js
  plugins/       # sweetalert2, select2, tempus-dominus, inputmask, …
  fontawesome/
  img/
  favicon.ico
```

## Routing

```php
$routes->prefix('Admin', function (RouteBuilder $builder): void {
    $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
    $builder->fallbacks(DashedRoute::class);
});
```

DashedRoute: `recordGet` → `/admin/{controller}/record-get/{id}`.

Member (opcionális): `/{lang}/...` + `Member` prefix — lásd [uj-projekt.md](uj-projekt.md).

## Middleware

Lásd [middleware.md](middleware.md).

| Osztály | Szerep |
|---------|--------|
| `LocaleMiddleware` | Member: `{lang}`; Admin: fix `hu_HU` |
| `NormalizeLocalizedDateMiddleware` | Dátum/idő → SQL |
| `NormalizeLocalizedNumberMiddleware` | Locale szám → `1234.56` |
