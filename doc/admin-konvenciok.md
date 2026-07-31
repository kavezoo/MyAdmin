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
| **view** | `pages/index` (record-view + related tabs) | `pages/index` JS + `MyAdmin.config` (`rowDoubleClickAction`, `entityFieldLabels`) + `modal_linked_record_view` |
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
Delete: SweetAlert → `#delete-form-{id}` **form** submit (`Form->create`). Gyerek rekordnál (`*_count > 0`) a gomb disabled.

### Form config

- `indexUrl` — Cancel / Close
- Select2 „+”: az URL-ek a **gomb** `data-create-url` attribútumán (nem kötelező a configban)

Form root: `#form-horizontal`. Mező ID-k: Cake FormHelper alapértelmezés (`#` + underscored field name).
**Minden** Admin form: `pages/form` JS + `#name` `autofocus` → `focusPrimaryFormField()` (pluginok után). Select2 „+” modal: `shown.bs.modal` → `[data-select2-text]` fókusz.

## Lista (index) UI — kötelező minden Admin CRUD-nál

Minden index lista **ugyanazt** a mintát kövesse ([uj-projekt.md](uj-projekt.md) + ez a szakasz).  
Referencia-implementáció (ha van a projektben): bármely teljes CRUD `index.php` + `webroot/js/pages/index.js`.

### Kötelező elemek

1. Külső wrapper: `<div class="row mt-3"><div class="col-12 p-2 pt-0">` (térköz a breadcrumb alatt)
2. Card fejléc: cím, súgó („Dupla kattintással…”), Search (opcionális bekötés később), lapozás
3. Tábla: `.table.index-data-table`
4. Sor: `id="record-{id}"` `data-id="{id}"`; ha `$lastVisitedId` egyezik → `class="last-visited"`
5. Actions: View / Edit / Delete (outline + HTML tooltip). Ha `*_count > 0`: Delete **disabled** + tooltip a tilalom okáról. Egyébként rejtett `#delete-form-{id}` (**`Form->create`**, nem `postLink`)
6. Lábléc: Showing X–Y of Z + pagination element
7. Modal: `#modalRecordView` (+ linked modal, ha van kapcsolt entitás link) — Delete gomb `can_delete` szerint enable/disable
8. Page asset: `pages/index` CSS + `pages/index` JS + `MyAdmin.config` (legalább `recordGetUrl`, `editUrl`, `viewUrl`, `recordFieldLabels`)
9. Controller: `recordGet` JSON (`can_delete` flag is) a modalhoz (kapcsolt névlisták **ASC** ABC-sorrendben); `setLastVisitedForIndex('Alias')`

### Utolsó rekord (`.last-visited`) — session

Az utoljára megtekintett / szerkesztésre megnyitott / sikeresen mentett (új vagy szerkesztett) rekord sessionben:

| Kulcs | Tartalom |
|-------|----------|
| `Admin.lastVisited` | pl. `['Samples' => 12, 'Cities' => 3, '_last' => ['model' => 'Samples', 'id' => 12]]` |

| Esemény | Mentés |
|---------|--------|
| `view` / `edit` (betöltés) | `rememberLastVisited('Alias', $id)` |
| `add` / `edit` sikeres `save` | ugyanaz az új/mentett id-vel |
| `recordGet` / linked get | modal megtekintés is |

Index: `$this->setLastVisitedForIndex('Alias')` → template `$lastVisitedId` → sor `class="last-visited"`.  
CSS már a `style.css`-ben (MyPluginTemplate zöld kiemelés). A `index.js` modalnál is állítja a class-t (azonnali UI); a session a következő index betöltéshez kell. **Később bővül** (scroll, stb.).

### Modal — kapcsolt (HABTM / hasMany) listák sorrendje

A `#modalRecordView` / linked modal mezőiben megjelenő kapcsolt nevek (pl. „City list” / „Sample list”) **mindig ABC (ASC)** sorrendben, és **linkként** nyithatók (második modal):

```php
// recordGet JSON
'cities' => [ ['id' => 1, 'name' => 'A'], … ],  // nem implode string
'samples' => [ ['id' => 2, 'name' => 'B'], … ],
```

Index config:

```php
'relatedLinkFields' => [
    'samples' => [
        'getUrl' => …, 'editUrl' => …, 'viewUrl' => …, 'deleteUrl' => …,
        'labels' => 'sample', 'title' => __('Sample details'), 'deleteFormPrefix' => 'sample',
    ],
    // cities ugyanígy
],
'entityFieldLabels' => [ 'sample' => [ … ], 'city' => [ … ] ],
```

JS (`pages/index.js`): tömb `{id,name}` + `relatedLinkFields` → `.record-modal-link` → `#modalLinkedRecordView`.  
Indexen kötelező: `admin/modal_linked_record_view` element, ha van ilyen lista.

```php
contain: [
    'Cities' => fn ($q) => $q->orderBy(['Cities.name' => 'ASC']),
    // vagy Samples ASC a Cities recordGet-nél
]
```

Ugyanez a `view()` contain-nél / fő `dl` „list” sorában.  
Részletek: [crud-utmutato.md](crud-utmutato.md) → `recordGet`.

### Rendezés (sort) — URL-ből, ne hardcoded order

- Az `index()` **ne** állítson be előre `orderBy([...])`-t a query-n.
- A rendezés a Paginator URL paraméterekből jön: `?sort=field&direction=asc|desc`.
- A `th`-okban `$this->Paginator->sort('mezo', 'Címke')`.
- A `paginate()` hívásban add meg a `sortableFields` listát (különösen associált mezőknél, pl. `Parents.name`).
- Az `index()` később még finomodik (session, default sort, search) — addig is tartsd URL-alapúnak.

```php
class ThingsController extends AppController
{
    /** Index: sor / oldal */
    protected int $indexLimit = 10;

    /** Index: max sor / oldal (`?limit=` hack ellen) */
    protected int $indexMaxLimit = 100;

    public function index()
    {
        $items = $this->paginate($this->Things->find(), $this->indexPaginateOptions([
            'sortableFields' => ['id', 'name', 'created', 'modified', /* … */],
        ]));
        $this->set(compact('items'));
    }
}
```

| Tulajdonság | Alap | Szerep |
|-------------|------|--------|
| `$indexLimit` | `10` | Alapértelmezett sorok száma oldalanként |
| `$indexMaxLimit` | `100` | Felső korlát — URL `?limit=9999` sem mehet e fölé |

Helper: `AppController::indexPaginateOptions()` → `limit` + `maxLimit` a Cake Paginatornak.  
**Ne** hardkódolj `'limit' => 10`-et a `paginate()` hívásban.

```php
// Kerülendő
$query->orderBy(['Model.id' => 'ASC']);
```

### Oszloptípus osztályok (`th` / `td`)

| Osztály | Jelentés | Fix CSS szélesség | Forrás |
|---------|----------|-------------------|--------|
| `string` | szöveg | **nincs** (rugalmas) | — |
| `id` | ID (~7–8 jegy) | `4.75rem` | MyAdmin (minta: nincs fix) |
| `pos` | pozíció (max ~5 jegy + locale ezres) | `5.5rem` | MyAdmin; érték = **DB DEFAULT** (`UsesDatabaseColumnDefaultsTrait`) |
| `number` (pl. `.szam`, nem id/pos/count) | általános szám | `6.5rem` | MyAdmin (minta: csak `nowrap`) |
| `currency` / `netto` | pénz (összeg + pénznem) | `12rem` | MyAdmin; szuffixum: `currencySymbol()` → **Ft** |
| `count` | `*_count` | `5.5rem` | **MyPluginTemplate**; **0 / null → üres cella** (`LocaleNumberParser::formatCount`) |
| `boolean` / `logikai` / `visible` / `valid` | logikai | `7.5rem` | **MyPluginTemplate** (`.visible`/`.valid`) |
| `date` | dátum | `8.5rem` | **MyPluginTemplate** |
| `datetime` | dátum+idő | `10.5rem` | **MyPluginTemplate** |
| `time` | idő | `5rem` | **MyPluginTemplate** |
| `times` | időtartomány | `9rem` | **MyPluginTemplate** |
| `actions` | gombok | — | — |

Szabály: **szám / pénz / logikai / id / pos / count / dátum-idő** oszlopok fix szélességűek; **szöveges** (`string`) oszlopok szabadon nyúlhatnak.  
CSS: `webroot/css/style.css` — minta forrás: `MyPluginTemplate/assets/css/style.css` (ahol van `width`).

