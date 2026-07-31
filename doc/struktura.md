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
| `index_pagination.php` | igen (index) | Lapozó |
| `modal_record_view.php` | igen (index) | Rekord modal |
| `modal_linked_record_view.php` | ha van kapcsolt link | Linked modal |
| `view_related_tabs.php` | igen (view + gyerek) | Gyerek tab sheet-ek |
| `header_profile.php` stb. | opcionális | Header részek |
| `header_language.php` | fájl lehet | **Ne** include-old az admin headerben |

## Controllerek

### Keretrendszer

| Controller | Szerep |
|------------|--------|
| `Admin\AppController` | Layout + locale |
| `Admin\DashboardController` | `/admin` kezdőlap |

### Domain / demó (példa, nem kötelező az új projektben)

| Controller | Tanulság |
|------------|----------|
| `CitiesController` | Teljes CRUD + `recordGet` (Samples ASC + link lista) + `setFormOptions` (Samples list) + HABTM `samples._ids` mentés + `sample_count` |
| `SamplesController` | Teljes CRUD + `recordGet` (Cities ASC) + `select2Create` / `select2CreateCity` + `setFormOptions` (Parent: visible + pos/name; Cities list) + `parentGet` |
| `ParentsController` | CRUD + `recordGet` + gyerekvédelem |

Új modulnál a **viselkedést** másold (nem a mezőlistát) — [crud-utmutato.md](crud-utmutato.md).

## Modellek — tipikus buktatók

| Helyzet | Megoldás |
|---------|----------|
| Reserved entity (`Parent`) | Átnevezés pl. `ParentRecord` + `Table::setEntityClass` |
| HABTM through kötelező join mező | `beforeSave` / `beforeMarshal` default |
| Számláló mező (`*_count`) | `allowEmptyString` + create default 0; megjelenítés: `formatCount` (0/null → üres) |
| `pos` / `visible` / `logikai` | **DB DEFAULT** — PHP-ban **ne** hardkódolj; `UsesDatabaseColumnDefaultsTrait` + `newEntityWithSchemaDefaults()`. Üres form → unset. `beforeMarshal` `$data` = `ArrayObject` → `getArrayCopy()` |
| Pénznem UI | `LocaleNumberParser::currencySymbol()` — hu → **`Ft`** (ne `HUF`) |

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
  plugins/       # sweetalert2, select2, daterangepicker, inputmask, …
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
