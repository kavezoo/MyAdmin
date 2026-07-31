# Változásnapló

Minden lényeges projektmódosítás után **ide írj bejegyzést** (dátum, mi változott, érintett fájlok).  
Új CakePHP projektbe másolt `doc/` esetén: ezt a fájlt **nullázhatod** / új bejegyzéssel indíthatod; a többi spec (`uj-projekt.md`, `admin-konvenciok.md`, …) a tartós tudás.

---

## 2026-07-31 — Currency oszlop szélesebb (`12rem`)

### Mi változott
- `.table th/td.currency`: `8.5rem` → `12rem` (~4–5 számjegy plusz + „HUF”).

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-oldal.md`, `doc/admin-konvenciok.md`, `doc/README.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index: `row mt-3` a tartalom tetején

### Mi változott
- Minden Admin CRUD `index.php` külső sora: `class="row mt-3"` (térköz a breadcrumb / Flash alatt).

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Dokumentáció: Admin oldal teljes kép

### Mi változott
- Új **`doc/admin-oldal.md`**: egyben leírja, hogyan nézzen ki és működjön az Admin (layout, index/form/view, oszlopszélességek, interakciók, dialógus, i18n, checklist).
- `README.md` és a kapcsolódó spec fájlok erre mutatnak első olvasmánynak; rögzített döntések kiegészítve a teljes oszlopszélesség-táblával.

### Érintett fájlok
- `doc/admin-oldal.md` (új), `doc/README.md`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/crud-utmutato.md`, `doc/keretrendszer.md`, `doc/struktura.md`, `doc/valtozasok.md`

### Szabály
Lényeges UI/viselkedés változás után frissítsd az `admin-oldal.md`-t is (ne csak a részlet-doksit), amíg a célkép konzisztens marad.

---

## 2026-07-31 — Index oszlopszélességek: minta + szám/pénz/logikai fix

### Mi változott
- MyPluginTemplate `style.css` szerinti fix: `date` 8.5 / `datetime` 10.5 / `time` 5 / `times` 9 / `count` 5.5 / `visible|valid|boolean` 7.5 rem.
- MyAdmin kiegészítés (mintában nem volt width): `id` 4.75, `pos` 5.5, általános `number` 6.5, `currency` 8.5 rem.
- `string` oszlopok továbbra is rugalmasak.

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index: `$numberDecimals` tizedesjegyek

### Mi változott
- Minden `index.php` elején: `$numberDecimals = ['integer' => 0, 'decimal' => 2]`.
- Lista számkiírás: `LocaleNumberParser::format(..., decimals: $numberDecimals['integer'|'decimal'])`.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-konvenciok.md`, `uj-projekt.md`, `crud-utmutato.md`, `valtozasok.md`

---

## 2026-07-31 — Cities/index: id + pos fix szélesség; agent szabályok összefoglalva

### Mi változott
- CSS: `.table th/td.pos` = `5rem` (max 5 jegy + locale ezres, pl. `12 345`); `id` megmarad `4.75rem`; sort link `id`/`pos` oszlopban `width: 100%` (ne nyíljon szét).
- `uj-projekt.md` §5: tartós agent checklist — új projektnél ne kelljen újra elmondani a szabályokat.

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index számkiírás locale + fix oszlopszélességek

### Mi változott
- Index / view / `recordGet`: számok `LocaleNumberParser::format()` (Admin `hu_HU` → `1 234,56`); címkék továbbra is `__('English')`.
- Fix CSS: `th/td.id` = `4.75rem` (~7–8 jegy); date/datetime/time a MyPluginTemplate értékei (`8.5` / `10.5` / `5` rem) — dokumentálva.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`, `view.php`
- `SamplesController::recordGet`, `webroot/css/style.css`
- Doc: `admin-konvenciok.md`, `i18n.md`, `valtozasok.md`

---

## 2026-07-31 — Modal: HABTM lista ABC (ASC) sorrend

### Mi változott
A gyorsnézet modalban (és a view kapcsolt tabon) a **több-több / hasMany** megjelenített lista **név szerint ABC (ASC)** sorrendben jelenik meg.

Példa (Samples → Cities):

```php
$sample = $this->Samples->get($id, contain: [
    'Parents',
    'Cities' => function ($q) {
        return $q->orderBy(['Cities.name' => 'ASC']);
    },
]);
// recordGet: implode(', ', $city->name …) → ABC sorrendű felsorolás
```

**Tartós szabály:** minden Admin `recordGet` / view, ahol kapcsolt rekordnevek listája látszik → `contain` + `orderBy(['Alias.name' => 'ASC'])` (vagy a megjelenített címkemező).

### Érintett fájlok
- `src/Controller/Admin/SamplesController.php` (`recordGet`, `view`)

### Doc frissítve
- [x] `crud-utmutato.md` — recordGet szakasz
- [x] `admin-konvenciok.md` — index kötelező elem + modal kapcsolt listák
- [x] `uj-projekt.md` / `README.md` — rögzített döntés
- [x] `valtozasok.md`

---

## 2026-07-31 — Form számmezők locale (hu) + parser javítás

### Mi változott
- Inputmask locale-aware: `MyAdmin.config.numberFormat` (`LocaleNumberParser::jsConfig()`); hu: tizedes `,`, ezres szóköz.
- Mező osztályok: `.js-input-decimal` / `.js-input-integer`; value: `LocaleNumberParser::format()`.
- Parser: vegyes `1,234.56` / `1.234,56` helyes; mentéskor nem vesznek el számjegyek.
- Gyökérok: angol maszk (`1,234.56`) + hu middleware → Cake float cast = `1`.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php`, `webroot/js/pages/form.js`, `templates/Admin/Samples/form.php`, `tmp/test_locale_parsers.php`