Számok **megjelenítése**: `LocaleNumberParser::format(..., decimals: $numberDecimals['integer'|'decimal'])`.  
`*_count` oszlopok: `LocaleNumberParser::formatCount(...)` — **0 vagy null esetén üres** (ne írjon `0`-t). Címkék: `__('English')`.

```php
<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($row->pos, decimals: $numberDecimals['integer'])) ?></td>
<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($row->city_count, decimals: $numberDecimals['integer'])) ?></td>
<td class="currency netto text-end"><span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::format($row->netto, decimals: $numberDecimals['decimal'])) ?></span> <?= h(\App\Utility\LocaleNumberParser::currencySymbol()) ?></td>
```

Pénznem megjelenítés — **`LocaleNumberParser::currencySymbol()`** (kötelező):

| Locale | Megjelenő szuffixum | Megjegyzés |
|--------|---------------------|------------|
| `hu_HU` (Admin) | **`Ft`** | Magyar szokás; **ne** ISO `HUF` |
| később EUR | pl. `€` | A helper `match` ágát bővítsd — ne hardkódolj templateben |

```php
<?= h(LocaleNumberParser::format($row->netto, decimals: 2)) ?>
<?= h(LocaleNumberParser::currencySymbol()) ?>
// → „12 345,67 Ft”
```

A pénznem **nem** `__()` string — locale-függő formázás, mint a számok. Címke továbbra is `__('Net')`.

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
- Delete → SweetAlert → **form** submit (`#delete-form-{id}`); ha `*_count > 0`, gomb disabled (nincs submit)
- Tooltippek az action gombokon (tiltott törlésnél: miért nem törölhető)

### Törlés — gyerekvédelem (kötelező)

| Réteg | Szabály |
|-------|---------|
| Model | `PreventsDeleteWithChildrenTrait` + `countRelatedChildren()`; `beforeDelete` → `$event->setResult(false)` + `_delete` error, ha van gyerek (CakePHP 5.2+: ne `return false`) |
| HABTM | `dependent => true` a join tisztításához (csak ha a törlés lefut) |
| hasMany szülő | `dependent => false` — gyerek meglétekor **tilos** a törlés |
| Controller | `deleteEntityOrFail()` / Flash a `_delete` üzenettel; `setCanDeleteFlag()` view/edit breadcrumbhez |
| Index UI | `*_count > 0` → disabled Delete + tooltip; különben `Form->create` `#delete-form-{id}` |
| Modal | `record.can_delete` / `data-can-delete` → `#btn-record-delete` / `#btn-linked-delete` |
| Breadcrumb | `#btn-delete` → `#delete-form-current` + Swal; disabled ha `!$canDelete` |

**Fontos:** a Cake `Form->postLink()` `id`/`class` opciói az **`<a>`** elemre mennek, nem a formra — ezért a JS `$('#delete-form-…').submit()` nem működött. Mindig valódi **`<form id="delete-form-…">`**.

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

$showIdColumn = true;       // id (#)
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
| `$showIdColumn` | `id` (`#`) |
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
Összefoglaló: [admin-oldal.md](admin-oldal.md) §7.

### View template eleje — config (kötelező, ha van kapcsolt rekord / tab)

```php
/**
 * Kapcsolt tábla sor dupla kattintás:
 * 'modal' | 'edit' | 'none'
 */
$rowDoubleClickAction = 'modal';

$config = [
    'rowDoubleClickAction' => $rowDoubleClickAction,
    'entityFieldLabels' => [
        'city' => [ /* recordGet mezőkulcs → __('Label') */ ],
        'parent' => [ /* … */ ],
        'sample' => [ /* … */ ],
    ],
];
// scriptBlock → MyAdmin.config; scriptBottom → pages/index
```

Asset: CSS `pages/index`; JS `pages/index`; element: `admin/modal_linked_record_view`.

### Fő rekord (bake-szerű adatlap)

1. Card: cím (`… details`), rövid súgó, bezáró gomb → index
2. Mezők: `<dl class="record-view-fields">` + `.record-view-row` / `dt` / `dd`
3. **belongsTo** szülő a fő `dl`-ben — **nem** külön tab; a név **félkövér link** (`.record-modal-link`) → AJAX modal
4. **belongsToMany** nevek a fő `dl`-ben (opcionális „City list” sor): **városonként** ugyanez a link
5. Lábléc: Edit + Back to list
6. Controller `view()`: `contain` + gyerekek **ASC** név szerint

### Kapcsolt rekord modal (AJAX)

