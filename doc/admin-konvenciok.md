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

**Nem** kerül a layoutba: Select2, Trumbowyg, Tempus Dominus, inputmask, page CSS/JS.

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
| **form** (add/edit) | tempus-dominus, select2, select2-bootstrap5, `pages/form` | popper, tempus-dominus, inputmask, select2, `pages/form` |
| **view** | `pages/index` (record-view + related tabs) | `pages/index` JS + `MyAdmin.config` (`rowDoubleClickAction`, `entityFieldLabels`) + `modal_linked_record_view` |
| WYSIWYG / Prism | **csak** ha van `.editor` mező a formon | trumbowyg + pluginek |

Trumbowyg / WYSIWYG **csak** akkor kerüljön a form templatebe, ha van `.editor` mező.

## JavaScript API — `window.MyAdmin`

Layout: `webroot/js/app.js`

```js
MyAdmin.config = { /* oldal tölti fel */ };
MyAdmin.initTooltips();
MyAdmin.initScrollTop(); // layout: #btn-scroll-top (görgetés után látszik)
MyAdmin.confirmDelete({ onConfirm: fn });
MyAdmin.alert({ icon: 'error', text: '…' });
MyAdmin.alertError('…');
MyAdmin.flashSwal({ icon, title, html }); // Flash SWAL sorban
```

### Flash üzenetek (JeffAdmin5 mintájára)

| Típus | Element / API | Viselkedés |
|-------|---------------|------------|
| **Notify** (alap) | `$this->Flash->success($msg)` → `flash/success.php` | Simple Notify toast; **több** üzenet egyszerre (bottom left) |
| **SWAL** | `$this->flashSwal('success', $msg)` vagy `['element' => 'flash/success_swal']` | SweetAlert2 modal; egyszerre **egy** (több Flash sorban) |
| Legacy | `['element' => 'flash_/success']` | jquery-toastmessage (asset: `plugins/jquery-toastmessage/`) |

**Hol:** Admin layout **és** CakeDC auth (`login` layout) — `AppView::usesFlashToast()`; auth: `Flash->render('auth')` + default a layout végén. Spec: [users-auth.md](users-auth.md).

Assetek: `simple-notify` 1.0.6, `sweetalert2` 11.x (legújabb), layout végén `<script>` + `admin/script_flash`.

### SweetAlert — kötelező (tilos a `window.alert`)

Az Admin UI-n **minden** felhasználói dialógus / megerősítés **SweetAlert2** legyen.  
Server-side flash alapból **Notify** toast; modal flashhez `flashSwal()`.  
**Ne** használd: `window.alert()`, `window.confirm()`, `window.prompt()`.

| API (`webroot/js/app.js`) | Mikor |
|---------------------------|--------|
| `MyAdmin.swal({…})` | Alacsony szintű Swal; Bootstrap modal FocusTrap pause + z-index 20000 |
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés megerősítés (index sor, breadcrumb, record/linked modal, …) |
| `MyAdmin.alert({ icon, title, text })` | Általános üzenet (`icon`: `error` / `success` / `info` / `warning`) |
| `MyAdmin.alertError(text)` | Rövid hiba (Select2 mentés sikertelen, hiányzó URL, tiltott törlés, …) |
| `MyAdmin.flashSwal({ icon, title, html })` | Flash SWAL (queue) |

**Kinézet (kötelező CSS — `webroot/css/style.css`):**

| Szabály | Érték |
|---------|--------|
| Z-index | `.swal2-container` → `20000` (Bootstrap modal / FocusTrap fölött) |
| Árnyék | `.swal2-popup` → látható `box-shadow` (mint a linked modal / Tempus panel): `0 0.75rem 2rem rgba(0,0,0,.28), 0 0.25rem 0.75rem rgba(0,0,0,.18)` |

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

### Vissza a tetejére (kötelező)

Layout (`admin.php`): `#btn-scroll-top` — jobb alsó sarok, `fa-angle-up`.  
Csak akkor látszik (`is-visible`), ha a scroll > ~200px; kattintásra sima görgetés az oldal tetejére (`MyAdmin.initScrollTop`).

