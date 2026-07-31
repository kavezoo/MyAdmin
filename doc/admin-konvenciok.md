# Admin UI és asset konvenciók

**Teljes kép (kinézet + működés összefoglaló):** [admin-oldal.md](admin-oldal.md) — olvasd először, ha az oldal „hogy nézzen ki / működjön” a kérdés.  
Greenfield: [uj-projekt.md](uj-projekt.md). Szövegek: [i18n.md](i18n.md).

Ez a fájl a **részletes viselkedési specifikáció**: layout, asset, index/form/view, JS API — új projektben is ezek szerint írd meg a kódot, akkor is, ha még nincs „Samples” minta.

## Nyelv / i18n (röviden)

- Minden címke és statikus szöveg: `__('English msgid')` — **minden prefix** alatt.
- Admin locale: `hu_HU` → fordítás: `resources/locales/hu_HU/default.po`.
- Admin headerben **nincs** nyelvválasztó.
- JS: `MyAdmin.messages` (layout, `__()`-ből) + oldalspecifikus config `json_encode` + `__()`.

## Asset betöltési szabályok (kötelező)

### Layout — minden admin oldal (`templates/layout/admin.php`)

**Csak** a közös fájlok:

- CSS: bootstrap, fontawesome (+ v4 shims), `style.css`, sweetalert2
- JS (body végén): modernizr, jquery, moment, bootstrap.bundle, bridge, detect, fastclick, blockUI, nicescroll, pikeadmin, sweetalert2, **`app.js`**
- CSRF: `<meta name="csrfToken">`

**Nem** kerül a layoutba: Select2, Trumbowyg, daterangepicker, inputmask, page CSS/JS.

### Template — oldalspecifikus

A view-ban `block`-kal:

```php
// CSS a <head>-be
$this->Html->css([...], ['block' => true]);

// Config inline a layout JS után (script block)
$this->Html->scriptBlock('window.MyAdmin.config = ...', ['block' => 'script']);

// Page JS a config után
$this->Html->script([...], ['block' => 'scriptBottom']);
```

| Oldaltípus | CSS | JS / plugin |
|------------|-----|-------------|
| **index** (lista) | `pages/index` | `pages/index` + `MyAdmin.config` (URL-ek, field label-ek) |
| **form** (add/edit) | daterangepicker, select2, select2-bootstrap5, `pages/form` | daterangepicker, inputmask, select2, `pages/form` |
| **view** | `pages/index` (record-view + related tabs) | — |
| WYSIWYG / Prism | **csak** ha van `.editor` mező a formon | trumbowyg + pluginek |

Trumbowyg / WYSIWYG **csak** akkor kerüljön a form templatebe, ha van `.editor` mező.

## JavaScript API — `window.MyAdmin`

Layout: `webroot/js/app.js`

```js
MyAdmin.config = { /* oldal tölti fel */ };
MyAdmin.initTooltips();
MyAdmin.confirmDelete({ onConfirm: fn });
MyAdmin.alert({ icon: 'error', text: '…' });
MyAdmin.alertError('…');
```

### SweetAlert — kötelező (tilos a `window.alert`)

Az Admin UI-n **minden** felhasználói dialógus / hiba / info üzenet **SweetAlert2** legyen.  
**Ne** használd: `window.alert()`, `window.confirm()`, `window.prompt()`.

| API (`webroot/js/app.js`) | Mikor |
|---------------------------|--------|
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés megerősítés (index sor, breadcrumb, …) |
| `MyAdmin.alert({ icon, title, text })` | Általános üzenet (`icon`: `error` / `success` / `info` / `warning`) |
| `MyAdmin.alertError(text)` | Rövid hiba (Select2 mentés sikertelen, hiányzó URL, …) |

Layout `MyAdmin.messages` kötelező kulcsok (mind `__()`-zel):

- `errorTitle`, `successTitle`, `infoTitle`, `okButton`
- `deleteTitle`, `deleteConfirm`, `deleteButton`, `cancelButton`
- `failedToSave` / `saveNewValueFailed`, `noServerResponseSaveFailed`, …

Új JS hibajelzésnél: hívd `MyAdmin.alertError(üzenet)`-t (vagy `MyAdmin.alert`), és ha új szöveg, tedd a layout messages + `.po` fájlba.