Link / dupla klikk → `#modalLinkedRecordView` (`admin/modal_linked_record_view`).

| Modal gomb | Viselkedés |
|------------|------------|
| **Close** | Modal bezárás |
| **Edit** | Kapcsolt entitás `edit` URL |
| **View details** | Kapcsolt entitás `view` URL |
| Delete gomb | `MyAdmin.confirmDelete` → rejtett form submit; ha van gyerek (`*_count` / `can_delete=false`) → gomb **disabled** + tooltip |

Link attribútumok (`.record-modal-link`):

| Attribútum | Szerep |
|------------|--------|
| `data-id` | Rekord ID |
| `data-get-url` | JSON `recordGet` (vagy tábláról öröklődik) |
| `data-edit-url` / `data-view-url` / `data-delete-url` | Modal footer gombok |
| `data-delete-form-prefix` | Form id: `#delete-form-{prefix}-{id}` |
| `data-labels` | Kulcs a `entityFieldLabels` mapben |
| `data-title` | Modal cím prefix |

CSS: `a.record-modal-link` — `font-weight: 700` + link ikon (mint index `.category-link`).

### Kapcsolt gyerek táblák — tab sheet

Minden **hasMany** / **belongsToMany** külön Bootstrap **tab**-ban a fő card **alatt**.

| Szabály | Részlet |
|---------|---------|
| Element | `templates/element/admin/view_related_tabs.php` |
| Tab megjelenés | **Mindig** — üresnél is (`__('No related records.')`) |
| Tábla osztály | `.index-data-table.related-records-table` + `data-get-url` / `edit` / `view` / `delete` / `labels` / `title` / `delete-form-prefix` |
| **Name** oszlop | Félkövér `.record-modal-link` → ugyanaz a linked modal |
| Dupla klikk soron | `$rowDoubleClickAction` (`modal` / `edit` / `none`) — a tábla `data-*` URL-jeivel |
| Actions | legalább View + Edit; rejtett delete form a modal törléshez |
| belongsTo | **ne** legyen tab — fő `dl` + `.record-modal-link` |

```php
<?= $this->element('admin/view_related_tabs', [
    'relatedTabs' => [
        [
            'id' => 'cities',
            'title' => __('Cities'),
            'count' => $citiesCount,
            'table' => $citiesTableHtml, // üres → „Nincs adat.”
        ],
    ],
]); ?>
```

Több gyerek → több tab. Tábla HTML: `ob_start()` / `ob_get_clean()`.

### Asszociáció → megjelenés mapping

| Asszociáció típus | Hol jelenik meg |
|-------------------|-----------------|
| belongsTo | Fő `dl`, **félkövér** `.record-modal-link` → modal |
| hasMany | Saját tab + tábla; **name** link + dupla klikk |
| belongsToMany | Tab + opcionálisan fő `dl` „list” sor; **névenként** link → modal |
| hasOne | Általában fő `dl` + link, ha van kapcsolt entitás |

Delete után a controller **referer**-re irányít (`referer(['action' => 'index'])`), hogy a view oldalra vissza lehessen térni.

## Form (add / edit) UI

- Add és edit **ugyanaz** a `form.php`; controller `render('form')`
- Card: cím, Created/Modified (edit), bezáró gomb
- Bootstrap rács: label `col-md-2`, mező jobbra; címkék félkövérek (`style.css`)
- Lábléc: Save + Cancel; Save a breadcrumbben is (`form="form-horizontal"`)
- Kapcsolók: Visible / boolean switch-ek
- Dátumok: daterangepicker + inputmask, formátum `yyyy-mm-dd` / `hh:mm`
- **Számok (i18n):** inputmask a locale szerint — lásd alább
- **belongsTo Select2 / list** (pl. Parent): visible + pos/name sorrend — lásd alább
- **HABTM multiple Select2** (pl. Samples↔Cities mindkét irány): lásd alább
- Select2 „+”: lásd alább (csak ha az új rekord egyszerűen felvehető)
- **Name fókusz (kötelező):** lásd alább
- **Mentés hibakezelés:** lásd alább

### Name mező — azonnali fókusz (kötelező, **minden** Admin form)

Amikor **bármely** Admin `form.php` megjelenik (add **és** edit), a felhasználó **azonnal** gépelhessen — ne kelljen odamenni kattintással.

