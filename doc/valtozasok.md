# Változásnapló

Minden lényeges projektmódosítás után **ide írj bejegyzést** (dátum, mi változott, érintett fájlok).  
Új CakePHP projektbe másolt `doc/` esetén: ezt a fájlt **nullázhatod** / új bejegyzéssel indíthatod; a többi spec (`uj-projekt.md`, `admin-konvenciok.md`, …) a tartós tudás.

---

## 2026-07-31 — Cities form: Samples HABTM Select2 multiple

### Mi változott
- Cities add/edit: `samples._ids` multiple Select2 (mint Samples → Cities fordítva).
- `setFormOptions()` + `patchEntity` `associated => ['Samples']` + `sample_count` a kiválasztottakból.
- Nincs Select2 „+” Sample-hez (sok kötelező mező); tags csak ha van create gomb.
- Placeholder: `data-placeholder` / `Select samples...`.

### Érintett fájlok
- `templates/Admin/Cities/form.php`, `Samples/form.php` (placeholder)
- `CitiesController.php`, `webroot/js/pages/form.js`, `layout/admin.php`
- `resources/locales/hu_HU/default.po`
- Doc: `admin-konvenciok.md` (HABTM multiple Select2), `admin-oldal.md`, `crud-utmutato.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Modal: HABTM lista linkekkel (Cities ↔ Samples)

### Mi változott
- Cities `recordGet`: `samples` = `[{id, name}, …]` ASC; index modal „Sample list” + linked modal (Sample details).
- Samples `recordGet`: `cities` ugyanez a formátum (nem implode string) + linkek.
- JS: `relatedLinkFields` config → `.record-modal-link` a modal mezőkben.
- Cities view fő `dl`: Sample list linkek (mint Samples City list).

### Érintett fájlok
- `webroot/js/pages/index.js`
- `CitiesController` / `SamplesController` `recordGet`
- `templates/Admin/Cities/{index,view}.php`, `Samples/index.php`
- `resources/locales/hu_HU/default.po` (`Sample list`)
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `valtozasok.md`

---

## 2026-07-31 — Napi zárás (péntek) → folytatás hétfőn

### Mai nap összefoglalója (rögzítve a specekben is)
1. **Form `#name` autofókusz** — minden Admin form + Select2 „+” modal (`pages/form.js` kötelező).
2. **Séma DEFAULT-ok** — `UsesDatabaseColumnDefaultsTrait`; `pos`/`visible`/`logikai` DB-ből; `cities_samples.pos` DEFAULT 1000; `*_count` még PHP `0`.
3. **Mentés hibák** — try/catch + Flash; `beforeMarshal` ArrayObject → `getArrayCopy()`.
4. **Index lapozás** — `$indexLimit` (10) / `$indexMaxLimit` (100) + `indexPaginateOptions()`.
5. **Utolsó rekord** — session `Admin.lastVisited` + index `.last-visited` (zöld); később bővíthető.

### Hol a tudás
- Célkép: [admin-oldal.md](admin-oldal.md)
- Részletek: [admin-konvenciok.md](admin-konvenciok.md) (`.last-visited`, limit, form fókusz, DB default)
- Új modul: [crud-utmutato.md](crud-utmutato.md)
- Agent checklist: [uj-projekt.md](uj-projekt.md) §5

### Hétfői nyitott / lehetséges folytatás
- `.last-visited` bővítés (pl. scroll a sorhoz, linked-modal finomítás)
- `*_count` oszlopokra DB `DEFAULT 0` → PHP `0` eltávolítása
- Index keresés bekötése (UI megvan)
- Egyéb UI/CRUD finomítások a felhasználó szerint

---

## 2026-07-31 — Index: utolsó rekord (`.last-visited` + session)

### Mi változott
- Session `Admin.lastVisited`: model alias + id (és `_last`); mentés view / edit betöltés / sikeres save / `recordGet`.
- Index: `$lastVisitedId` → sor `class="last-visited"` (meglévő zöld CSS a `style.css`-ben).
- Helper: `rememberLastVisited()`, `setLastVisitedForIndex()` — később bővíthető.

### Érintett fájlok
- `src/Controller/Admin/AppController.php`
- Samples / Parents / CitiesController
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `keretrendszer.md`, `valtozasok.md`

---

## 2026-07-31 — Index: `$indexLimit` / `$indexMaxLimit`

### Mi változott
- Minden Admin CRUD controller tetején: `$indexLimit` (alap sor/oldal, default **10**) és `$indexMaxLimit` (felső korlát, default **100**) — URL `?limit=` hack ellen.
- `AppController::indexPaginateOptions()` adja a Cake Paginator `limit` + `maxLimit` értékeit.