Példa (form / AJAX hiba):

```js
MyAdmin.alertError(message || MyAdmin.messages.failedToSave);
// NE: window.alert(message);
```

### Index config (kötelező / opcionális kulcsok)

Kötelező:

- `rowDoubleClickAction` — `'modal'` | `'edit'` | `'none'` (sor dupla kattintás)
- `recordGetUrl`, `editUrl`, `viewUrl` (`edit` / `modal` módhoz szükségesek)
- `recordFieldLabels` (mezőkulcs → lefordított címke)

Opcionális (ha van kapcsolt „category” / szülő link a listában):

- `categoryGetUrl`, `parentEditUrl`, `parentViewUrl`, `categoryFieldLabels`

AJAX: path + `/{id}` (pl. `/admin/items/record-get/1`).  
Delete: SweetAlert → `#delete-form-{id}` postLink submit.

### Form config

- `indexUrl` — Cancel / Close
- Select2 „+”: az URL-ek a **gomb** `data-create-url` attribútumán (nem kötelező a configban)

Form root: `#form-horizontal`. Mező ID-k: Cake FormHelper alapértelmezés (`#` + underscored field name).

## Lista (index) UI — kötelező minden Admin CRUD-nál

Minden index lista **ugyanazt** a mintát kövesse ([uj-projekt.md](uj-projekt.md) + ez a szakasz).  
Referencia-implementáció (ha van a projektben): bármely teljes CRUD `index.php` + `webroot/js/pages/index.js`.

### Kötelező elemek

1. Külső wrapper: `<div class="row mt-3"><div class="col-12 p-2 pt-0">` (térköz a breadcrumb alatt)
2. Card fejléc: cím, súgó („Dupla kattintással…”), Search (opcionális bekötés később), lapozás
3. Tábla: `.table.index-data-table`
4. Sor: `id="record-{id}"` `data-id="{id}"`
5. Actions: View / Edit / Delete (outline + HTML tooltip) + rejtett `#delete-form-{id}` postLink
6. Lábléc: Showing X–Y of Z + pagination element
7. Modal: `#modalRecordView` (+ linked modal, ha van kapcsolt entitás link)
8. Page asset: `pages/index` CSS + `pages/index` JS + `MyAdmin.config` (legalább `recordGetUrl`, `editUrl`, `viewUrl`, `recordFieldLabels`)
9. Controller: `recordGet` JSON action a modalhoz (kapcsolt névlisták **ASC** ABC-sorrendben)

### Modal — kapcsolt (HABTM / hasMany) listák sorrendje

A `#modalRecordView` mezőiben megjelenő kapcsolt nevek (pl. „City list: A, B, C”) **mindig ABC (ASC)** sorrendben:

```php
contain: [
    'Cities' => fn ($q) => $q->orderBy(['Cities.name' => 'ASC']),
]
```

Ugyanez a `view()` contain-nél, ha a view tab / összefoglaló ugyanazt a listát mutatja.  
Részletek: [crud-utmutato.md](crud-utmutato.md) → `recordGet`.

### Rendezés (sort) — URL-ből, ne hardcoded order

- Az `index()` **ne** állítson be előre `orderBy([...])`-t a query-n.
- A rendezés a Paginator URL paraméterekből jön: `?sort=field&direction=asc|desc`.
- A `th`-okban `$this->Paginator->sort('mezo', 'Címke')`.
- A `paginate()` hívásban add meg a `sortableFields` listát (különösen associált mezőknél, pl. `Parents.name`).
- Az `index()` később még finomodik (session, default sort, search) — addig is tartsd URL-alapúnak.

```php
// Helyes
$items = $this->paginate($this->Model->find(), [
    'limit' => 10,
    'sortableFields' => ['id', 'name', 'created', 'modified', /* … */],
]);

// Kerülendő
$query->orderBy(['Model.id' => 'ASC']);
```

### Oszloptípus osztályok (`th` / `td`)

