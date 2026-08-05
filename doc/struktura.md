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

Admin `AppController` / `PanelAppController`:

```php
$locale = BrowserLocale::forLoggedIn($request, $identity);
I18n::setLocale($locale);
$this->viewBuilder()->setLayout('admin');
// panelHomeUrl, panelSidebar, panelBrand
```

## Szerepkör panelek

| Prefix | URL | Controllers / templates |
|--------|-----|-------------------------|
| Admin | `/admin` | `src/Controller/Admin/`, `templates/Admin/` |
| New | `/new` | `src/Controller/New/`, `templates/New/` |
| Member | `/member` | `src/Controller/Member/`, `templates/Member/` |
| Clubpresident | `/clubpresident` | `…/Clubpresident/` |
| President | `/president` | `…/President/` — Members + **Clubs** CRUD |

Sidebar elementek: `templates/element/{admin,new,member,clubpresident,president}/sidebar.php`.  
Dashboard navigáció: `templates/element/panel/dashboard_nav_cards.php` — card cím + leírás + gomb; a **Dashboard card-body**-ban (nem külön keret alatt).
Tag szerkesztés (Clubpresident/President): `templates/element/users/member_edit_form.php`.
Spec: [users-auth.md](users-auth.md).

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
| `header_search.php` | igen | Globális kereső → `/admin/search` — csak `superuser`/`admin`/`president`/`vicepresident` |
| `modal_record_view.php` | igen (index) | Rekord modal |
| `modal_linked_record_view.php` | ha van kapcsolt link | Linked modal |
| `view_related_tabs.php` | igen (view + gyerek) | Gyerek tab sheet-ek |
| `field_error.php` | igen (form, összetett mező) | Validációs hiba a Tempus/Select2+/checkbox wrapper **után** |
| `header_profile.php` stb. | igen (auth után) | Belépve + Profile / Change password / Logout |
| `header_language.php` | fájl lehet | **Ne** include-old az admin headerben |
| `script_flash.php` | igen | Simple Notify `flashMessage()` — Admin + login layout |

## Controllerek

### Keretrendszer

| Controller | Szerep |
|------------|--------|
| `Admin\AppController` | CRUD helpers + panel chrome + index state / search / lastVisited |
| `PanelAppController` | New/Member/Clubpresident/President közös layout |
| `IndexListCrudTrait` | Admin + President index állapot / keresés / last-visited / delete helpers |
| `Admin\DashboardController` | `/admin` kezdőlap |
| `Admin\SearchController` | Globális keresés (`/admin/search`) — role-gated |

### Domain / demó (példa, nem kötelező az új projektben)

| Controller | Tanulság |
|------------|----------|
| `SetupsController` | Típusos beállítások CRUD (`SetupValue`) — [setups.md](setups.md) |
| `CitiesController` | Teljes CRUD + `recordGet` + HABTM `samples._ids` (CounterCache tartja a `sample_count`-ot) |
| `CountriesController` | Lista / view / edit; **csak** `visible` + `pos`; contain Continents; i18n csak megjelenítés |
| `LanguagesController` | UI locale CRUD; **LanguageAccess**; visible-only index; Translate `name` |
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
| `pos` / `visible` / `logikai` | **DB DEFAULT** — PHP-ban **ne** hardkódolj; `UsesDatabaseColumnDefaultsTrait` + `newEntityWithSchemaDefaults()`. Üres form → unset. `beforeMarshal` `$data` = `ArrayObject` → `getArrayCopy()`. **`pos`:** mindig séma DEFAULT (**`1000`**); az agent soha ne állítsa / ne növelgesse (rule: `pos-db-default.mdc`) |
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
$panelPrefixes = ['Admin', 'New', 'Member', 'Clubpresident', 'President'];
foreach ($panelPrefixes as $prefix) {
    $routes->prefix($prefix, function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });
}
```

DashedRoute: `recordGet` → `/admin/{controller}/record-get/{id}`.

**Nincs** `/{lang}/…` URL szegmens — locale session / user ország.

| Prefix | URL | Role(ok) |
|--------|-----|----------|
| Admin | `/admin` | `superuser`, `admin` |
| New | `/new` | `new` (regisztráció) |
| Member | `/member` | `member`, `editor` |
| Clubpresident | `/clubpresident` | `clubpresident` |
| President | `/president` | `president`, `vicepresident` |

Közös chrome: `templates/layout/admin.php` + `PanelAppController` / `Admin\AppController`. Sidebar: `templates/element/{prefix}/sidebar.php`.

## Middleware

Lásd [middleware.md](middleware.md).

| Osztály | Szerep |
|---------|--------|
| `LocaleMiddleware` | session / cookie / Accept-Language → `Countries.locale` (nincs URL lang) |
| `SanitizeAuthRedirectMiddleware` | CakeDC login redirect loop ellen |
| `NormalizeLocalizedDateMiddleware` | Dátum/idő → SQL |
| `NormalizeLocalizedNumberMiddleware` | Locale szám → `1234.56` |

## CakeDC Users (auth)

| Réteg | Útvonal |
|-------|---------|
| Config | `config/users.php`, `config/permissions.php` |
| Controller / Table | `src/Controller/UsersController.php`, `src/Model/Table/UsersTable.php` |
| Layout | `templates/layout/login.php` |
| Templatek | `templates/Users/` (login, register, profile, change_password, …) |
| CSS/JS | `webroot/css/pages/users_auth.css`, `webroot/js/pages/users_auth_country.js` |
| RoleHome | `src/Auth/RoleHome.php`, `AppRoles.php` |

Spec: [users-auth.md](users-auth.md).