### Érintett fájlok
- `src/Controller/Admin/AppController.php`
- `Samples` / `Parents` / `Cities`Controller
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `keretrendszer.md`, `valtozasok.md`

---

## 2026-07-31 — Oszlop DEFAULT-ok a sémából (`pos`, `visible`, …)

### Mi változott
- Élő DB: `pos` DEFAULT 1000 (cities, parents, samples); `cities_samples.pos` is DEFAULT 1000 (korábban hiányzott); `visible` / `logikai` DEFAULT 1.
- Új trait: `UsesDatabaseColumnDefaultsTrait` (régi `OmitsEmptyPos…` helyett) — üres mező unset + `applySchemaDefaults()` a séma alapján.
- Controller: `newEntityWithSchemaDefaults()`; nincs hardkodolt `visible=true` / `logikai=true`; Select2 create nem küld `visible`-t.
- `*_count` továbbra is `0` a PHP-ban (NOT NULL, nincs DB DEFAULT).

### Érintett fájlok
- `src/Model/Table/Concerns/UsesDatabaseColumnDefaultsTrait.php`
- `CitiesTable`, `ParentsTable`, `SamplesTable`, `CitiesSamplesTable`
- `Admin/AppController`, Cities/Parents/SamplesController
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `keretrendszer.md`, `struktura.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Form: `name` mező autofókusz + doksi szinkron

### Mi változott
- **Minden** Admin `form.php`: `#name` `autofocus` + kötelező `pages/form` JS; `focusPrimaryFormField()` a Select2/inputmask **után** (ne lopja el a fókuszt).
- Cities / Parents is betölti a `form.js`-t (korábban csak CSS).
- Select2 „+” modal name input: `autofocus`.
- Spec: célkép + konvenciók + CRUD / új projekt / keretrendszer.

### Érintett fájlok
- `templates/Admin/{Cities,Parents,Samples}/form.php`
- `webroot/js/pages/form.js`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `keretrendszer.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Fix: `beforeDelete` CakePHP 5.2 deprecation

### Mi változott
- `PreventsDeleteWithChildrenTrait::beforeDelete`: ne `return false` — `$event->stopPropagation()` + `$event->setResult(false)` (CakePHP ≥5.2).

### Érintett fájlok
- `src/Model/Table/Concerns/PreventsDeleteWithChildrenTrait.php`
- Doc: `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Index: `$showIdColumn`

### Mi változott
- Minden Admin CRUD `index.php` elején: `$showIdColumn = true|false` — az `id` (`#`) oszlop ki/be.
- `$indexColspan` számítás figyelembe veszi.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `uj-projekt.md`, `README.md`, `valtozasok.md`

---

## 2026-07-31 — Pénznem megjelenítés: `Ft` (nem HUF)

### Mi változott
- `LocaleNumberParser::currencySymbol()` — Admin `hu_HU` → **`Ft`** (magyar szokás; nem ISO `HUF`).
- Index / view / `recordGet`: minden `HUF` hardkód cseréje a helperre.
- Későbbi EUR: a helper `match` ágát bővíteni — ne template hardkód.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php` (`currencySymbol`)
- `templates/Admin/Samples/index.php`, `Samples/view.php`, `Parents/view.php`, `Cities/view.php`
- `SamplesController::recordGet`
- Doc: **`admin-konvenciok.md`**, **`admin-oldal.md`**, **`i18n.md`**, **`middleware.md`**, `README.md`, `struktura.md`, `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

### Példa
```php
<?= h(LocaleNumberParser::format($row->netto, decimals: 2)) ?>
<?= h(LocaleNumberParser::currencySymbol()) ?>
// → 12 345,67 Ft
```

---

## 2026-07-31 — Fix: Table-en nincs `getTableLocator`

### Mi változott
- `CitiesTable` / `SamplesTable` `countRelatedChildren`: join számlálás `getAssociation(…)->junction()`-nel (CakePHP 5 Table-en nincs `getTableLocator()`).

### Érintett fájlok
- `src/Model/Table/CitiesTable.php`, `SamplesTable.php`, `doc/valtozasok.md`

---

## 2026-07-31 — Form Parent lista: visible + pos, name sorrend

### Mi változott
- Sample form Parent Select2/list: csak `visible = true`; rendezés `pos` ASC, `name` ASC.
- Edit: a jelenlegi `parent_id` akkor is megjelenik, ha a szülő nem visible.
- **Általános konvenció** belongsTo listákra dokumentálva.

### Érintett fájlok
- `src/Controller/Admin/SamplesController.php` (`setFormOptions`)
- Doc: **`admin-oldal.md`** §6, **`admin-konvenciok.md`** (Form → Kapcsolt lista), `crud-utmutato.md`, `uj-projekt.md` §5–6, `README.md`, `valtozasok.md`