| Osztály | Jelentés | Fix CSS szélesség | Forrás |
|---------|----------|-------------------|--------|
| `string` | szöveg | **nincs** (rugalmas) | — |
| `id` | ID (~7–8 jegy) | `4.75rem` | MyAdmin (minta: nincs fix) |
| `pos` | pozíció (max ~5 jegy + locale ezres) | `5.5rem` | MyAdmin (mintában a `.count`-tal egyező nagyságrend) |
| `number` (pl. `.szam`, nem id/pos/count) | általános szám | `6.5rem` | MyAdmin (minta: csak `nowrap`) |
| `currency` / `netto` | pénz (összeg + pénznem) | `12rem` | MyAdmin (minta: csak `nowrap`; ~4–5 jegy plusz a korábbi 8.5-höz) |
| `count` | `*_count` | `5.5rem` | **MyPluginTemplate** |
| `boolean` / `logikai` / `visible` / `valid` | logikai | `7.5rem` | **MyPluginTemplate** (`.visible`/`.valid`) |
| `date` | dátum | `8.5rem` | **MyPluginTemplate** |
| `datetime` | dátum+idő | `10.5rem` | **MyPluginTemplate** |
| `time` | idő | `5rem` | **MyPluginTemplate** |
| `times` | időtartomány | `9rem` | **MyPluginTemplate** |
| `actions` | gombok | — | — |

Szabály: **szám / pénz / logikai / id / pos / count / dátum-idő** oszlopok fix szélességűek; **szöveges** (`string`) oszlopok szabadon nyúlhatnak.  
CSS: `webroot/css/style.css` — minta forrás: `MyPluginTemplate/assets/css/style.css` (ahol van `width`).

Számok **megjelenítése**: `LocaleNumberParser::format(..., decimals: $numberDecimals['integer'|'decimal'])`. Címkék: `__('English')`.

```php
<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($row->pos, decimals: $numberDecimals['integer'])) ?></td>
<td class="currency netto text-end"><span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::format($row->netto, decimals: $numberDecimals['decimal'])) ?></span> HUF</td>
```

### Created + Modified közös oszlop

Mint a MyPluginTemplate-ben: **egy** `th` két sort linkkel, osztály: `datetime created modified`.

```php
<th scope="col" class="datetime created modified">
    <?= $this->Paginator->sort('created', __('Created')) ?>
    <?= $this->Paginator->sort('modified', __('Modified')) ?>
</th>
```

Cellában mindkét dátum, `<br>`-rel.  
CSS: a `th`-n **tilos** a `display: flex` (elveszíti a `table-cell` viselkedést → alsó border feljebb ugrik). Stackelt linkek: `display: block` az `a`-kon (`webroot/css/style.css`).

### Index template tipikus elején (tooltip + config)

```php
$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
// Figyelem: minden __('…') hívást zárd le )-vel — hiányzó zárójel ParseError.
```

### Viselkedés

- Sor dupla klikk → `$rowDoubleClickAction` szerint (`modal` / `edit` / `none`) — lásd alább
- `.category-link` → linked modal (ha van)
- Delete → SweetAlert → postLink form submit
- Tooltippek az action gombokon

### Sor dupla kattintás (`$rowDoubleClickAction`)

Az `index.php` **elején** állítsd (majd tedd a `MyAdmin.config`-ba is):

```php
/**
 * 'modal' → gyors nézet modal (recordGet)
 * 'edit'  → szerkesztő form (ugyanaz, mint az Edit gomb)
 * 'none'  → nincs művelet
 */
$rowDoubleClickAction = 'modal';

$config = [
    'rowDoubleClickAction' => $rowDoubleClickAction,
    // …
];
```

| Érték | Hatás |
|-------|--------|
| `modal` | `#modalRecordView` + `recordGet` AJAX (alapértelmezés) |
| `edit` | navigálás `editUrl/{id}`-re |
| `none` | semmi |

A card fejléc súgószövege igazodjon az értékhez (`$rowDoubleClickHint`). JS: `webroot/js/pages/index.js`.

### Opcionális oszlopok (`$show*Column`)

A `$rowDoubleClickAction` **után** az `index.php` elején:

```php
/**
 * Hány tizedesjeggyel a számok (locale szerinti kiírás).
 * integer = egész mezők; decimal = tört/pénz (netto, …)
 */
$numberDecimals = [
    'integer' => 0,
    'decimal' => 2,
];

$showCountColumn = true;    // *_count (gyerek számosság)
$showVisibleColumn = true;  // visible
$showCreatedColumn = true;  // created
$showModifiedColumn = true; // modified — külön kapcsolható
```

Használat:

```php
LocaleNumberParser::format($row->netto, decimals: $numberDecimals['decimal']);
LocaleNumberParser::format($row->pos, decimals: $numberDecimals['integer']);
```

| Változó | Oszlop |
|---------|--------|
| `$numberDecimals['integer']` / `['decimal']` | tizedesjegyek a listában |
| `$showCountColumn` | `*_count` (pl. `city_count`, `sample_count`) |
| `$showVisibleColumn` | `visible` |
| `$showCreatedColumn` | `created` (önállóan) |
| `$showModifiedColumn` | `modified` (önállóan) |

- Ha **mindkét** dátum be van kapcsolva: egy közös `th` / `td` (`datetime created modified`), két sort link / két sor a cellában (mint eddig).
- Ha csak az egyik: egy oszlop, csak az a mező + sort.
- Ha egyik sem: nincs timestamp oszlop.
- A `thead` / `tbody` cellákat `if ($show…)`-vel rendereld; az üres lista `colspan` = `$indexColspan`.

Ez **csak** az index táblára vonatkozik; a view / modal továbbra is mutathatja ezeket a mezőket.

## View (megnézés) UI — kötelező minta

Implementáld a specifikáció szerint; element: `admin/view_related_tabs`.  
(Ha van demó a projektben, pl. egy `view.php` a teljes CRUD mintában — a szabály az alábbi, nem a mezőnevek.)

### Fő rekord (bake-szerű adatlap)

1. Card: cím (`… details`), rövid súgó, bezáró gomb → index
2. Mezők: `<dl class="record-view-fields">` + `.record-view-row` / `dt` / `dd` (mint a bake view)
3. **belongsTo** szülő (pl. Parent neve) a fő adatlapra kerül — **nem** külön tab
4. Lábléc: Edit + Back to list
5. CSS: `$this->Html->css(['pages/index'], ['block' => true]);`
6. Controller `view()`: `contain` a megjelenített asszociációkra (gyerekek + belongsTo)

### Kapcsolt gyerek táblák — tab sheet

Minden **hasMany** / **belongsToMany** (és hasonló „gyerek”) asszociáció külön Bootstrap **tab**-ban jelenik meg a fő card **alatt**.

| Szabály | Részlet |
|---------|---------|
| Element | `templates/element/admin/view_related_tabs.php` |
| Tab megjelenés | **Mindig** — üres asszociációnál is (üres tartalom + `__('No related records.')`) |
| Tab cím | Asszociáció neve + opcionális `(count)` |
| Tábla | Index-szerű: `.table.index-data-table`, típusoszlop osztályok (`number`, `string`, `boolean`, …) |
| Actions a gyerek soron | legalább View + Edit a gyerek modulra (outline gombok) |
| Rendezés / lapozás | view-n **nem** kötelező |
| belongsTo | **ne** legyen tab — a fő `dl`-ben marad |

```php
<?= $this->element('admin/view_related_tabs', [
    'relatedTabs' => [
        [
            'id' => 'cities',           // egyedi slug → HTML id
            'title' => __('Cities'),
            'count' => $citiesCount,    // opcionális; üresnél is 0
            'table' => $citiesTableHtml, // üres string → „Nincs adat.”
        ],
        // további gyerek asszociációk = további tabok
    ],
]); ?>
```

Több gyerek asszociáció → több tab ugyanabban a cardban. A tábla HTML-t a view-ban `ob_start()` / `ob_get_clean()`-nel (vagy partial elementtel) építsd; ha nincs rekord, add át üres stringet.

### Asszociáció → megjelenés mapping

| Asszociáció típus | Hol jelenik meg |
|-------------------|-----------------|
| belongsTo | Fő `dl` (név / címke mező) |
| hasMany | Saját tab + index-szerű tábla |
| belongsToMany | Saját tab + index-szerű tábla |
| hasOne | Általában fő `dl` (ha kevés mező); különben tab |

## Form (add / edit) UI

- Add és edit **ugyanaz** a `form.php`; controller `render('form')`
- Card: cím, Created/Modified (edit), bezáró gomb
- Bootstrap rács: label `col-md-2`, mező jobbra; címkék félkövérek (`style.css`)
- Lábléc: Save + Cancel; Save a breadcrumbben is (`form="form-horizontal"`)
- Kapcsolók: Visible / boolean switch-ek
- Dátumok: daterangepicker + inputmask, formátum `yyyy-mm-dd` / `hh:mm`
- **Számok (i18n):** inputmask a locale szerint — lásd alább
- Select2: lásd alább (single **és** multiple „+” gomb)

