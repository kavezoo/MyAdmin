# Új CakePHP projekt — Admin keretrendszer felállítása a nulláról

Ez a fájl **önálló**: ha csak a `doc/` mappát másolod egy üres / friss CakePHP 5 projektbe, az agent **ebből** építse fel a teljes Admin UI keretrendszert.  
Nem kell a korábbi MyAdmin kód, és nem kötelező a teszt Samples/Parents/Cities modul — azok csak opcionális ellenőrző minták.

Kapcsolódó specifikációk (ugyanebben a `doc/`-ban):

| Fájl | Mit ír le |
|------|-----------|
| **[admin-oldal.md](admin-oldal.md)** | **Teljes kép**: hogyan nézzen ki és működjön az Admin (index/form/view) |
| [admin-konvenciok.md](admin-konvenciok.md) | Layout, asset, index/form/view, JS API (részlet) |
| [i18n.md](i18n.md) | `__()` + `hu_HU` .po |
| [middleware.md](middleware.md) | Locale szám/dátum normalizálás |
| [crud-utmutato.md](crud-utmutato.md) | Egy új CRUD modul lépései |
| [struktura.md](struktura.md) | Könyvtárak, routing, element lista |
| [keretrendszer.md](keretrendszer.md) | Mi tartós / mi eldobható |
| [minta-tanulsagok.md](minta-tanulsagok.md) | Demó → éles: CounterCache, modal, törlésvédelem |
| [users-auth.md](users-auth.md) | CakeDC login / register / profile |
| [setups.md](setups.md) | Setups EAV (opcionális) |

---

## 0. Előfeltételek

- **CakePHP 5.4+** app (`composer create-project cakephp/app`)
- PHP 8.2+
- Működő DB kapcsolat (`config/app_local.php`)
- UI kinézet forrás: egy Pike Admin / Bootstrap 5 admin sablon (pl. korábbi `MyPluginTemplate` `assets/` mappája), **vagy** meglévő webroot assetek bemásolása egy referenciaprojektből

Az agent **ne** találjon ki új design rendszert — a dokumentált struktúrát és viselkedést kövesse.

---

## 1. Célállapot (mit kell elérni)

Egy `/admin/...` prefixű admin felület — a **célkép** részletesen: [admin-oldal.md](admin-oldal.md). Röviden:

1. Közös layout (header, sidebar, breadcrumb, footer, Flash)
2. Minden CRUD modulnak van: **index** (lista) + **form** (add=edit) + **view** (adatlap + gyerek tabok) + **delete**
3. Index: típusoszlopok + fix szélességek (szám/pénz/logikai/id/…; `string` rugalmas), URL sort, opcionális oszlopok, dupla klikk, SweetAlert delete
4. View: bake-szerű `dl` + kapcsolt gyerekek Bootstrap **tab sheet**-ekben (üres tab is látszik)
5. i18n: `__('English')` msgid; Admin mindig `hu_HU`
6. Middleware: locale dátum + szám → SQL formátum mentés előtt
7. Layoutba **csak** közös CSS/JS; oldalspecifikus pluginok a templateben

---

## 2. Telepítési sorrend (kötelező)

Hajtsd végre **ebben a sorrendben**. Minden lépés után ellenőrizd, hogy a következő lépés fájljai még nincsenek-e — ne duplikáld.

### 2.1 Assetek a `webroot/`-ba

Másold / állítsd össze:

```
webroot/
  css/bootstrap.min.css
  css/style.css                 # Pike Admin + index-data-table, record-view, form label szabályok
  css/pages/index.css           # lista + view related-tabs finomítások
  css/pages/form.css
  js/jquery*.js, bootstrap.bundle*, moment*, modernizr*, …
  js/pikeadmin.js (vagy sablon app JS)
  js/app.js                     # window.MyAdmin API (lásd 2.6)
  js/pages/index.js             # lista: modal, delete confirm, tooltip
  js/pages/form.js              # select2, Tempus Dominus, inputmask, select2-add modal
  plugins/sweetalert2/
  plugins/simple-notify/
  plugins/select2/
  plugins/tempus-dominus/ (+ popper.js a form oldalakon)
  plugins/inputmask/
  plugins/jquery-toastmessage/  # opcionális legacy flash_
  plugins/trumbowyg/            # csak ha lesz .editor mező
  fontawesome/                  # all.min + v4-shims + v4-font-face
  img/                          # logo, avatars (sidebar/header)
  favicon.ico
```