| Réteg | Szabály |
|-------|---------|
| Asset | Minden `form.php` töltse a `pages/form` JS-t (`scriptBottom`) — Select2 nélkül is |
| Template | `$this->Form->control('name', […, 'id' => 'name', 'autofocus' => true])` |
| JS | `form.js` → `focusPrimaryFormField()` a pluginok **után** (`#name`, különben első szöveges `.form-control`) |
| Select2 „+” modal | `shown.bs.modal` → `[data-select2-text="1"]` + `autofocus` a name inputon |

Ha a domain első mezője nem `name`, a **fő azonosító / címke** mezőt tedd `#name` helyett fókuszba (`id` + `autofocus`); a JS a `#name`-et keresi először.

### Mentés hibakezelés (kötelező)

A felhasználó **soha** ne lásson nyers PHP hibát (TypeError, stack trace) mentéskor.

| Kontextus | Viselkedés |
|-----------|------------|
| `add` / `edit` | `patchEntity` + `save` `try { … } catch (\Throwable)` — Flash: `__('The record could not be saved. Please try again.')` |
| `save` false (validáció) | Ugyanaz a Flash (és a mezőhibák a formon) |
| Select2 create | JSON `{ success: false, message }` — első entity error, vagy udvarias fallback; `Throwable` catch |

### `beforeMarshal` + DB oszlop DEFAULT-ok (PHP 8+)

Oszlop-defaultok (**`pos`**, **`visible`**, **`logikai`**, …) a **séma**ból jönnek — PHP-ban **ne** hardkódolj (`1000`, `true`).

Trait: `UsesDatabaseColumnDefaultsTrait` (Cities / Parents / Samples / CitiesSamples):

| Metódus | Szerep |
|---------|--------|
| `beforeMarshal` | üres `''` / `null` → unset, ha van schema DEFAULT → INSERT kihagyja → MySQL DEFAULT |
| `applySchemaDefaults($entity)` | add form UI: értékek a sémából (nem dirty → INSERT még mindig kihagyhatja) |

Cake a marshal adatot **`ArrayObject`**-ként adja. **Tilos:** `array_key_exists('pos', $data)` — használd `$data->getArrayCopy()`.

Controller add: `$this->newEntityWithSchemaDefaults($this->Table)` (Admin `AppController`).  
Select2 create: ne küldj `visible` / `pos` fallbackot.  
NOT NULL mező **DEFAULT nélkül** (pl. `sample_count`, `city_count`): továbbra is `0` a controllerben, amíg a séma nem kap DEFAULT-ot.

### Kapcsolt lista (belongsTo) — `setFormOptions` (kötelező minta)

A formon megjelenő **szülő / kategória** választólista (Select2 single vagy plain `<select>`):

| Szabály | Részlet |
|---------|---------|
| Szűrés | Csak `visible = true` rekordok |
| Rendezés | **`pos` ASC**, majd **`name` ASC** |
| Edit kivétel | A jelenleg hozzárendelt rekord (`parent_id` stb.) **akkor is** a listában legyen, ha `visible = false` (OR feltétel) |
| `pos` értéke | DB DEFAULT — PHP ne állítson 1000-et ([struktura.md](struktura.md)) |

```php
protected function setFormOptions(?Sample $sample = null): void
{
    $parentConditions = ['Parents.visible' => true];
    if ($sample !== null && $sample->parent_id) {
        $parentConditions = [
            'OR' => [
                ['Parents.visible' => true],
                ['Parents.id' => $sample->parent_id],
            ],
        ];
    }

    $parents = $this->Samples->Parents
        ->find('list', keyField: 'id', valueField: 'name')
        ->where($parentConditions)
        ->orderBy(['Parents.pos' => 'ASC', 'Parents.name' => 'ASC'])
        ->toArray();

    // …
    $this->set(compact('parents', /* … */));
}

// add() / edit(): $this->setFormOptions($sample);
```

Ugyanez a minta más belongsTo listáknál (ha a domain `pos` + `visible` mezőt használ).

### HABTM — multiple Select2 (kötelező, mindkét oldalon)

Ha belongsToMany van A↔B között, **mindkét** formon legyen multiple Select2 a másik oldalhoz (nem csak az egyik CRUD-on).

