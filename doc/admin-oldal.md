# Admin oldal — teljes kép (kinézet + működés)

Ez a fájl a **egységes célkép**: hogyan nézzen ki és hogyan működjön egy Admin CRUD felület.  
Részletszabályok: [admin-konvenciok.md](admin-konvenciok.md). Greenfield: [uj-projekt.md](uj-projekt.md). Új modul: [crud-utmutato.md](crud-utmutato.md).

Ha az agent / fejlesztő „hogyan kell kinéznie?” kérdést kap — **ezt olvasd először**, majd a hivatkozott fájlokat.

---

## 1. Mi az Admin?

| Tulajdonság | Érték |
|-------------|--------|
| URL | `/admin/...` (nincs `{lang}` a pathban) |
| Framework | CakePHP 5.4+, Bootstrap 5 + Pike Admin kinézet |
| Nyelv | UI msgid **angol** (`__('…')`); megjelenés mindig **magyar** (`hu_HU`) |
| Dialógus | **csak** SweetAlert2 — tilos `window.alert` / `confirm` / `prompt` |
| Layout | közös váz; oldalspecifikus CSS/JS a templateben |

Minden üzleti modulnak **ugyanaz** a viselkedés-mintája: lista → form → adatlap → törlés. Nincs „egyszerűsített” index modal/sort/típusoszlopok nélkül.

---

## 2. Közös váz (minden Admin oldal)

```
┌─────────────────────────────────────────────────────────────┐
│ header (profil, értesítések… — NYELVVÁLASZTÓ NÉLKÜL)       │
├──────────┬──────────────────────────────────────────────────┤
│ sidebar  │ breadcrumb: cím + kontextus gombok               │
│ menü     │   (Back / New / Save / Edit / View / Delete)     │
│          ├──────────────────────────────────────────────────┤
│          │ Flash üzenetek                                   │
│          │                                                  │
│          │ ═══ oldal tartalom (index | form | view) ═══     │
│          │                                                  │
│          ├──────────────────────────────────────────────────┤
│          │ footer                                           │
└──────────┴──────────────────────────────────────────────────┘
```

**Breadcrumb gombok** (element: `admin/breadcrumb.php`) — oldal típusa szerint:

| Oldal | Tipikus gombok |
|-------|----------------|
| index | New |
| form (add) | Save, Cancel/Back |
| form (edit) | Save, View, Delete, Back |
| view | Edit, Delete, Back |

Layout assetek: csak közös (bootstrap, fontawesome, `style.css`, sweetalert2, `app.js`, …).  
Select2 / daterangepicker / inputmask / `pages/*` → **template** `block`.

---

## 3. Három oldaltípus — áttekintés

| Oldal | Fájl | Cél | Kötelező asset |
|-------|------|-----|----------------|
| **Lista** | `index.php` | kereshető/rendezhető tábla, gyorsnézet, CRUD akciók | `pages/index` CSS+JS + `MyAdmin.config` |
| **Űrlap** | `form.php` (add=edit) | mentés locale szám/dátummal; Select2 „+” | `pages/form` + select2/date/inputmask |
| **Adatlap** | `view.php` | bake-szerű `dl` + gyerek **tabok** | `pages/index` CSS |

Controller: `add`/`edit` → `render('form')`; `recordGet` JSON a lista modalhoz; opcionális `select2Create…`.

---

## 4. Lista (index) — kinézet

### 4.1 Card szerkezet

Külső wrapper (a tartalom tetején):

```php
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card …">
```

(`mt-3` = térköz a breadcrumb / Flash alatt; `pt-0` a col-on — ne legyen dupla felső padding.)

1. **Fejléc:** ikon + modulcím (`__('English')`), súgó a dupla kattintásról (ha nem `none`), Search mező (UI megvan; bekötés később), lapozó felül
2. **Tábla:** `.table.index-data-table` — típusoszlop osztályokkal
3. **Sor:** `id="record-{id}"` + `data-id="{id}"`
4. **Actions oszlop:** View details → Edit → Delete (outline gombok, HTML tooltip) + rejtett `#delete-form-{id}`
5. **Lábléc:** „Showing X–Y of Z” + `admin/index_pagination`
6. **Modalok:** `#modalRecordView` (+ linked modal, ha van szülő/kapcsolt link)

### 4.2 Template eleji config (minden indexen)

```php
$rowDoubleClickAction = 'modal'; // modal | edit | none
$numberDecimals = ['integer' => 0, 'decimal' => 2];
$showCountColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;
// → $indexColspan számítás a thead/tbody/empty row-hoz
```

