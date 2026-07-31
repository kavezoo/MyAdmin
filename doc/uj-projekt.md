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
  js/pages/form.js              # select2, daterangepicker, inputmask, select2-add modal
  plugins/sweetalert2/
  plugins/select2/
  plugins/daterangepicker/ (vagy datetimepicker a sablon szerint)
  plugins/inputmask/
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

// Admin — nincs {lang} a URL-ben
$routes->prefix('Admin', function (RouteBuilder $builder): void {
    $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
    $builder->fallbacks(DashedRoute::class);
});

// Member — opcionális, de a locale middleware-hez illeszkedik
$routes->scope('/{lang}', function (RouteBuilder $builder): void {
    $builder->connect('/', ['prefix' => 'Member', 'controller' => 'Dashboard', 'action' => 'index']);
    // vagy redirect → Member
    $builder->prefix('Member', function (RouteBuilder $builder): void {
        $builder->fallbacks(DashedRoute::class);
    });
});
```

`{lang}` whitelist: `config/languages.php` + `Configure::read('App.languages')`.

Default locale: `config/app.php` → `App.defaultLocale` = `hu_HU`.

### 2.3 Middleware + utility

Hozd létre (spec: [middleware.md](middleware.md)):

| Osztály | Felelősség |
|---------|------------|
| `App\Middleware\LocaleMiddleware` | Admin → mindig `hu_HU`; Member → `{lang}` |
| `App\Middleware\NormalizeLocalizedDateMiddleware` | POST body dátum/idő → `Y-m-d` / `Y-m-d H:i:s` / `H:i:s` |
| `App\Middleware\NormalizeLocalizedNumberMiddleware` | POST body szám → `1234.56` |
| `App\Utility\LocaleDateParser` | parse logika |
| `App\Utility\LocaleNumberParser` | parse logika |
| `App\Middleware\HostHeaderMiddleware` | (ha a környezet igényli) |

`Application::middleware()` sorrend:

```
ErrorHandler → HostHeader → Asset → Routing → Locale
→ BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
```

Dátum **előtt** a szám előtt (hogy `12.03.2024` ne legyen szám).

### 2.4 Controllerek alap

```
src/Controller/Admin/AppController.php
src/Controller/Admin/DashboardController.php
src/Controller/Member/AppController.php   # opcionális, ha van Member
src/Controller/Member/DashboardController.php
```

Admin `AppController::initialize()`:

```php
I18n::setLocale('hu_HU');
$this->viewBuilder()->setLayout('admin');
```

### 2.5 Layout + elementek

| Fájl | Szerep |
|------|--------|
| `templates/layout/admin.php` | Csak közös CSS/JS; CSRF meta; `MyAdmin.messages` JSON `__()`-ből; `fetch('css'|'script'|'scriptBottom')` |
| `templates/element/admin/header.php` | Felső sáv — **ne** include-old a `header_language`-t |
| `templates/element/admin/sidebar.php` | Oldalsáv menü |
| `templates/element/admin/breadcrumb.php` | Cím + kontextus gombok (Back, New, Save, Edit, View, Delete) |
| `templates/element/admin/footer.php` | Lábléc |
| `templates/element/admin/header_*.php` | profile, alerts, messages, help, search (language fájl létezhet, de **ne** legyen behúzva) |
| `templates/element/admin/index_pagination.php` | Lista lapozó |
| `templates/element/admin/modal_record_view.php` | `#modalRecordView` |
| `templates/element/admin/modal_linked_record_view.php` | Kapcsolt rekord modal (ha kell) |
| `templates/element/admin/view_related_tabs.php` | View gyerek tab sheet-ek |

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
`pages/form.js`: `#name` autofókusz; daterangepicker + inputmask; **számmezők locale szerint** (`numberFormat`); Select2; `.btn-select2-add` + modal name-fókusz …

### 2.7 i18n