## Lista (index) UI — kötelező minden Admin CRUD-nál

Minden index lista **ugyanazt** a mintát kövesse ([uj-projekt.md](uj-projekt.md) + ez a szakasz).  
Referencia-implementáció (ha van a projektben): bármely teljes CRUD `index.php` + `webroot/js/pages/index.js`.

### Kötelező elemek

1. Külső wrapper: `<div class="row mt-3"><div class="col-12 p-2 pt-0">` (térköz a breadcrumb alatt)
2. Card fejléc: cím, súgó („Dupla kattintással…”), `admin/table_search`, `admin/index_pagination`
3. Tábla: `.table.index-data-table`
4. Sor: `id="record-{id}"` `data-id="{id}"`; ha `$lastVisitedId` egyezik → `class="last-visited"`
5. Actions: View / Edit / Delete (outline + HTML tooltip). Ha `*_count > 0`: Delete **disabled** + tooltip a tilalom okáról. Egyébként rejtett `#delete-form-{id}` (**`Form->create`**, nem `postLink`)
6. Lábléc: **`admin/index_footer`** (bal: `1–100 / 266 records | 1. page / 3`; jobb: lapozó) — **ne** másold be inline a summary-t
7. Modal: `#modalRecordView` (+ linked modal, ha van kapcsolt entitás link) — Delete gomb `can_delete` szerint enable/disable
8. Page asset: `pages/index` CSS + `pages/index` JS + `MyAdmin.config` (legalább `recordGetUrl`, `editUrl`, `viewUrl`, `recordFieldLabels`)
9. Controller: `recordGet` JSON (`can_delete` flag is) a modalhoz (kapcsolt névlisták: utolsó 20 modified → ABC); `setLastVisitedForIndex('Alias')`

### Index card footer (kötelező)

```php
<div class="card-footer">
    <?= $this->element('admin/index_footer') ?>
</div>
```

Az element balra az **`admin/index_counter`**-t írja (bake `Paginator::counter` + `__()`), jobbra az `admin/index_pagination`-t.  
Lapozó részletek: lásd **Lapozó (paginator)** alább. Fejlécben / keresőnél: csak `admin/index_pagination`.

### Lapozó (paginator) — kötelező

Agent rule: `.cursor/rules/admin-paginator.mdc`.

**Sorrend:** « → ‹ → oldalszámok → › → » (FA: `fa-angle-double-left` / `fa-angle-left` / `fa-angle-right` / `fa-angle-double-right`).

| Gomb | Cake API | Disabled mikor |
|------|----------|----------------|
| First («) | `Paginator->first(__('First'))` — ikon a sablonban; label = `title`/`aria-label` | `!$this->Paginator->hasPrev()` (manuális disabled LI) |
| Previous (‹) | `Paginator->prev(__('Previous'))` | `prevDisabled` sablon |
| Numbers | `Paginator->numbers(['modulus' => 3])` | aktuális = `active` |
| Next (›) | `Paginator->next(__('Next'))` | `nextDisabled` sablon |
| Last (») | `Paginator->last(__('Last'))` | `!$this->Paginator->hasNext()` (manuális disabled LI) |

Msgid (csak accessibility / tooltip): `First` / `Previous` / `Next` / `Last` / `Pagination`.  
**Lapozáskor** (`?page=` a queryben): `clearLastVisited($model)` — az utoljára megtekintett kiemelés törlődik.

### Setups (típusos beállítások)

EAV modul — teljes specek: **[setups.md](setups.md)**. Rule: `.cursor/rules/setups-eav.mdc`.

| Szabály | Érték |
|---------|--------|
| Tárolás | `setups.value` TEXT + `type` + `country_id` (ne külön oszlop típusonként) |
| Slug | csak `a-z0-9` + **`_`**; unique **országonként** `(country_id, slug)` |
| Ország | Index = working country (`AdminCountry`, default HU); új = minden látható ország |
| `edit_by` | `superuser` \| `admin` \| `president` — [setups.md](setups.md) |
| Form | type → widget (`setups_form.js`); `secret` = titkosított adat + encrypt |
| Olvasás | **bárhol:** `Setup::get('slug', $default)` |

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
CSS: `style.css` (zöld kiemelés). A `index.js` modalnál is állítja a class-t; **betöltéskor** a last-visited sorhoz **görget** (viewport teteje ≈ header + breadcrumb + ~mt-3).