`style.css` kötelező szabályok (ha hiányoznak, add hozzá):

- `.index-data-table`, `.record-view-fields`, `.record-view-row`, `.custom-modal`, form label félkövér
- `th.datetime.created.modified` **ne** legyen `display:flex` (maradjon `table-cell`); a benne lévő `a` legyen `display:block`

### 2.2 Routing (`config/routes.php`)

```php
use Cake\Routing\Route\DashedRoute;

// Szerepkör panelek — nincs {lang} a URL-ben (locale = session / user ország)
$panelPrefixes = ['Admin', 'New', 'Member', 'Clubpresident', 'President'];
foreach ($panelPrefixes as $prefix) {
    $routes->prefix($prefix, function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });
}

$routes->scope('/', function (RouteBuilder $builder): void {
    $builder->connect('/', ['controller' => 'Locales', 'action' => 'home']); // → login vagy role home
    $builder->fallbacks(DashedRoute::class);
});
```

Részletek: [users-auth.md](users-auth.md) §2 (RoleHome), [struktura.md](struktura.md).  
**Ne** állíts vissza `/{lang}/member` scope-ot, amíg a projekt külön nem kéri.

Default locale: `config/app.php` → `App.defaultLocale` = `hu_HU`.  
Panel locale bejelentkezés után: `BrowserLocale::forLoggedIn` (user ország / login session); `App.adminLocale` csak fallback — [i18n.md](i18n.md), [users-auth.md](users-auth.md).

### 2.3 Middleware + utility

Hozd létre (spec: [middleware.md](middleware.md)):

| Osztály | Felelősség |
|---------|------------|
| `App\Middleware\LocaleMiddleware` | session / cookie / Accept-Language → `Countries.locale` (**nincs** URL `{lang}`) |
| `App\Middleware\SanitizeAuthRedirectMiddleware` | `/login?redirect=/login…` loop ellen (CakeDC) |
| `App\Middleware\NormalizeLocalizedDateMiddleware` | POST body dátum/idő → `Y-m-d` / `Y-m-d H:i:s` / `H:i:s` |
| `App\Middleware\NormalizeLocalizedNumberMiddleware` | POST body szám → `1234.56` |
| `App\Utility\LocaleDateParser` | parse logika |
| `App\Utility\LocaleNumberParser` | parse logika |
| `App\Utility\BrowserLocale` | resolve / persist / `forLoggedIn` |
| `App\Auth\RoleHome` + `AppRoles` | role → panel URL (auth után) |
| `App\Middleware\HostHeaderMiddleware` | (ha a környezet igényli) |

`Application::middleware()` sorrend:

```
ErrorHandler → HostHeader → SanitizeAuthRedirect → Asset → Routing → Locale
→ BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
```

Dátum **előtt** a szám előtt (hogy `12.03.2024` ne legyen szám).

### 2.4 Controllerek alap

```
src/Controller/PanelAppController.php          # közös panel chrome (locale + admin layout)
src/Controller/Admin/AppController.php         # CRUD helpers + panel vars
src/Controller/Admin/DashboardController.php
src/Controller/New/{App,Dashboard}Controller.php
src/Controller/Member/{App,Dashboard}Controller.php
src/Controller/Clubpresident/{App,Dashboard}Controller.php
src/Controller/President/{App,Dashboard}Controller.php
src/Controller/LocalesController.php           # / → login vagy RoleHome
```

Admin / Panel `initialize()`: `BrowserLocale::forLoggedIn` + `viewBuilder()->setLayout('admin')` + `panelHomeUrl` / `panelSidebar` / `panelBrand`.  
Részletek: [users-auth.md](users-auth.md).
AppView (Admin Form hibák): `templates.errorClass` = `is-invalid` — [minta-tanulsagok.md](minta-tanulsagok.md) §6c.

### 2.5 Layout + elementek