### Számmezők — locale / i18n (kötelező)

Admin locale `hu_HU` → tizedes **`,`**, ezres **szóköz** (pl. `1 234,56`).  
**Ne** hardkódold az angol inputmaskot (`radixPoint: '.'`, `groupSeparator: ','`) — abból mentéskor számjegyvesztés lesz (`1,234.56` → ORM `1`).

1. Form config: `'numberFormat' => LocaleNumberParser::jsConfig()`
2. Mezők: `.js-input-decimal` / `.js-input-integer`
3. Value megjelenítés: `LocaleNumberParser::format($value, decimals: 2|0)`
4. Mentés: `NormalizeLocalizedNumberMiddleware` → kanonikus `1234.56`

Részletek: [middleware.md](middleware.md).

### Select2 „+” — inline új elem (single + multiple)

**Kötelező** minden olyan Select2 mezőnél (single **és** multiple), ahol a felhasználó felvehet új kapcsolt rekordot a form elhagyása nélkül.

#### UI szerkezet

```html
<div class="select2-with-add">
  <div class="select2-with-add-field">
    <!-- select: .js-example-basic-single | .js-example-basic-multiple -->
  </div>
  <button type="button"
    class="btn btn-outline-secondary btn-select2-add"
    data-select2-target="#field-id"
    data-create-url="/admin/.../select2-create-..."
    data-bs-toggle="modal"
    data-bs-target="#modalSelect2Add...">
    <i class="fa fa-plus"></i>
  </button>
</div>
```

| Attribútum | Szerep |
|------------|--------|
| `data-select2-target` | Cél `<select>` CSS selector |
| `data-create-url` | POST JSON endpoint (DashedRoute) |
| `data-bs-target` | Saját modal az adott asszociációhoz |

#### Modal

- Osztály: `.modal-select2-add`
- Belső form: `.select2-add-form`
- Listában megjelenő név mező: `data-select2-text="1"` (általában `name`)
- **Jövő:** a modalban több mező is lehet (pl. név + kód + megjegyzés); a Select2 option szövege **csak** a `data-select2-text` / válasz `text` mező. A többi mező a POST body része, a controller elmenti.
- Mentés gomb: `.btn-select2-add-save`

#### JS viselkedés (`webroot/js/pages/form.js`)

1. Modal megnyitás → `relatedTarget` gombból target + create URL
2. Save → form mezők összegyűjtése → AJAX POST (CSRF)
3. Válasz: `{ success: true, id, text }`
4. Új `<option>` a selectbe; **immediate select**:
   - **single:** `val(id)`
   - **multiple:** meglévő kiválasztások megmaradnak, az új `id` **hozzáadódik**
5. Select2 `tags: true` + gépelős új tag ugyanarra az endpointokra megy (ha van `data-create-url` a kapcsolt „+” gombon)

#### Controller

- Külön action mezőnként / entitásonként (pl. `select2Create`, `select2CreateCity`), vagy közös helper.
- A Table-t **`fetchTable('Cities')`** / **`fetchTable('Parents')`** add át — ne `$this->Samples->Cities`-t: az **Association** (belongsToMany), nem `Table` (type error `select2CreateEntity`-nél).
- Válasz mindig: `id` + `text` (a listában látható címke).
- Extra modal mezők: `getData()`-ból az entity-be; a listában **ne** jelenjen meg az összes mező.

#### Példa (demó Samples form)

| Mező | Típus | Modal | Endpoint |
|------|-------|-------|----------|
| Parent | single | New parent | `select2Create` |
| Cities | multiple | New city | `select2CreateCity` |

## Breadcrumb eszköztár

Element: `templates/element/admin/breadcrumb.php`  
Gombok kontextus szerint: Back, New, Save, Edit, View details, Delete.

## CakePHP 5 figyelmeztetések

- `find('list', keyField: 'id', valueField: 'name')` — **named argument**, ne options tömb!
- Paginator: **ne** `loadComponent('Paginator')`; használd `$this->paginate($query)`
- Entity név: `Parent` tilos → `ParentRecord`