### Index lista állapot (sort / page / keresés) — session + URL

| Kulcs | Tartalom |
|-------|----------|
| `Admin.indexState[Alias]` | `sort`, `direction`, `page`, `q`, opcionális `limit` |

- **URL a forrás** (könyvjelzőzhető): sort / direction / **page** (1 is!) / q / limit — `App\View\Helper\PaginatorHelper` (Cake alapból elhagyná a `page=1`-et).
- Üres index URL → redirect a mentett kanonikus URL-re (`applyIndexListState` Response).
- Mentés / „Back to list”: `redirectToIndexList('Alias')` / `$indexListUrl`.
- Kereső param: `q` (`AdminSearch::queryParam()`).
- **Keresés indítás** (form: csak `q`): mindig **1. oldal** + kanonikus redirect.
- **Lapozás / sort** (ha a `page` változik): `clearLastVisited`.
- **Keresés törlése** (`?clear_search=1`): last-visited oldal kiszámítása + **redirect** kanonikus URL-re; scroll a `.last-visited` sorra.

### Keresés (index + globális)

**Kötelező minden új projektben / CRUD-nál** — greenfield playbook: [uj-projekt.md](uj-projekt.md) §2.8; agent rule: `.cursor/rules/admin-kereses-index-allapot.mdc`.

Config: **`config/admin_search.php`** → `Configure::read('AdminSearch')` (bootstrap betölti).

| Kulcs | Szerep |
|-------|--------|
| `queryParam` | URL param (alap: `q`) |
| `globalPageLimit` | `/admin/search` oldalméret (alap: 20) |
| `globalLimitPerModel` | max találat / model összevonás előtt (200) |
| `globalMaxResults` | összevont lista felső korlát (1000) |
| `models[Alias].label` | msgid a tábla névhez |
| `models[Alias].controller` | Admin controller |
| `models[Alias].titleField` | találat címsora |
| `models[Alias].labelsKey` | `entityFieldLabels` kulcs (Search modal) |
| `models[Alias].fields` | szöveges oszlopok (index + global) |

| Hol | Mit keres |
|-----|-----------|
| Index card (`admin/table_search`) | Csak az adott Table alias `fields` listája (saját oszlopok) |
| Header (`admin/header_search`) | Összes model összes `fields` → `Admin\SearchController` |