| Fájl | Szerep |
|------|--------|
| `templates/layout/admin.php` | Csak közös CSS/JS; CSRF meta; `MyAdmin.messages` JSON `__()`-ből; `fetch('css'|'script'|'scriptBottom')` |
| `templates/element/admin/header.php` | Felső sáv — **ne** include-old a `header_language`-t |
| `templates/element/admin/sidebar.php` | Oldalsáv menü |
| `templates/element/admin/breadcrumb.php` | Cím + kontextus gombok (Back, New, Save, Edit, View, Delete) |
| `templates/element/admin/footer.php` | Lábléc |
| `templates/element/admin/header_*.php` | profile, alerts, messages, help, search (language fájl létezhet, de **ne** legyen behúzva) |
| `templates/element/admin/index_pagination.php` | Lista / keresés lapozó |
| `templates/element/admin/index_counter.php` | Bake `Paginator::counter` összesítő |
| `templates/element/admin/index_footer.php` | Card lábléc: counter + lapozó |
| `templates/element/admin/table_search.php` | Index szöveges kereső + nagyító (`Start search`) |
| `templates/element/admin/header_search.php` | Globális kereső → `/admin/search` |
| `templates/element/admin/modal_record_view.php` | `#modalRecordView` |
| `templates/element/admin/modal_linked_record_view.php` | Kapcsolt rekord modal (ha kell) |
| `templates/element/admin/view_related_tabs.php` | View gyerek tab sheet-ek |
| `templates/element/admin/field_error.php` | Form mezőhiba összetett widgetnél |

Layout közös CSS (példa lista): `bootstrap.min`, fontawesome (+ v4 shims), `style`, sweetalert2.  
Layout közös JS: modernizr, jquery, moment, bootstrap.bundle, bridge/detect/fastclick/blockUI/nicescroll (ha a sablonhoz kellenek), pikeadmin, sweetalert2, **`app.js`**.

### 2.6 `window.MyAdmin` (`webroot/js/app.js`)

Minimális API (`webroot/js/app.js`):

- `MyAdmin.config` — oldal tölti fel
- `MyAdmin.messages` — layout tölti fel (lefordított stringek, `__()`)
- `MyAdmin.initTooltips()`
- `MyAdmin.confirmDelete({ onConfirm })` — törlés megerősítés (Swal)
- `MyAdmin.alert({ icon, title, text })` / `MyAdmin.alertError(text)` — **minden** egyéb üzenet

**Kötelező szabály:** Adminban tilos a `window.alert` / `confirm` / `prompt`. Mindig SweetAlert2 a fenti API-n keresztül.  
Részletek: [admin-konvenciok.md](admin-konvenciok.md) → „SweetAlert”.

Layout `MyAdmin.messages` legalább: `errorTitle`, `okButton`, `deleteTitle`, `deleteConfirm`, `deleteButton`, `cancelButton`, `failedToSave`, …
`pages/index.js`: sor dupla klikk → `rowDoubleClickAction` (`modal` / `edit` / `none`); delete → confirm → `#delete-form-{id}` submit; category-link → linked modal.

Index template elején (minden CRUD `index.php`):

```php
$rowDoubleClickAction = 'modal'; // modal | edit | none
$numberDecimals = ['integer' => 0, 'decimal' => 2]; // tizedesjegyek a listában
$showIdColumn = true;
$showCountColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;
```

Részletek: [admin-konvenciok.md](admin-konvenciok.md).  
`pages/form.js`: `#name` autofókusz; Tempus Dominus (date/time/datetime); **számmezők locale szerint** (`numberFormat`); Select2; `.btn-select2-add` + modal name-fókusz …

### 2.7 i18n

1. Minden UI string: `__('English msgid')` — lásd [i18n.md](i18n.md)
2. `resources/locales/hu_HU/default.po` — magyar `msgstr`
3. Extract: `bin/cake i18n extract ...`
4. Layout `MyAdmin.messages` kulcsai is `__()`-zel

### 2.8 Keresés + index állapot + last-visited (kötelező keretrész)

**Minden új projektben** ez a csomag a keretrendszer része — az első Admin felépítéskor másold be / építsd fel, ne hagyd „későbbre”.

#### Fájlok (egyszer)

| Elem | Útvonal |
|------|---------|
| Keresés config | `config/admin_search.php` — bootstrap: `Configure::load('admin_search')` |
| Helper | `src/Utility/AdminSearch.php` |
| AppController API | `applyIndexListState`, `applyIndexSearch`, `resolveIndexPageForLastVisited`, `redirectToIndexList`, `rememberLastVisited`, `setLastVisitedForIndex`, `indexListUrl` |
| Globális keresés | `Admin\SearchController` + `templates/Admin/Search/index.php` + `css/pages/search.css` (Google-szerű találat + modal) |
| Index kereső UI | `templates/element/admin/table_search.php` |
| Header kereső UI | `templates/element/admin/header_search.php` (header include) |
| Lapozó | `index_pagination` (First…Last), `index_counter`, `index_footer` |
| Scroll | `webroot/js/pages/index.js` → `.last-visited` a breadcrumb alá (~mt-3) |