1. Minden UI string: `__('English msgid')` — lásd [i18n.md](i18n.md)
2. `resources/locales/hu_HU/default.po` — magyar `msgstr`
3. Extract: `bin/cake i18n extract ...`
4. Layout `MyAdmin.messages` kulcsai is `__()`-zel

### 2.8 Első CRUD modul

Kövesd a [crud-utmutato.md](crud-utmutato.md)-t egy **valós** táblára (vagy ideiglenes teszt táblára).

Kötelező checklist modulonként:

- [ ] Model + asszociációk (reserved entity név kezelve)
- [ ] Admin controller: index, add, edit→`form`, view (+ contain, kapcsolt lista **ASC**), delete, `recordGet` (modal lista is ASC)
- [ ] `index.php` — teljes lista-minta ([admin-konvenciok.md](admin-konvenciok.md))
- [ ] `form.php` — közös add/edit; `#name` autofocus; mentés try/catch + Flash
- [ ] `view.php` — `dl` + `view_related_tabs` gyerekekhez
- [ ] Sidebar menüpont
- [ ] Új stringek a `.po`-ban
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
4. Számok **kiírása** (index/view/modal): `LocaleNumberParser::format()`; count: `formatCount()`; pénznem: `currencySymbol()` → **Ft**. Form: `numberFormat` + `.js-input-decimal`/`.js-input-integer`. Mentés: szám/dátum middleware.
5. Index fix oszlopszélesség (`style.css`): minta (`MyPluginTemplate`) + MyAdmin kiegészítés — `count`/`visible`/`boolean`/`date`/`datetime`/`time` a mintából; `id`/`pos`/`number`/`currency` fix; **`string` rugalmas**.
6. Modal/view kapcsolt listák: **ABC ASC** (`contain` + `orderBy` name).
7. Dialógusok: csak SweetAlert (`MyAdmin.alert` / `alertError` / `confirmDelete`) — **tilos** `window.alert`.
8. Select2 „+” ahol egyszerű create; HABTM multiple **mindkét** formon; `fetchTable()`, ne Association. **belongsTo lista** (Parent): `visible = true`, order `pos` ASC + `name` ASC; editnél aktuális szülő akkor is.
9. Form: `#name` autofocus + `form.js`; `newEntityWithSchemaDefaults()` (pos/visible/… = DB); mentés try/catch + Flash; `beforeMarshal` ArrayObject → `getArrayCopy()`.
10. View: bake `dl` + `view_related_tabs` (üres tab is); kapcsolt nevek `.record-modal-link` + AJAX modal; `$rowDoubleClickAction` a kapcsolt táblára.
11. Index: `$indexLimit` / `$indexMaxLimit` + `indexPaginateOptions()`; `setLastVisitedForIndex` + `.last-visited`; `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`; `*_count` → `formatCount`; `pos` = DB default.
12. Layoutba csak közös asset; oldalspecifikus a templateben.
13. Minden lényeges változás → `valtozasok.md` (+ érintett spec, különösen `admin-oldal.md` / `admin-konvenciok.md`).

---

## 6. Ellenőrző lista „kész a keretrendszer”

- [ ] `/admin` betölt (Dashboard + layout)
- [ ] Nincs nyelvválasztó az admin headerben
- [ ] `MyAdmin.alert` / `confirmDelete` működik; **nincs** `window.alert` az admin JS-ben
- [ ] Legalább egy CRUD: lista dupla klikk modal működik
- [ ] Form: `#name` fókusz; Parent/belongsTo lista visible + pos/name sorrend; szám/dátum middleware; Select2 „+”; mentés Flash (ne nyers PHP)
- [ ] Index számok locale szerint; fix oszlopok: id/pos/number/currency/count/boolean/date/time ([admin-oldal.md](admin-oldal.md) §4.3); `string` rugalmas; count 0 üres
- [ ] View: fő mezők + (ha van gyerek) tab sheet ASC, üresen is; modal linkek
- [ ] Flash / gombok magyarul (`hu_HU` .po)
- [ ] `doc/` naprakész (`admin-oldal.md` is tükrözi a célképet)