**`/admin/search` UI:** Google-szerű sor — cite (tábla · #id); kék cím → AJAX modal (összes mező + **Table** sor `data-source-table`); szem → view; ceruza → edit; lapozás fent (`index_pagination`) + lent (`index_footer`). CSS: `pages/search.css`.

Új CRUD / új projekt: sorold fel a modelt + **minden szöveges** mezőt a configban (lásd [uj-projekt.md](uj-projekt.md)).  
Helper: `App\Utility\AdminSearch`; controller: `applyIndexSearch($query, $table)`.

UI: mindkét kereső mellett nagyító (`__('Start search')` + hol keres) és **törlés** gomb (`__('Clear search')`) — HTML tooltip; indexen `?clear_search=1`; **üres keresésnél a törlés gomb nem jelenik meg**.

**Keresés után:** ha a mezőben van szöveg, a fókusz a keresőmezőn marad, kurzor a **szöveg végén** (`MyAdmin.focusActiveSearchField` — index: `#table-search-input`; globális oldal: `.search-page-input`).

**Jövőbeli (nem kötelező még):** belongsTo szöveges mezők az index keresőben (pl. `Continents.name` dotted `fields`) — csak ha a felhasználó kéri.

### Modal — kapcsolt (HABTM / hasMany) listák sorrendje

A `#modalRecordView` / linked modal mezőiben megjelenő kapcsolt nevek (pl. „City list” / „Sample list”):

1. **Kiválasztás:** az utoljára módosított max. **20** gyerek (`modified DESC` + `limit 20`)
2. **Megjelenítés:** ABC **name ASC**
3. **Link:** `.record-modal-link` → második modal

Helper (`Admin\AppController`): `$modalRelatedLimit`, `containRelatedForModal($alias)`, `relatedNameLinksForModal($entities)`.

```php
// recordGet / parentGet
contain: [
    'Cities' => $this->containRelatedForModal('Cities'),
    // vagy 'Samples' => $this->containRelatedForModal('Samples'),
]
// …
'cities' => $this->relatedNameLinksForModal($sample->cities ?? []),
```

Index config: `relatedLinkFields` + `entityFieldLabels` + `admin/modal_linked_record_view`.

A **view** oldal kapcsolt tab / fő `dl` továbbra is lehet teljes lista (ABC ASC) — a 20-as limit a **modal JSON**-ra vonatkozik.

Részletek: [crud-utmutato.md](crud-utmutato.md) → `recordGet`.

### Rendezés (sort) — URL-ből + session

- Az `index()` **ne** állítson be előre `orderBy([...])`-t a query-n (kivéve ritka default `paginate` `order`, pl. Countries).
- A rendezés a Paginator URL paraméterekből jön: `?sort=field&direction=asc|desc` — és sessionben megmarad.
- A `th`-okban `$this->Paginator->sort('mezo', 'Címke')`.
- A `paginate()` hívásban add meg a `sortableFields` listát (különösen associált mezőknél, pl. `Parents.name`).

```php
class ThingsController extends AppController
{
    /** Index: sor / oldal */
    protected int $indexLimit = 100;

    /** Index: max sor / oldal (`?limit=` hack ellen) */
    protected int $indexMaxLimit = 1000;

    public function index()
    {
        $this->applyIndexListState('Things');
        $paginateOptions = $this->indexPaginateOptions([
            'sortableFields' => ['id', 'name', 'created', 'modified', /* … */],
        ]);
        $query = $this->applyIndexSearch($this->Things->find(), $this->Things);
        $this->resolveIndexPageForLastVisited('Things', $query, $paginateOptions);
        $items = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Things');
        $this->set(compact('items'));
    }
}
```

| Tulajdonság | Alap | Szerep |
|-------------|------|--------|
| `$indexLimit` | `100` | Alapértelmezett sorok száma oldalanként |
| `$indexMaxLimit` | `1000` | Felső korlát — URL `?limit=9999` sem mehet e fölé |

Helper: `AppController::indexPaginateOptions()` → `limit` + `maxLimit` a Cake Paginatornak.  
**Ne** hardkódolj `'limit' => 100`-et a `paginate()` hívásban.

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
| `currency` / `netto` | pénz (összeg + pénznem) | `12rem` | MyAdmin; **`formatCurrency()`** (HUF, ICU pozíció) |
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
<td class="currency netto text-end"><span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::formatCurrency($row->netto, decimals: $numberDecimals['decimal'])) ?></span></td>
```

Pénz megjelenítés — **`LocaleNumberParser::formatCurrency()`** (kötelező; mindig **HUF**):

| Locale | Példa | Megjegyzés |
|--------|-------|------------|
| `hu_HU` | `12 345,67 Ft` | Magyar szokás (`Ft`, utótag) |
| `en_*` | `HUF 12,345.67` | ISO kód, **előtag** |
| `de_DE` / `fr_FR` / `sk_SK` | `12.345,67 HUF` / … | ISO kód, utótag (+ szóköz) |

```php
<?= h(LocaleNumberParser::formatCurrency($row->netto, decimals: 2)) ?>
// hu → „12 345,67 Ft”; en → „HUF 12,345.67”
```

Ne rakd össze kézzel `format()` + `currencySymbol()` — a pozíció locale-függő. A pénznem **nem** `__()` string. Címke: `__('Net')`. Más valuta: `$currency` param (később).

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
| Model | `PreventsDeleteWithChildrenTrait` + `relatedChildrenCountField()` (CounterCache `*_count`); `beforeDelete` → `$event->setResult(false)` + `_delete` error (CakePHP 5.2+: ne `return false`) |
| Számláló | **`CounterCache` behavior** tartja a `*_count` mezőt — **ne** írj manuális `COUNT()` / controller `*_count = count(_ids)` kódot |
| HABTM | through Table-en CounterCache; `belongsToMany`: `dependent => true` + **`cascadeCallbacks => true`** |
| hasMany szülő | gyerek Table-en CounterCache (`belongsTo` szülő); szülőn `dependent => false` — gyerek meglétekor **tilos** a törlés |
| Controller | `deleteEntityOrFail()` / Flash; `setCanDeleteFlag()`; új rekordnál `*_count = 0` csak ha NOT NULL + nincs DB DEFAULT |
| Index UI | `*_count > 0` → `btn-outline-secondary disabled` + tooltip; különben danger + `Form->create` `#delete-form-{id}` |
| Modal | `record.can_delete` → `#btn-record-delete` / `#btn-linked-delete`: törölhető = `btn-danger`; nem = `btn-secondary disabled` + tooltip |
| Breadcrumb | `#btn-delete` → `#delete-form-current` + Swal; nem törölhető = `btn-secondary disabled` |