Ezeket tedd a `MyAdmin.config`-ba is (`rowDoubleClickAction`, URL-ek, `recordFieldLabels`).

### 4.3 Oszloptípusok és fix szélességek

**Szabály:** szám, pénz, logikai, id, pos, count, dátum/idő → **fix**; szöveg (`string`) → **rugalmas**.

| Osztály(ok) | Tartalom | Szélesség | Megjegyzés |
|-------------|----------|-----------|------------|
| `string` | név, cím, … | rugalmas | kitölti a maradékot |
| `number id` | ID | `4.75rem` | ~7–8 jegy |
| `number pos` | pozíció | `5.5rem` | max ~5 jegy + locale ezres |
| `number` (+ pl. `.szam`) | általános szám | `6.5rem` | ne `id`/`pos`/`count` |
| `number count` | `*_count` | `5.5rem` | minta |
| `currency` (+ pl. `.netto`) | összeg + pénznem | `12rem` | összeg: `.currency-amount` (félkövér); bőven elfér nagyobb összeg is |
| `boolean` (+ `.logikai` / `.visible` / `.valid`) | FA pipa / X | `7.5rem` | minta `.visible`/`.valid` |
| `date` | dátum | `8.5rem` | |
| `datetime` | dátum+idő | `10.5rem` | created/modified közös oszlop is |
| `time` | idő | `5rem` | |
| `times` | időtartomány | `9rem` | |
| `actions` | gombok | — | **nincs** sort link |

CSS: `webroot/css/style.css`. Sort link `id`/`pos`/`number`/`currency`/`count`/`boolean` fejlécben: `width: 100%` (ne nyíljon szét a `max-content` miatt).

**Számkiírás** (index / view / modal JSON):

```php
LocaleNumberParser::format($value, decimals: $numberDecimals['integer']); // vagy 'decimal'
```

Admin `hu_HU` → pl. `1 234` / `1 234,56`. Címkék továbbra is `__('English')`.

### 4.4 Created + Modified

- Mindkettő be: **egy** oszlop, `th.datetime.created.modified`, két sort link egymás alatt; cellában két sor `<br>`-rel
- Csak az egyik: egy oszlop, egy mező
- Egyik sem: nincs timestamp oszlop  
`th`-n **tilos** `display:flex` (maradjon `table-cell`).

### 4.5 Rendezés

- URL: `?sort=mezo&direction=asc|desc`
- Index query-n **ne** legyen előre `orderBy`
- `paginate(..., ['sortableFields' => [...]])` — associált mezők is (pl. `Parents.name`)

---

## 5. Lista (index) — működés

| Interakció | Viselkedés |
|------------|------------|
| Sor **dupla katt** | `$rowDoubleClickAction`: `modal` → AJAX `recordGet` + `#modalRecordView`; `edit` → edit URL; `none` → semmi |
| View details gomb | Ugyanaz a modal (vagy view URL — a mintában modal/gyorsnézet + külön view oldal) |
| Edit gomb | `/admin/.../edit/{id}` |
| Delete gomb | `MyAdmin.confirmDelete` → `#delete-form-{id}` submit |
| `.category-link` | Kapcsolt rekord linked modal (`categoryGetUrl`) |
| Fejléc sort | Paginator link → újratöltés új sorrenddel |
| Modal kapcsolt nevek (HABTM/hasMany) | **ABC ASC** (`contain` + `orderBy` name) |
| Üres lista | Egy sor `colspan="$indexColspan"` + „nincs találat” szöveg |

Modal mezősorrend / címkék: `MyAdmin.config.recordFieldLabels`.  
Edit gomb a modalban a View details **előtt** (UX konvenció).

---

## 6. Űrlap (form) — kinézet + működés

### Kinézet

- Egy card: cím (New / Edit …), editnél Created/Modified a fejlécben, bezáró → index
- Bootstrap rács: label `col-md-2` **félkövér**, mező jobbra
- Boolean: switch
- Lábléc: Save + Cancel; Save a breadcrumbben is (`form="form-horizontal"`)
- Select2 mező mellett **„+”** gomb (single **és** multiple), ha új kapcsolt rekord felvehető

### Számok és dátumok