### Példa (rövid)
```php
->where(['OR' => [['Parents.visible' => true], ['Parents.id' => $sample->parent_id]]])
->orderBy(['Parents.pos' => 'ASC', 'Parents.name' => 'ASC'])
```

---

## 2026-07-31 — `beforeMarshal` ArrayObject + mentés hibakezelés

### Mi változott
- Bugfix: `array_key_exists('pos', $data)` TypeError PHP 8+-on, mert Cake `ArrayObject`-et ad (`OmitsEmptyPosForDbDefaultTrait`, `CitiesSamplesTable`). Megoldás: `$data->getArrayCopy()`.
- Cities / Parents / Samples `add`/`edit`: váratlan kivétel → Flash („The record could not be saved…”), nem nyers PHP hiba.
- Select2 inline create: validációs első hibaüzenet JSON-ban; Throwable → udvarias üzenet.

### Érintett fájlok
- `src/Model/Table/Concerns/OmitsEmptyPosForDbDefaultTrait.php`
- `src/Model/Table/CitiesSamplesTable.php`
- `src/Controller/Admin/{Cities,Parents,Samples}Controller.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `keretrendszer.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — `pos`: csak DB default, ne PHP 1000

### Mi változott
- Eltávolítva minden programozott `pos = 1000` (controller add, Select2 create, `normalizeCounters`, CitiesSamples beforeSave/Marshal force).
- `OmitsEmptyPosForDbDefaultTrait`: üres `pos` → unset → INSERT-nél a séma DEFAULT érvényesül.
- Validáció: `pos` `allowEmptyString`.

### Érintett fájlok
- `src/Model/Table/Concerns/OmitsEmptyPosForDbDefaultTrait.php`
- `ParentsTable`, `SamplesTable`, `CitiesTable`, `CitiesSamplesTable`
- `Samples`/`Parents`/`Cities`Controller
- Doc: `struktura.md`, `crud-utmutato.md`, `keretrendszer.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — `*_count` oszlop: 0/null ne jelenjen meg

### Mi változott
- `LocaleNumberParser::formatCount()` — null vagy 0 → üres string.
- Index / view / `recordGet` / modal: count mezők nem írnak ki `0`-t.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php`
- `templates/Admin/{Samples,Parents,Cities}/index.php`, `view.php`
- `*Controller` recordGet / parentGet
- `webroot/js/pages/index.js`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `valtozasok.md`

---

## 2026-07-31 — Törlés: gyerekvédelem + működő delete form

### Mi változott
- Model: `PreventsDeleteWithChildrenTrait` — gyerek/join van → `beforeDelete` false + `_delete` hibaüzenet; gyerek nélkül törölhető (HABTM `dependent => true` a joinra).
- **Bugfix:** `Form->postLink` `id` az `<a>`-n volt → JS nem submitolt. Most `Form->create` `#delete-form-{id}`.
- UI: `*_count > 0` → Delete gomb disabled + tooltip; modal `can_delete`; breadcrumb Swal + `#delete-form-current`.
- Controller: `deleteEntityOrFail`, `setCanDeleteFlag`; Flash a model üzenettel.

### Érintett fájlok
- `src/Model/Table/Concerns/PreventsDeleteWithChildrenTrait.php`
- `ParentsTable` / `SamplesTable` / `CitiesTable`
- `Admin/AppController`, `*Controller` delete/view/edit/recordGet
- Index/view templatek, `breadcrumb.php`, `app.js`, `pages/index.js`, `hu_HU/default.po`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `valtozasok.md`

---

## 2026-07-31 — View: kapcsolt rekordok modal link + dupla klikk

### Mi változott
- View: belongsTo / HABTM / kapcsolt tab **name** → félkövér `.record-modal-link` → `#modalLinkedRecordView` (AJAX `recordGet`).
- Modal gombok: Close, Edit, View details, Delete; Delete → SweetAlert `confirmDelete`.
- View elején `$rowDoubleClickAction` (`modal`/`edit`/`none`) a `.related-records-table` soraira.
- `pages/index.js`: generikus linked context (`data-*` URL-ek, `entityFieldLabels`); delete form prefix / CSRF POST.
- Delete actionök: redirect `referer`-re (view-ról törlés után vissza).
- Samples view: Parent link + City list + Cities tab name linkek.

### Érintett fájlok
- `webroot/js/pages/index.js`, `webroot/css/style.css`
- `templates/Admin/{Samples,Parents,Cities}/view.php`, `Samples/index.php` (`parentDeleteUrl`)
- `src/Controller/Admin/{Samples,Parents,Cities}Controller.php` (delete referer)
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `README.md`, `valtozasok.md`

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