**Fontos:** a Cake `Form->postLink()` `id`/`class` opciói az **`<a>`** elemre mennek, nem a formra — ezért a JS `$('#delete-form-…').submit()` nem működött. Mindig valódi **`<form id="delete-form-…">`**.

### CounterCache — `*_count` mezők (kötelező)

A megjelenített / törléshez használt `city_count`, `sample_count` stb. **CounterCache**-ből jön. Manuális `find()->count()` a `countRelatedChildren`-ben **tilos** (duplikált logika, elcsúszhat).

| Kapcsolat | Hol a CounterCache? | Frissülő mező |
|-----------|---------------------|---------------|
| hasMany / belongsTo (pl. Sample → Parent) | **Gyerek** Table (`SamplesTable`) | `Parents.sample_count` |
| belongsToMany / HABTM | **Through** Table (`CitiesSamplesTable`) | mindkét oldal: `Samples.city_count`, `Cities.sample_count` |

```php
// SamplesTable — hasMany számláló a szülőn
$this->belongsTo('Parents', […]);
$this->addBehavior('CounterCache', [
    'Parents' => ['sample_count'],
]);
$this->belongsToMany('Cities', [
    'through' => 'CitiesSamples',
    'dependent' => true,
    'cascadeCallbacks' => true, // kötelező: join törlés → CounterCache
]);

// CitiesSamplesTable — HABTM számlálók
$this->addBehavior('CounterCache', [
    'Samples' => ['city_count'],
    'Cities' => ['sample_count'],
]);

// PreventsDeleteWithChildrenTrait
protected function relatedChildrenCountField(): string
{
    return 'city_count'; // vagy sample_count
}
```

`countRelatedChildren()` a traitben a CounterCache oszlopot olvassa (lehetőleg friss DB értékkel, ha van PK).  
UI / `can_delete` / index Delete: ugyanez a mező (`*_count > 0`).

Újraszámolás (import / elcsúszás után):

```bash
bin/cake rebuild_counter_caches
```

Ha a számláló elcsúszik: futtasd a fenti parancsot — ne írj vissza élő JOIN `COUNT()`-ot a modelbe.

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
LocaleNumberParser::formatCurrency($row->netto, decimals: $numberDecimals['decimal']);
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
- Dátum megjelenítés: `LocaleDateParser::format($row->created, 'datetime_short')` (és `date` / `time_short` a mezőtípus szerint) — **ne** `->format('Y.m.d. H:i')`.

Ez **csak** az index táblára vonatkozik; a view / modal ugyanazokat a locale formázókat használja.

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
5. Lábléc: csak **Edit**, az **adatoszlop** (`dd`) alatt: `.record-view-footer-actions` (`padding-left: calc(9rem + 1rem)` — ugyanaz, mint a `dt` + gap); Back to list a breadcrumbben
   - Form Save továbbra is `offset-md-2` (form label `col-md-2`)
6. Controller `view()`: `contain` + gyerekek **ASC** név szerint

### Kapcsolt rekord modal (AJAX)

Link / dupla klikk → `#modalLinkedRecordView` (`admin/modal_linked_record_view`).