### Doc frissítve
- [x] `middleware.md`, `admin-konvenciok.md` (Számmezők), `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Admin: window.alert → SweetAlert (`MyAdmin.alert`)

### Mi változott (tartós szabály új projektnél is)

Az Adminban **tilos** a natív `window.alert` / `confirm` / `prompt`. Minden felhasználói üzenet **SweetAlert2**:

| API | Szerep |
|-----|--------|
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés megerősítés |
| `MyAdmin.alert({ icon, title, text })` | Általános dialógus |
| `MyAdmin.alertError(text)` | Hiba (pl. Select2 mentés) |

- Implementáció: `webroot/js/app.js`
- Select2 hibák: `webroot/js/pages/form.js` → `App.alertError(...)`
- Szövegek: layout `MyAdmin.messages` (`errorTitle`, `okButton`, `failedToSave`, `saveNewValueFailed`, …) + `hu_HU/default.po`

Greenfield / következő projekt: kövesd [admin-konvenciok.md](admin-konvenciok.md) „SweetAlert” + [uj-projekt.md](uj-projekt.md) 2.6 / agent szabály 6.

### Érintett fájlok
- `webroot/js/app.js`, `webroot/js/pages/form.js`, `templates/layout/admin.php`
- Doc: `admin-konvenciok.md`, `uj-projekt.md`, `i18n.md`, `keretrendszer.md`, `README.md`, `valtozasok.md`

---
## 2026-07-31 — Fix: select2CreateCity Table vs Association

### Mi változott
- `select2Create` / `select2CreateCity`: `$this->fetchTable('Parents'|'Cities')` — nem `$this->Samples->Cities` (BelongsToMany Association ≠ Table).

### Érintett fájlok
- `SamplesController.php`, `doc/admin-konvenciok.md`

---

## 2026-07-31 — Modal gombok: Edit előbb, „View details”

### Mi változott
- Rekord / linked modal lábléc: **Edit** a **View details** előtt.
- Címke: `__('View details')` → hu: „Részletek megtekintése” (breadcrumb, tooltip, view actions is).

### Érintett fájlok
- `templates/element/admin/modal_record_view.php`, `modal_linked_record_view.php`, `breadcrumb.php`
- index/view templatek tooltip/title; `resources/locales/hu_HU/default.po`

---

## 2026-07-31 — Index opcionális oszlopok ($show*Column)

### Mi változott
Az `index.php` elején, a `$rowDoubleClickAction` **után** négy kapcsoló vezérli, mely opcionális oszlopok jelenjenek meg a listában:

```php
$showCountColumn = true;    // *_count (gyerek rekordok száma)
$showVisibleColumn = true;  // visible
$showCreatedColumn = true;  // created — önállóan
$showModifiedColumn = true; // modified — önállóan

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
// $indexColspan = kötelező oszlopok + bekapcsolt opcionálisak (üres lista sor)
```

| Változó | Hatás |
|---------|--------|
| `$showCountColumn` | `city_count` / `sample_count` stb. oszlop |
| `$showVisibleColumn` | `visible` oszlop |
| `$showCreatedColumn` | `created` a timestamp oszlopban |
| `$showModifiedColumn` | `modified` a timestamp oszlopban |

- Created és Modified **egyenként** ki/be kapcsolható.
- Ha mindkettő `true`: egy közös `th`/`td` (`datetime created modified`), két sort link, két sor a cellában.
- Ha csak az egyik: egy oszlop, csak az a mező.
- Ha egyik sem: nincs timestamp oszlop.
- Csak az **index táblára** vonatkozik (view/modal ettől független).
- Alapértelmezés minden demó modulban: mind `true`.

### Érintett fájlok
- `templates/Admin/Samples/index.php`
- `templates/Admin/Parents/index.php`
- `templates/Admin/Cities/index.php`

### Doc frissítve
- [x] `admin-konvenciok.md` — „Opcionális oszlopok”
- [x] `crud-utmutato.md` — index checklist
- [x] `uj-projekt.md` — index konfig
- [x] `valtozasok.md`

---

## 2026-07-31 — Index sor dupla kattintás konfigurálható

### Mi változott
Az `index.php` **elején**:

```php
$rowDoubleClickAction = 'modal'; // 'modal' | 'edit' | 'none'
```

| Érték | Hatás |
|-------|--------|
| `modal` | Gyors nézet `#modalRecordView` + `recordGet` (alapértelmezés) |
| `edit` | Navigálás az edit formra (`editUrl/{id}`) |
| `none` | Nincs művelet |