Agent rules (másolat új repo `.cursor/rules/`-ba): `admin-kereses-index-allapot.mdc`, `admin-paginator.mdc`, `penznem-formatcurrency.mdc`, `pos-db-default.mdc`, `users-auth.mdc`, `auto-dokumentalas.mdc`.

#### Config — első felépítés + minden új Table

Az Admin **indulásakor** sorold fel az **összes** CRUD modelt és **minden szöveges** mezőt (`fields`). Új modulnál azonnal bővítsd.

```php
'Things' => [
    'label' => 'Things',           // msgid __()
    'controller' => 'Things',
    'titleField' => 'name',
    'labelsKey' => 'thing',        // Search modal entityFieldLabels
    'fields' => ['name', 'code'],  // string/text only — ne szám/dátum/bool/FK
],
```

Globális kulcsok: `queryParam`, `globalPageLimit` (20), `globalLimitPerModel` (200), `globalMaxResults` (1000).

| Kereső | Hol keres |
|--------|-----------|
| Index (`table_search`) | Csak az adott model `fields` |
| Header (`header_search`) | Összes model összes `fields` → `/admin/search` (Google UI + lapozás) |

#### Viselkedés (ne térj el)

| Esemény | Elvárás |
|---------|---------|
| Index `?q=` | Szűrt lista; session `Admin.indexState[Alias]` (sort, direction, page, q) |
| Bare index URL | Sessionből visszatölt (ugyanaz az oldal / szűrő / rendezés) |
| Save / Back to list | `redirectToIndexList('Alias')` / `$indexListUrl` — **ne** bare `index` |
| Clear search | `?clear_search=1` → `q` törölve, **szűretlen** lista; `resolveIndexPageForLastVisited` → a **last-visited** rekord **oldala**; scroll a kiemelt sorra. Nincs last-visited → keresés előtti oldal |
| Keresés submit | Form csak `q` (nincs `page`) → **mindig 1. oldal** |
| Lapozás (`?page=`) | `clearLastVisited` — kiemelés törlése |
| Üres keresőmező | A törlés (×) gomb **nem** jelenik meg |
| Tooltipek | `data-bs-html`: pl. `__('Start search')` + `__('Search in the text fields of this list.')` (index) / `…of all configured tables.` (globális); clear magyarázat; EN msgid + `hu_HU` `.po` |

#### Index controller minta

```php
$redirect = $this->applyIndexListState('Things');
if ($redirect !== null) {
    return $redirect;
}
$paginateOptions = $this->indexPaginateOptions(['sortableFields' => [/* … */]]);
$query = $this->applyIndexSearch($this->Things->find(), $this->Things);
$redirect = $this->resolveIndexPageForLastVisited('Things', $query, $paginateOptions);
if ($redirect !== null) {
    return $redirect;
}
$items = $this->paginate($query, $paginateOptions);
$this->setLastVisitedForIndex('Things');
```

**URL = igazság:** a listaállapot (sort / direction / **page** / q / limit) a query stringben van (könyvjelzőzhető). A `page=1` is mindig szerepel (egyedi `App\View\Helper\PaginatorHelper` — a Cake alapból elhagyná). Üres `/admin/things` → redirect a sessionben mentett kanonikus URL-re. Lapozáskor, ha az oldal változik → `clearLastVisited`.

Részletek: [admin-konvenciok.md](admin-konvenciok.md) → „Keresés” / „Index lista állapot” / „Utolsó rekord”.

### 2.9 CakeDC Users — Auth UI + role panelek

Teljes baseline + checklist: **[users-auth.md](users-auth.md)** (§0: mi stabil / mi képlékeny). Cursor rule: `users-auth.mdc`.

Röviden:

1. `composer require cakedc/users` + `Users.config` → `config/users.php` (App `Users`; login = **email** ha a baseline kell)
2. `config/permissions.php` — **ne** `'plugin' => false`; role→saját prefix; Search csak elnök+
3. `SanitizeAuthRedirectMiddleware` (lásd §2.3)
4. `RoleHome` + `PanelAppController` + New/Member/Clubpresident/President Dashboardok
5. `UsersController` + `UsersTable` (+ `country_id`, OneTimeLogin wrappers)
6. `templates/layout/login.php` + `templates/Users/*` + users_auth CSS/JS
7. afterLogin: **`$event->setResult(RoleHome::url($role))`** (ne `return`)
8. Header: Belépve + Profile / Change password / Logout; search role-gated