| Modal gomb | Viselkedés |
|------------|------------|
| **Close** | Modal bezárás |
| **Edit** | Kapcsolt entitás `edit` URL |
| **View details** | Kapcsolt entitás `view` URL |
| Delete gomb | `MyAdmin.confirmDelete` → rejtett form submit; ha van gyerek → **`btn-secondary` / outline-secondary + disabled** + tooltip; Swal z-index > Bootstrap modal |

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
- Lábléc: Save + Cancel; Save a breadcrumbben is (`form="form-horizontal"`); gombok: `col-12 col-md-10 col-xxl-9 offset-md-2` (mezősorral egy vonalban)
- **Mezőhiba:** a beviteli mező **alatt**, piros félkövér (`.error-message`); Admin Form: `errorClass=is-invalid`, `inputContainerError={{content}}{{error}}` — `AppView` + `style.css`
  - Egyszerű `Form->control`: a hiba automatikusan a mező alatt
  - **input-group** (Tempus), **select2-with-add**, **checkbox**: `'error' => false` a controlon, majd a wrapper **után** `<?= $this->element('admin/field_error', ['field' => '…']) ?>` — így nem a naptár ikon / „+” gomb mellé kerül
- Kapcsolók: Visible / boolean switch-ek
- **`visible` + `pos` blokk (kötelező, ha mindkettő van):** a többi mező **után** (pl. `logikai` után is), elválasztó csak a `visible` fölött — markup:
  ```html
  <div class="row"><div class="col-12 col-xxl-11"><hr class="my-4"></div></div>
  ```
  (mezősor szélessége: label `col-md-2` + mező `col-xxl-9` ≈ `col-xxl-11`; **ne** teljes szélességű bare `<hr>`). Sorrend: **visible → pos**.
- Dátumok: **Tempus Dominus 6** (JeffAdmin5), formátum `yyyy.MM.dd.` / `HH:mm:ss` / `yyyy.MM.dd HH:mm:ss`; mentés: `LocaleDateParser`
- **Számok (i18n):** inputmask a locale szerint — lásd alább
- **belongsTo Select2 / list** (pl. Parent): visible + pos/name sorrend — lásd alább
- **HABTM multiple Select2** (pl. Samples↔Cities mindkét irány): lásd alább
- Select2 „+”: lásd alább (csak ha az új rekord egyszerűen felvehető)
- **Name fókusz (kötelező):** lásd alább
- Mentés hibakezelés: lásd alább
- **Mező validációs hiba:** a mező **alatt**, `.error-message` — piros félkövér (`AppView` Admin Form + `style.css`)

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
| Számláló | CounterCache tartja; **ne** `*_count = count(_ids)` a controllerben |
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
// sample_count / city_count → CounterCache (CitiesSamplesTable), ne állítsd kézzel
```

Demó: Samples form → Cities (+ create); Cities form → Samples (csak választás).  
Parent (hasMany) modal / `parentGet` / Parents `recordGet`: ugyanez a **Sample list** `[{id,name}]` + `relatedLinkFields.samples` minta.

### Számmezők — locale / i18n (kötelező)

Admin locale: `App.adminLocale` (éles `hu_HU` → tizedes **`,`**, ezres **szóköz**, pl. `1 234,56`).  
**Ne** hardkódold az angol inputmaskot (`radixPoint: '.'`, `groupSeparator: ','`) — abból mentéskor számjegyvesztés lesz.

1. Form config: `'numberFormat' => LocaleNumberParser::jsConfig()`
2. Mezők: **`LocaleNumberParser::formIntegerOptions($entity->field)`** / **`formDecimalOptions($entity->field, 2)`** — class, locale value, placeholder, `type=text`. (Legacy: `.js-input-integer` / `.js-input-decimal` + kézi `format()`.)
3. **Ne** állíts `inputmode="decimal"|"numeric"` a templateben. A `form.js` `inputmode: 'text'`-et kényszerít; inputmask: ezres csoport (`groupSize: 3`), tizedes a `numberFormat.decimal` szerint.
4. **Minden** Admin form, ahol van számmező: `/plugins/inputmask/jquery.inputmask.min` + `'numberFormat' => LocaleNumberParser::jsConfig()`.
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