- Átmegy a `MyAdmin.config.rowDoubleClickAction`-be; kezelés: `webroot/js/pages/index.js`.
- A card fejléc súgó (`$rowDoubleClickHint`) az értékhez igazodik (üres, ha `none`).

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- `webroot/js/pages/index.js`
- `resources/locales/hu_HU/default.po` (`Double-click a row to edit the record.`)

### Doc frissítve
- [x] `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Select2 „+” multiple selectnél is

### Mi változott
- Cities (HABTM / multiple Select2) mellett is „+” gomb + modal; mentés után az új város **azonnal kiválasztott** a listában (meglévő kijelölések megmaradnak).
- Generikus minta: `data-select2-target` + `data-create-url` + `.modal-select2-add` / `.select2-add-form`; jövőbeli többmezős felvitel támogatott, a Select2 option szövege a válasz `text` (ill. `data-select2-text` mező).
- Endpoint: `select2CreateCity`; parent `select2Create` közös helperrel.

### Érintett fájlok
- `templates/Admin/Samples/form.php`, `webroot/js/pages/form.js`, `SamplesController.php`
- Doc: `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Hordozható dokumentáció (új projektből is felépíthető)

### Mi változott
- Új **[uj-projekt.md](uj-projekt.md)**: greenfield Admin keretrendszer lépésről lépésre (asset, routing, middleware, layout, elementek, i18n, első CRUD, agent szabályok).
- README / keretrendszer / struktura / crud-utmutato átírva: **nem** függnek a MyAdmin kódtól vagy fix `MyPluginTemplate` úttól; a `doc/` önálló specifikáció.
- View + related tabs, index/form/asset, i18n, middleware szabályok egy helyen követhetők új projektben is.

### Érintett fájlok
- `doc/uj-projekt.md` (új)
- `doc/README.md`, `keretrendszer.md`, `struktura.md`, `crud-utmutato.md`, `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Admin alap + keretrendszer funkciók (eredeti MyAdmin építés)

### Cél
CakePHP 5 Admin prefix Pike Admin kinézettel. Demó DB táblák csak keretrendszer-teszthez.

### Létrehozott / fő területek
- Layout + `templates/element/admin/*`
- Middleware: Locale + NormalizeLocalizedDate/Number
- i18n: `__('English')` + `hu_HU/default.po`; adminban nincs nyelvválasztó
- Index minta: URL sort; opcionális oszlopok (`$showCountColumn`, `$showVisibleColumn`, `$showCreatedColumn`, `$showModifiedColumn`); konfigurálható dupla klikk (`$rowDoubleClickAction`); SweetAlert delete
- View minta: bake `dl` + `view_related_tabs` (üres tab is)
- Form: Select2 „+” single **és** multiple (inline create); számmezők locale (`numberFormat`)
- Modal: kapcsolt HABTM/hasMany lista **ABC ASC**; gombok Edit → View details; Swal hibák
- JS: `MyAdmin` + `pages/index.js` / `pages/form.js`

### Demó modulok (eldobhatók)
- Samples / Parents / Cities CRUD — tanulság: belongsTo, hasMany, HABTM, `ParentRecord`

### Ismert következő lépések (opcionális)
- Lista Search bekötése
- `index()` default sort / session query
- Auth / login
- Member többnyelvű .po bővítés

---

<!-- Új bejegyzés sablon:

## YYYY-MM-DD — Rövid cím

### Mi változott
- …

### Érintett fájlok
- …

### Doc frissítve
- [ ] uj-projekt.md / admin-konvenciok.md / crud-utmutato.md / …

-->