**Képlékeny:** más role-ok / más login-reg form / más auth provider → projektspecben leírni, majd frissíteni a `users-auth.md`-t.
### 2.10 Első CRUD modul

Kövesd a [crud-utmutato.md](crud-utmutato.md)-t egy **valós** táblára (vagy ideiglenes teszt táblára).

Kötelező checklist modulonként:

- [ ] Model + asszociációk (reserved entity név kezelve)
- [ ] Admin controller `index`: `applyIndexListState` + `applyIndexSearch` + `resolveIndexPageForLastVisited` + `paginate` + `setLastVisitedForIndex`
- [ ] Save / delete után: `redirectToIndexList('Alias')` (ne bare `['action'=>'index']`)
- [ ] `index.php` — `admin/table_search` + `admin/index_pagination` + `admin/index_footer` + teljes lista-minta ([admin-konvenciok.md](admin-konvenciok.md))
- [ ] `config/admin_search.php` — model + **összes** szöveges `fields` + `labelsKey`
- [ ] `form.php` — közös add/edit; `#name` autofocus; mentés try/catch + Flash
- [ ] `view.php` — `dl` + `view_related_tabs` gyerekekhez
- [ ] Sidebar menüpont
- [ ] Új stringek a `.po`-ban (kereső tooltip msgid-ek is, ha hiányoznak)
- [ ] `doc/valtozasok.md` bejegyzés

---

## 3. View + related tabs (rövid spec)

Részletek: [admin-konvenciok.md](admin-konvenciok.md) → „View (megnézés) UI”.

1. Fő card: `dl.record-view-fields` / `.record-view-row` — bake-szerű mezőlista
2. **belongsTo** a fő `dl`-ben (nem tab)
3. **hasMany** / **belongsToMany**: `$this->element('admin/view_related_tabs', ['relatedTabs' => [...]])`
4. Tab paraméterek: `id`, `title`, `count` (opcionális), `table` (HTML string; üres → „No related records.” / „Nincs adat.”)
5. Üres asszociációnál a **tab akkor is** megjelenik
6. Gyerek tábla: `.index-data-table` + típusoszlop osztályok; Actions: legalább View + Edit
7. CSS: `pages/index` a view-n is

---

## 4. Opcionális tesztmodulok (Samples minta)

Ha a keretrendszert próbálod ki üres domain mellett, ideiglenes táblákkal:

| Tábla | Asszociáció-tanulság |
|-------|----------------------|
| `parents` | hasMany samples; entity **nem** lehet `Parent` → `ParentRecord` |
| `samples` | belongsTo parent; belongsToMany cities through |
| `cities` | belongsToMany samples |
| `cities_samples` | through; join `pos` / `visible` → **DB default** (PHP ne erőltessen 1000-et) |

Ezek **eldobhatók** — a keretrendszer a fenti 1–3. szakasz. Teszt CRUD törlésekor ne töröld a layoutot, elementeket, middleware-t, `app.js` / `pages/*`, i18n szabályt.

---

## 5. Agent munkaszabály (új projektben) — NE kérdezd újra

Olvasd el és **kövesd** — a felhasználónak ne kelljen ezeket minden chatben elmondania:

1. Először: [admin-oldal.md](admin-oldal.md) (célkép) + ez a fájl + [admin-konvenciok.md](admin-konvenciok.md) + [i18n.md](i18n.md) + [middleware.md](middleware.md).
2. Üres projekt → **2. szakasz**. Új tábla → [crud-utmutato.md](crud-utmutato.md).
3. Címkék: `__('English msgid')` + `hu_HU/default.po`. Admin locale mindig `hu_HU` (middleware + AppController). **Nincs** nyelvválasztó az admin headerben.
4. Számok **kiírása** (index/view/modal): `LocaleNumberParser::format()`; count: `formatCount()`; pénz: `formatCurrency()` (HUF, ICU). Form: `numberFormat` + `.js-input-decimal`/`.js-input-integer`. Mentés: szám/dátum middleware.
5. Index fix oszlopszélesség (`style.css`): minta (`MyPluginTemplate`) + MyAdmin kiegészítés — `count`/`visible`/`boolean`/`date`/`datetime`/`time` a mintából; `id`/`pos`/`number`/`currency` fix; **`string` rugalmas**.
6. Modal kapcsolt névlisták: utolsó **20** `modified DESC`, megjelenítés **ABC ASC** (`containRelatedForModal` / `relatedNameLinksForModal`). View tab: teljes ABC lista OK.
7. Dialógusok: SweetAlert (`MyAdmin.swal` / `alert` / `alertError` / `confirmDelete` / `flashSwal`) — **tilos** `window.alert`; Bootstrap modal FocusTrap pause. Flash alap: **Simple Notify**; modal Flash: `flashSwal()`.
8. Select2 „+” ahol egyszerű create; HABTM multiple **mindkét** formon; `fetchTable()`, ne Association. **belongsTo lista** (Parent): `visible = true`, order `pos` ASC + `name` ASC; editnél aktuális szülő akkor is.
9. Form: `#name` autofocus + `form.js`; Tempus Dominus date/time/datetime; `newEntityWithSchemaDefaults()`; mentés try/catch + Flash; `beforeMarshal` ArrayObject → `getArrayCopy()`.
10. View: bake `dl` + `view_related_tabs` (üres tab is); kapcsolt nevek `.record-modal-link` + AJAX modal; `$rowDoubleClickAction` a kapcsolt táblára.
11. Index / keresés (kötelező csomag — [§2.8](#28-keresés--index-állapot--last-visited-kötelező-keretrész)): `$indexLimit` / `$indexMaxLimit`; `applyIndexListState` + `applyIndexSearch` + `resolveIndexPageForLastVisited`; `setLastVisitedForIndex` + scroll; `redirectToIndexList` save után; `admin/table_search` + header globális kereső (Google UI + lapozás); `admin/index_pagination` First…Last; `admin_search.php` mezőlista + `labelsKey` az **első** felépítéskor; clear → last-visited oldal; `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`; `*_count` → CounterCache + `formatCount`; `pos` = DB default; pénz → `formatCurrency`.
12. Típusos beállítások (ha kell): [setups.md](setups.md) — `setups` EAV + `SetupValue`.
13. Törlésvédelem: `PreventsDeleteWithChildrenTrait` + CounterCache; UI: törölhető = danger + Swal question; **nem** = secondary disabled ([minta-tanulsagok.md](minta-tanulsagok.md) §3). HABTM through + `cascadeCallbacks`; `bin/cake rebuild_counter_caches` ha kell.
14. Layoutba csak közös asset; oldalspecifikus a templateben.
15. Éles DB modul: [minta-tanulsagok.md](minta-tanulsagok.md) **§0 + §11**. Minden lényeges változás → `valtozasok.md` (+ érintett spec).

---

## 6. Ellenőrző lista „kész a keretrendszer”

- [ ] `/admin` betölt (Dashboard + layout)
- [ ] Nincs nyelvválasztó az admin headerben
- [ ] `MyAdmin.alert` / `confirmDelete` / Flash Notify működik; **nincs** `window.alert` az admin JS-ben
- [ ] Legalább egy CRUD: lista dupla klikk modal működik
- [ ] Form: `#name` fókusz; Parent/belongsTo lista visible + pos/name sorrend; Tempus ha van dátum; szám/dátum middleware; Select2 „+”; mentés Flash (ne nyers PHP)
- [ ] Index számok locale szerint; fix oszlopok: id/pos/number/currency/count/boolean/date/time ([admin-oldal.md](admin-oldal.md) §4.3); `string` rugalmas; count 0 üres
- [ ] Keresés csomag ([§2.8](#28-keresés--index-állapot--last-visited-kötelező-keretrész)): `admin_search.php` kitöltve (`fields` + `labelsKey`); index + header kereső; Search lapozás; First…Last paginator; session sort/page/q; clear → last-visited oldal + scroll; `redirectToIndexList`
- [ ] `.cursor/rules/` másolva (keresés, paginator, pénz, pos, auto-dokumentálás)
- [ ] View: fő mezők + (ha van gyerek) tab sheet ASC, üresen is; modal linkek (modal lista: max 20 modified → ABC)
- [ ] CounterCache + törlésvédelem; Delete UI: secondary disabled ha van gyerek
- [ ] Flash / gombok magyarul (`hu_HU` .po)
- [ ] `doc/minta-tanulsagok.md` §0–11 tükrözi az éles építési szabályokat