| Réteg | Szabály |
|-------|---------|
| Megjelenítés | `LocaleNumberParser::format()`; hu: `1 234,56` |
| Input | `.js-input-decimal` / `.js-input-integer` + `MyAdmin.config.numberFormat` (= `LocaleNumberParser::jsConfig()`) |
| Dátum UI | daterangepicker + inputmask (`yyyy-mm-dd`, `hh:mm`) |
| Mentés előtt | Middleware: locale szám → `1234.56`; dátum → SQL formátum ([middleware.md](middleware.md)) |

**Ne** hardkódold az angol inputmaskot (`radixPoint: '.'`) hu Adminban.

### Select2 „+”

1. Gomb: `data-select2-target`, `data-create-url`, saját modal
2. Mentés → AJAX POST → `{ success, id, text }`
3. Új option + azonnali kiválasztás (multiple: **hozzáad** a meglévőkhöz)
4. Controller: `fetchTable('Alias')` — **ne** Association objektumot adj Table helyett

Részletek: [admin-konvenciok.md](admin-konvenciok.md) → Select2.

---

## 7. Adatlap (view) — kinézet + működés

```
┌─ Card: „… details” ─────────────────────────┐
│ dl.record-view-fields                       │
│   dt / dd sorok (bake-szerű)                │
│   belongsTo név a fő listában (NEM tab)     │
│ Edit + Back                                 │
└─────────────────────────────────────────────┘
┌─ Card: tab sheet (ha van gyerek) ───────────┐
│ [Cities (n)] [Más hasMany…]                 │
│ index-szerű tábla VAGY „No related records.”│
│ Üres asszociációnál a TAB IS LÁTSZIK        │
└─────────────────────────────────────────────┘
```

| Asszociáció | Hol |
|-------------|-----|
| belongsTo | Fő `dl` |
| hasMany / belongsToMany | Saját tab (`admin/view_related_tabs`) |
| hasOne | Általában fő `dl` |

Gyerek tab tábla: típusoszlop osztályok + legalább View + Edit a gyerek modulra.  
Kapcsolt lista sorrend: **ASC** név szerint (ugyanaz, mint a modal).

---

## 8. Dialógusok és JS API

| API | Mikor |
|-----|--------|
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés |
| `MyAdmin.alert({ icon, title, text })` | Info / warning / success |
| `MyAdmin.alertError(text)` | Hiba (AJAX, validáció UI) |
| `MyAdmin.initTooltips()` | Action gomb tooltippek |

Szövegek: `MyAdmin.messages` a layoutból (`__()` → hu).  
**Tilos:** `window.alert`, `confirm`, `prompt`.

---

## 9. i18n és locale (rövid)

1. Minden UI string: `__('English msgid')`
2. Fordítás: `resources/locales/hu_HU/default.po`
3. Admin: `LocaleMiddleware` + `Admin\AppController` → mindig `hu_HU`
4. Számok megjelenítése ≠ fordítás: `LocaleNumberParser::format()`, nem `__()` a számra

Részletek: [i18n.md](i18n.md).

---

## 10. Kötelező checklist egy „kész” Admin modulhoz

- [ ] Sidebar menüpont
- [ ] `index`: config változók, típusoszlopok + fix szélesség osztályok, sort URL, modal, SweetAlert delete, `pages/index`
- [ ] `form`: közös add/edit, locale szám/dátum, Select2 „+” ahol kell, `pages/form`
- [ ] `view`: `dl` + `view_related_tabs` (üres tab is), `pages/index` CSS
- [ ] `recordGet` (+ kapcsolt listák ASC); DashedRoute (`record-get`)
- [ ] Új stringek a `.po`-ban
- [ ] Nincs `window.alert` az admin JS-ben
- [ ] `doc/valtozasok.md` bejegyzés, ha a keret/szabály változott

---

## 11. Hol van a részletszabály?

| Téma | Fájl |
|------|------|
| Asset, index/form/view UI, Select2, oszloposztályok | [admin-konvenciok.md](admin-konvenciok.md) |
| Nulláról felállítás | [uj-projekt.md](uj-projekt.md) |
| Új tábla lépései | [crud-utmutato.md](crud-utmutato.md) |
| Szám/dátum middleware | [middleware.md](middleware.md) |
| `__()` / .po | [i18n.md](i18n.md) |
| Könyvtárak / elementek | [struktura.md](struktura.md) |
| Tartós vs. demó | [keretrendszer.md](keretrendszer.md) |
| Változásnapló | [valtozasok.md](valtozasok.md) |

Demó referencia (ha van a projektben): `templates/Admin/Samples/` + `SamplesController` — a **viselkedést** másold, nem a mezőneveket.