| Szabály | Részlet |
|---------|---------|
| Mező | `related._ids` (pl. `samples._ids`, `cities._ids`) |
| UI | `.js-example-basic-multiple` + `data-placeholder` |
| Lista | `setFormOptions()` → `find('list')` + `orderBy` name ASC |
| Mentés | `patchEntity(..., ['associated' => ['Related']])` |
| Üres kijelölés | ha nincs `_ids` a POST-ban → `['_ids' => []]` (összes join törlése) |
| Számláló | `*_count` = `count(array_filter($ids))` mentés előtt |
| Edit | `get($id, contain: ['Related'])` — előválasztás |
| Select2 „+” | csak ha az új kapcsolt entitás **kevés kötelező mezővel** felvihető (pl. City = név). Sample-szerűen sok kötelező mező → **nincs** „+” / tags |

```php
// CitiesController
protected function setFormOptions(): void
{
    $samples = $this->Cities->Samples
        ->find('list', keyField: 'id', valueField: 'name')
        ->orderBy(['Samples.name' => 'ASC'])
        ->toArray();
    $this->set(compact('samples'));
}

// add/edit save:
$data = $this->request->getData();
if (!isset($data['samples']['_ids'])) {
    $data['samples']['_ids'] = [];
}
$city = $this->Cities->patchEntity($city, $data, ['associated' => ['Samples']]);
$city->sample_count = count(array_filter((array)($data['samples']['_ids'] ?? [])));
```

Demó: Samples form → Cities (+ create); Cities form → Samples (csak választás).

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

1. Oldal betöltés (minden `#form-horizontal`): `focusPrimaryFormField()` a Select2/inputmask **után** — `#name` vagy első szöveges mező
2. Modal megnyitás → `relatedTarget` gombból target + create URL; form reset; fókusz `[data-select2-text="1"]`
3. Save → form mezők összegyűjtése → AJAX POST (CSRF)
4. Válasz: `{ success: true, id, text }` — hiba: `{ success: false, message }` → `MyAdmin.alertError`
5. Új `<option>` a selectbe; **immediate select**:
   - **single:** `val(id)`
   - **multiple:** meglévő kiválasztások megmaradnak, az új `id` **hozzáadódik**
6. Select2 `tags: true` + gépelős új tag ugyanarra az endpointokra megy (ha van `data-create-url` a kapcsolt „+” gombon)

#### Controller

- Külön action mezőnként / entitásonként (pl. `select2Create`, `select2CreateCity`), vagy közös helper.
- A Table-t **`fetchTable('Cities')`** / **`fetchTable('Parents')`** add át — ne `$this->Samples->Cities`-t: az **Association** (belongsToMany), nem `Table` (type error `select2CreateEntity`-nél).
- Válasz mindig: `id` + `text` (a listában látható címke); hiba esetén `message` (validáció első hibája vagy udvarias fallback).
- Extra modal mezők: `getData()`-ból az entity-be; a listában **ne** jelenjen meg az összes mező.
- `add` / `edit` mentés: try/catch + Flash (lásd „Mentés hibakezelés”).

#### Példa (demó)

| Mező | Form | Típus | Modal „+” | Endpoint |
|------|------|-------|-----------|----------|
| Parent | Samples | single | New parent | `select2Create` |
| Cities | Samples | multiple | New city | `select2CreateCity` |
| Samples | Cities | multiple | — (Sample sok kötelező mező → csak választás) | — |

Multiple Select2: `data-placeholder` a placeholder szöveghez; `tags` / gépelős create **csak** ha van `.btn-select2-add` create URL.

## Breadcrumb eszköztár

Element: `templates/element/admin/breadcrumb.php`  
Gombok kontextus szerint: Back, New, Save, Edit, View details, Delete.

## CakePHP 5 figyelmeztetések

- `find('list', keyField: 'id', valueField: 'name')` — **named argument**, ne options tömb!
- Form Select2 / list (pl. Parent): csak `visible = true`; rendezés: **`pos` ASC, majd `name` ASC**. Editnél a jelenlegi kiválasztott szülő akkor is a listában marad, ha `visible = false`.
- Paginator: **ne** `loadComponent('Paginator')`; használd `$this->paginate($query)`
- Entity név: `Parent` tilos → `ParentRecord`
- `beforeMarshal` `$data`: **ArrayObject** — ne `array_key_exists($k, $data)`; használd `getArrayCopy()` (PHP 8+)
- `beforeDelete` tiltás: `$event->stopPropagation()` + `$event->setResult(false)` — ne `return false` (CakePHP ≥5.2)