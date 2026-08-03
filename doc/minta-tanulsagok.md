# Minta (demó) → éles projekt — tanulságok

A **Samples / Parents / Cities** modulok és a `myadmin` demó DB **csak minta**: a keretrendszer kipróbálására szolgálnak.  
Éles projektnél **cseréld / töröld** őket — a **viselkedést és a `doc/` szabályokat** vidd tovább.

Ez a fájl a demóból leszűrt, **tartós** mintákat rögzíti, hogy új / éles adatbázison az agent **újra fel tudja építeni** a CRUD-ot chatelőzmény nélkül.

Kapcsolódó: [keretrendszer.md](keretrendszer.md), [crud-utmutato.md](crud-utmutato.md), [admin-konvenciok.md](admin-konvenciok.md), [admin-oldal.md](admin-oldal.md), [uj-projekt.md](uj-projekt.md), [middleware.md](middleware.md), [i18n.md](i18n.md).

---

## 0. Éles adatbázis — agent playbook (olvasd el először)

Ha a felhasználó **valós táblákra** kér Admin CRUD-ot (vagy demó→éles átállást):

1. Olvasd el a DB sémát (migration / `DESCRIBE` / Cake schema) — **ne** a demó Samples mezőket másold vakon.
2. Ellenőrizd, hogy a **keretrendszer** megvan (layout, middleware, AppView, traits, `pages/*.js`) — hiányzó részek: [uj-projekt.md](uj-projekt.md) + alább **§0.1**.
3. Kövesd a [crud-utmutato.md](crud-utmutato.md) lépéseit + **ebből** a fájlból a mintákat (CounterCache, delete UI, Flash, Tempus, szám, mezőhiba, view).
4. Minden új modulnál másold a **viselkedést**, ne a demó domain neveket.
5. Ellenőrizd a **§11 checklistet** modulonként + a **§0.2 konfig** értékeket.

### 0.1 Keretrendszer — kötelező fájlok (éles indulás előtt)

| Terület | Fájl / hely |
|---------|-------------|
| Admin locale | `config/app.php` → `App.adminLocale` (éles: **`hu_HU`**; teszt EN: `en_US`) |
| Locale MW | `src/Middleware/LocaleMiddleware.php` — Admin → `Configure::read('App.adminLocale')` |
| Admin AppController | `src/Controller/Admin/AppController.php` — ugyanaz a locale + layout + index/modal helperök |
| Form hibák | `src/View/AppView.php` — Admin: `templates.errorClass=is-invalid`, `inputContainerError={{content}}{{error}}` |
| Mezőhiba element | `templates/element/admin/field_error.php` |
| Szám MW + parser | `NormalizeLocalizedNumberMiddleware` + `LocaleNumberParser` |
| Dátum MW + parser | `NormalizeLocalizedDateMiddleware` + `LocaleDateParser` (`jsConfig`: intl, moment, startOfTheWeek, formátumok) |
| Form JS/CSS | `webroot/js/pages/form.js`, `css/pages/form.css`, `style.css` (`.error-message`, `.record-view-*`) |
| Index JS/CSS | `webroot/js/pages/index.js`, `css/pages/index.css` |
| Middleware sorrend | [keretrendszer.md](keretrendszer.md) / [middleware.md](middleware.md) |

### 0.2 Konfig — `App.adminLocale` (egy helyen)

```php
// config/app.php
'adminLocale' => env('APP_ADMIN_LOCALE', 'hu_HU'), // éles default
// Ideiglenes EN teszt: 'en_US'  (vagy .env: APP_ADMIN_LOCALE=en_US)
```

| Locale | UI szövegek | Szám | Dátum mező / Tempus | Hét első napja |
|--------|-------------|------|---------------------|----------------|
| `hu_HU` | `hu_HU/default.po` | `1 234,56` | `YYYY.MM.DD.` | hétfő |
| `en_US` | angol msgid | `1,234.56` | `MM/DD/YYYY` | vasárnap |
| `en_GB` | (ha van .po) | `1,234.56` | `DD/MM/YYYY` | hétfő |

A Tempus naptár: `dateFormat.intl` + `Intl.Locale.weekInfo` (fallback: `dateFormat.startOfTheWeek`). Mentés **mindig** middleware → SQL kanonikus.

**Élesben:** `adminLocale = hu_HU`. Teszt után ne hagyd `en_US`-en.

### Kapcsolat-típus → kötelező minta

| DB kapcsolat | Mit csinálj |
|--------------|-------------|
| hasMany / belongsTo | CounterCache a **gyerek** Table-en → szülő `*_count`; törlésvédelem a szülőn |
| belongsToMany | Through Table + CounterCache **mindkét** `*_count`; `cascadeCallbacks` + `saveStrategy: replace`; formon **mindkét** oldalon multiple Select2; üres `_ids` → `[]` |
| date / time / datetime oszlop | Tempus Dominus 6; `dateFormat` = `LocaleDateParser::jsConfig()`; mentés middleware |
| integer / decimal / float oszlop | `.js-input-integer` / `.js-input-decimal` + `numberFormat`; mentés szám middleware |
| Nincs gyerek | Nincs `PreventsDeleteWithChildrenTrait`; Delete mindig danger |

### Demó → éles mezőleképezés (példa)

| Demó | Élesben |
|------|---------|
| `Parents` / `Samples` / `Cities` | Valós entitások (pl. Categories / Products / Tags) |
| `sample_count` / `city_count` | `{child}_count` a CounterCache szabály szerint |
| `datum` / `ido` / `datumido` | Valós `date` / `time` / `datetime` + `.js-tempus-picker` |
| `szam` / `netto` | Valós integer / decimal + `LocaleNumberParser` + szám middleware |
| `parent_id` Select2 + „+” | Valós belongsTo + `select2Create` ha egyszerű create |

---

## 1. Demó ↔ keretrendszer leképezés

| Demó (eldobható) | Mit tanul belőle (tartós) |
|------------------|---------------------------|
| `Parents` hasMany `Samples` | Gyerekvédelem törléskor; CounterCache a **gyerek** Table-en → `Parents.sample_count` |
| `Samples` belongsTo `Parents` | Index/form Parent Select2 (`visible` + `pos`/`name`); linked modal |
| `Samples` ↔ `Cities` HABTM | Through + CounterCache; formon **mindkét** oldalon multiple Select2; üres `_ids` → `[]` |
| `cities_samples` | Through: `UsesDatabaseColumnDefaultsTrait`; CounterCache; `cascadeCallbacks` + `saveStrategy: replace` |
| Index (`Samples/index.php`) | Teljes lista-minta: `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`, `$indexLimit`, last-visited, modalok |
| `recordGet` / `parentGet` | Modal JSON + `can_delete` + kapcsolt névlisták |
| Sidebar / Dashboard demó menü | Cseréld éles domain menüre |

---

## 2. CounterCache + törlésvédelem (kötelező minta)

**Ne** írj manuális `find()->count()`-ot a `countRelatedChildren`-be, és **ne** állíts `*_count = count(_ids)`-t a controllerben.

| Kapcsolat | CounterCache hol? | Mező |
|-----------|-------------------|------|
| hasMany / belongsTo | **Gyerek** Table | pl. `Parents.sample_count` |
| belongsToMany | **Through** Table | pl. `Samples.city_count`, `Cities.sample_count` |

```php
// Gyerek Table (Samples → Parents)
$this->belongsTo('Parents', […]);
$this->addBehavior('CounterCache', ['Parents' => ['sample_count']]);

// HABTM mindkét oldalon
$this->belongsToMany('Cities', [
    'through' => 'CitiesSamples',
    'dependent' => true,
    'cascadeCallbacks' => true,   // kötelező
    'saveStrategy' => 'replace',
]);

// Through Table — előbb belongsTo, aztán CounterCache
$this->belongsTo('Samples', […]);
$this->belongsTo('Cities', […]);
$this->addBehavior('CounterCache', [
    'Samples' => ['city_count'],
    'Cities' => ['sample_count'],
]);

// Törlésvédelem
use PreventsDeleteWithChildrenTrait;
protected function relatedChildrenCountField(): string
{
    return 'city_count'; // vagy sample_count
}
```

- Trait: `countRelatedChildren()` / `canDelete()` a CounterCache oszlopot olvassa (**friss DB** érték PK mellett).
- Új rekord: `*_count = 0` csak ha NOT NULL és nincs DB DEFAULT.
- Elcsúszás / import után: `bin/cake rebuild_counter_caches`
- UI: lásd §3 (Delete gomb kinézet)

Részlet: [admin-konvenciok.md](admin-konvenciok.md) → „CounterCache”, „Törlés — gyerekvédelem”.

---

## 3. Törlés gomb UI (mindenhol ugyanaz)

| Állapot | Index / view related | Breadcrumb / modal footer |
|---------|----------------------|---------------------------|
| **Törölhető** (`*_count = 0` / `can_delete: true`) | `btn-outline-danger btn-row-delete` + rejtett `#delete-form-…` | `btn-danger` |
| **Nem törölhető** | `btn-outline-secondary disabled` (wrapper `span` + tooltip) | `btn-secondary disabled` (+ modal: JS wrapper tooltip) |

- Megerősítés (csak ha törölhető): `MyAdmin.confirmDelete` → **`icon: 'question'`** → form POST.
- Linked / related delete: `deleteUrl` + `deleteFormPrefix` (soha ne a saját modul `#delete-form-{id}`-je kapcsolt entitásnál).
- Modal JS: `setModalDeleteEnabled($btn, canDelete)` — danger ↔ secondary+disabled.
- Breadcrumb: `setCanDeleteFlag($table, $entity)` → `$canDelete`.

**Ne** használj `is-delete-blocked` + kattintható hibaüzenetet — a tiltott gomb **disabled secondary**.

---

## 4. Modalok (record + linked)

| Szabály | Érték |
|---------|--------|
| Saját rekord | `#modalRecordView` + `recordGet` |
| Kapcsolt rekord | `#modalLinkedRecordView` + `relatedLinkFields` / `data-*` |
| Kapcsolt **névlista** a modalban | Utolsó **20** (`modified DESC` + limit), megjelenítés **name ASC** |
| Helper | `containRelatedForModal($alias)`, `relatedNameLinksForModal($entities)`, `$modalRelatedLimit` |
| JSON lista | `[{id, name}, …]` — ne `implode` string |
| Delete | §3 (secondary disabled / danger) |
| SweetAlert modal fölött | `MyAdmin.swal()` — FocusTrap pause + z-index 20000; popup árnyék (§5) |
| View tab / fő `dl` | Teljes lista lehet ABC ASC (a 20-as limit a **modal JSON**-ra vonatkozik) |

---

## 5. Flash üzenetek (Notify + SWAL)

JeffAdmin5 mintájára — Admin layout végén `<script>` + `admin/script_flash` + `Flash->render()`.

| Típus | Használat | Viselkedés |
|-------|-----------|------------|
| **Notify** (alap) | `$this->Flash->success($msg)` | Simple Notify toast; **több** egyszerre (bottom left) |
| **SWAL** | `$this->flashSwal('success', $msg)` vagy `['element' => 'flash/success_swal']` | SweetAlert2 modal; egyszerre **egy** (több sorban: `MyAdmin.flashSwal`) |
| Legacy | `['element' => 'flash_/success']` | jquery-toastmessage (opcionális) |

Assetek (legújabb): `webroot/plugins/simple-notify/` (≥1.0.6), `webroot/plugins/sweetalert2/` (11.x).

**SweetAlert2 kinézet (`style.css`):**

| Elem | Szabály |
|------|---------|
| `.swal2-container` | `z-index: 20000` — Bootstrap modal fölött |
| `.swal2-popup` | Látható **box-shadow** (linked modal / Tempus panellel egy család): `0 0.75rem 2rem rgba(0,0,0,.28), 0 0.25rem 0.75rem rgba(0,0,0,.18)` |

Nem Admin prefix: a `flash/*.php` HTML `.message` divet ad (default layout).

---

## 6. Dátum / idő / dátumidő picker (Tempus Dominus)

JeffAdmin5 opciók; asset: `webroot/plugins/tempus-dominus/` — **ne** daterangepicker.

| Típus | `data-picker-type` | UI (hu) | UI (en_US) | Init ISO (`data-picker-value`) |
|-------|--------------------|---------|------------|--------------------------------|
| date | `date` | `YYYY.MM.DD.` | `MM/DD/YYYY` | `Y-m-d` |
| time | `time` | `HH:mm:ss` | `HH:mm:ss` | `H:i:s` |
| datetime | `datetime` | `YYYY.MM.DD. HH:mm:ss` | `MM/DD/YYYY HH:mm:ss` | `Y-m-d H:i:s` |

**Kötelező form config:**

```php
'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
// → locale, intl, moment, startOfTheWeek, date, datetime, time
```

| Réteg | Szabály |
|-------|---------|
| Megjelenítés (input value) | `LocaleDateParser::format($entity->field, 'date'|'time'|'datetime')` |
| Megjelenítés (index / view / modal) | `format(..., 'date'|'time_short'|'datetime_short')` — **ne** `$entity->field->format('Y.m.d.')` |
| Tempus UI nyelv | `localization.locale` = `dateFormat.intl` (`hu-HU` / `en-US`) — **ne** hardcode `'hu'` |
| 12/24 óra + meridiem | `dateFormat.useTwentyFourHour`: hu/EU → **24h** (nincs DE/DU); `en_US` → **12h AM/PM** (Intl locale a DateTime-on) |
| Hét első napja | JS: `Intl.Locale.weekInfo`; fallback `dateFormat.startOfTheWeek` (hu→1, en_US→0) |
| Input formátum | `form.js` felülírja a Tempus 6.0 Intl `formatInput`-ot moment tokenekkel (különben hu: `2024. 03. 15.` szóköz) |
| Mentés | `NormalizeLocalizedDateMiddleware` → `Y-m-d` / `H:i:s` / `Y-m-d H:i:s` |
| Markup | `.input-group.js-tempus-picker` + `data-td-target-*` + input `data-td-target`; hiba: `'error' => false` + `field_error` a wrapper **után** |
| Edit init érték | `data-picker-value` = ISO (`Y-m-d` / `H:i:s` / `Y-m-d H:i:s`); JS: input ürítés → Tempus init → JeffAdmin5 `parseInput(moment.toDate())` + `setValue` — **ne** bízz a locale display string natív parse-ában |
| Idő = dátumidő óra | Ugyanaz a `tempusClockComponents` (óra/perc/mp + `useTwentyFourHour`); közös óra CSS (`.time-container-clock`) — egy „család” |
| AM/PM gomb (12h) | Plugin light: kék bg + fehér szöveg. **Ne** csak `color:#0d6efd` (kék a kéken = láthatatlan). Override: világoskék bg + kék szöveg + keret (`button[data-action=toggleMeridiem]`) |
| Téma | `display.theme: 'light'`; fehér panel + keret (`style.css`) |

Részlet: [middleware.md](middleware.md).

---

## 6b. Számmezők (integer / decimal)

| Réteg | Szabály |
|-------|---------|
| Form config | `'numberFormat' => LocaleNumberParser::jsConfig()` |
| Mező class | `LocaleNumberParser::formIntegerOptions()` / `formDecimalOptions()` (`.js-input-integer` / `.js-input-decimal` + locale value + placeholder) |
| inputmode | **ne** `decimal`/`numeric` a templateben — `form.js` → `inputmode: 'text'` |
| Inputmask | `autoUnmask` + `removeMaskOnSubmit`; locale radix/group a `numberFormat`-ból |
| Value (form) | `LocaleNumberParser::format($v, decimals: 0|2)` |
| Lista / view | `format()` / `formatCount()` (0/null count → üres); pénz: `formatCurrency()` (HUF, ICU) |
| Mentés | `NormalizeLocalizedNumberMiddleware` → `1234` / `1234.56` (tizedes megmarad ahol nem egész) |
| Parser | Szóközös ezres (`1 234 567`) **ne** dátumnak; dátumok (`.`/`/`/`-`) kihagyva |

Élesben ha „must be an integer” / elcsúszott tizedes: először a middleware + `looksLikeNumber` / locale — ne a validatorral „javítsd”.

---

## 6c. Mező validációs hibák (form)

| Szabály | Érték |
|---------|--------|
| Hol | A mező **alatt** (soha Flash-ben a mezőszöveg) |
| Stílus | `.error-message` — piros `#dc3545`, `font-weight: 700` (`style.css`) |
| AppView (Admin) | `templates.errorClass` = `is-invalid` (**ne** deprecated `setConfig('errorClass')`); `inputContainerError` = `{{content}}{{error}}` |
| Egyszerű control | Automatikus hiba a control után |
| Tempus / Select2+ / checkbox | `'error' => false` + `<?= $this->element('admin/field_error', ['field' => '…']) ?>` a wrapper **után** |

---

## 7. Index / form / view (rövid checklist éles modulhoz)

### Index
- `$indexLimit` / `$indexMaxLimit` + `indexPaginateOptions()`
- `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`
- `setLastVisitedForIndex` + `.last-visited`
- Delete: §3
- `pages/index` JS/CSS + `modal_record_view` (+ `modal_linked_record_view` ha kell)
- Számok: `LocaleNumberParser::format` / `formatCount` / `formatCurrency`

### Form
- `#name` autofocus + `form.js` (+ `pages/form` CSS)
- Config: `numberFormat` + `dateFormat` (`jsConfig()`), ha van szám/dátum
- `newEntityWithSchemaDefaults()`; `pos`/`visible`/… = DB DEFAULT
- **`visible` + `pos`:** form végén; csak a `visible` fölött `<div class="row"><div class="col-12 col-xxl-11"><hr class="my-4"></div></div>`; sorrend **visible → pos**
- belongsTo lista: `visible` + `pos` ASC, `name` ASC; editnél aktuális is
- HABTM: `related._ids` mindkét oldalon; hiányzó `_ids` → `[]`
- try/catch → Flash (Notify); Select2 create → JSON `message`
- date/time/datetime: §6; számok: §6b; mezőhibák: §6c
- Lábléc Save/Cancel: `col-12 col-md-10 col-xxl-9 offset-md-2` (form label `col-md-2`)
- CounterCache tartja a `*_count`-ot

### View
- Fő `dl.record-view-fields` + `view_related_tabs` (üres tab is)
- Grid: `dt` **9rem** + gap **1rem** + `dd`
- **Dátum / idő / szám:** mindig locale — `LocaleDateParser::format(..., 'date'|'time_short'|'datetime_short')`, `LocaleNumberParser::format` / `formatCount` / `formatCurrency` — **ne** hardcode `Y.m.d.` / `H:i`
- Kapcsolt nevek: `.record-modal-link` → linked modal
- Related actions Delete: §3
- `$rowDoubleClickAction` a kapcsolt táblán
- Lábléc **csak Edit**: `.record-view-footer-actions` (`padding-left: calc(9rem + 1rem)`) — az **adatoszlop** alatt; **ne** `offset-md-2` (az a formé)

---

## 8. JS / dialógus (tartós API)

| API | Szerep |
|-----|--------|
| `MyAdmin.swal({…})` | Swal + Bootstrap FocusTrap pause + z-index 20000; popup **árnyék** (`style.css`) |
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés megerősítés (`icon: 'question'`) |
| `MyAdmin.alert` / `alertError` | Üzenetek — **tilos** `window.alert` |
| `MyAdmin.flashSwal({…})` | Flash SWAL sorban |
| `flashMessage(title, text, status)` | Flash Notify (`script_flash`) |
| `pages/index.js` | Record/linked modal, delete, last-visited, dblclick |
| `pages/form.js` | Select2, Tempus Dominus (locale + hétkezdet), inputmask, select2-add, `#name` focus |

---

## 9. Admin AppController helpers (másold élesbe)

| Property / metódus | Cél |
|--------------------|-----|
| `I18n::setLocale(App.adminLocale)` | Admin nyelv (éles: hu_HU) |
| `$indexLimit` / `$indexMaxLimit` | Lista lapozás |
| `indexPaginateOptions()` | Paginator |
| `rememberLastVisited` / `setLastVisitedForIndex` | `.last-visited` |
| `$modalRelatedLimit` (20) | Modal gyereklista limit |
| `containRelatedForModal()` | `modified DESC` + limit |
| `relatedNameLinksForModal()` | `[{id,name}]` ABC |
| `newEntityWithSchemaDefaults()` | DB defaultok |
| `deleteEntityOrFail()` / `setCanDeleteFlag()` | Törlés + breadcrumb |
| `flashSwal($type, $message)` | Flash SweetAlert2 modal |

---

## 10. Traits / Command / assetek (tartós)

| Elem | Útvonal |
|------|---------|
| Gyerekvédelem | `src/Model/Table/Concerns/PreventsDeleteWithChildrenTrait.php` |
| DB oszlop default | `src/Model/Table/Concerns/UsesDatabaseColumnDefaultsTrait.php` |
| CounterCache rebuild | `bin/cake rebuild_counter_caches` |
| Locale dátum | `LocaleDateParser` + `NormalizeLocalizedDateMiddleware` |
| Locale szám | `LocaleNumberParser` + `NormalizeLocalizedNumberMiddleware` |
| Form hibák | `AppView` + `element/admin/field_error.php` |
| Notify / Swal / Tempus | `webroot/plugins/simple-notify/`, `sweetalert2/`, `tempus-dominus/`, `js/popper.js` |
| Flash elemek | `templates/element/flash/*`, `flash_/*`, `admin/script_flash.php` |

---

## 11. Éles modul checklist (pipáld le)

- [ ] Table: associations + CounterCache (+ through ha HABTM)
- [ ] `PreventsDeleteWithChildrenTrait` + `relatedChildrenCountField()` ha van gyerek
- [ ] Controller: CRUD + `recordGet` (`can_delete`) + try/catch Flash + `setCanDeleteFlag` view/edit
- [ ] Index: oszlopok, delete §3, modal config, last-visited + scroll, számformázás, **`admin/index_footer`** (counter + First…Last lapozó)
- [ ] Keresés: `admin_search.php` mezők + `labelsKey`; `applyIndexListState` / `applyIndexSearch` / `resolveIndexPageForLastVisited`; `table_search` + header; clear → last-visited oldal; `redirectToIndexList`
- [ ] Form: Select2, Tempus §6, szám §6b, mezőhiba §6c, schema defaults, üres `_ids`, `visible`→`pos`+hr
- [ ] View: `dl` + related tabs + Edit `.record-view-footer-actions` + delete §3 + locale dátum/szám
- [ ] i18n: `__()` + `.po`; `App.adminLocale` = `hu_HU` élesben
- [ ] Sidebar menüpont
- [ ] Ha importált számlálók: `bin/cake rebuild_counter_caches`

---

## 12. Éles projekt indulás (rövid)

1. Másold a `doc/` mappát (+ layout, elementek, JS/CSS, middleware, traits, AppController, AppView, flash, plugins).
2. [uj-projekt.md](uj-projekt.md) 2. szakasz, ha még nincs keretrendszer; ellenőrizd **§0.1**.
3. Állítsd: `App.adminLocale` = **`hu_HU`** (és `APP_ADMIN_LOCALE` ha van .env).
4. Demó Samples/Parents/Cities **ne** legyen kötelező — helyette a valós domain ([crud-utmutato.md](crud-utmutato.md) + **§0–11 itt**).
5. Minden új modulnál: CounterCache + trait + modal 20/ABC + Delete UI + Flash + Tempus + szám MW + mezőhiba + **keresés/index állapot** ([uj-projekt.md](uj-projekt.md) §2.8) **ebből** a fájlból / a specekből.
6. `valtozasok.md` az éles projektben újraindítható; ez a fájl + a többi spec **marad**. Másold a `.cursor/rules/` fájlokat is (`admin-kereses-index-allapot.mdc`, `admin-paginator.mdc`, `penznem-formatcurrency.mdc`, `pos-db-default.mdc`, `auto-dokumentalas.mdc`).

---

## 13. Gyakori hibák (demóból)

| Hiba | Helyes |
|------|--------|
| Élő `COUNT()` a törlésvédelemben | CounterCache `*_count` + `relatedChildrenCountField()` |
| `*_count = count(_ids)` controllerben | CounterCache (+ üres `_ids` → `[]`) |
| HABTM CounterCache a fő Table-en | Through Table + `cascadeCallbacks` |
| Nem törölhető Delete = danger / kattintható blocked | `btn-secondary` / outline-secondary + **disabled** + tooltip |
| Linked delete a saját `#delete-form-{id}`-re megy | `deleteUrl` + `deleteFormPrefix` |
| Modalban az összes gyerek ABC | Max 20 legutóbb módosított, majd ABC |
| Dátum: daterangepicker | Tempus Dominus 6 + `dateFormat` locale |
| Tempus `locale: 'hu'` hardcode | `dateFormat.intl` / `App.adminLocale` |
| Idő picker DE/DU angol UI-n | `useTwentyFourHour` + DateTime `setLocale(intl)` — en_US: AM/PM; hu: 24h |
| AM/PM láthatatlan (csak hover) | Ne írd felül csak a `color`-t kékre a plugin kék gombháttere mellett; állíts `background` + `border`-t is |
| Tempus edit: üres dátum/dátumidő | ISO `data-picker-value` + JeffAdmin `parseInput(moment().toDate())` `setValue`; init előtt ürítsd a locale display inputot |
| View/index dátum `->format('Y.m.d.')` | `LocaleDateParser::format(..., 'date'|'datetime_short'|…)` |
| `1 234` → „must be an integer” | Szám middleware + `looksLikeNumber` (ezres szóköz OK) |
| `Form->setConfig('errorClass')` | `templates.errorClass` (Cake 5.2+ deprecation) |
| Hiba a naptár ikon / „+” mellett | `error => false` + `admin/field_error` wrapper után |
| View Edit `offset-md-2` | `.record-view-footer-actions` (dd oszlop) |
| Flash HTML a tartalom közepén Adminban | Layout végén `<script>` + Notify/SWAL elemek |
| `array_key_exists` ArrayObject-en | `getArrayCopy()` / trait |
| Entity név `Parent` | `ParentRecord` + `setEntityClass` |
| Élesben maradt `adminLocale = en_US` | Vissza `hu_HU` |

---

## 14. Agent: éles DB első üzenet — mit csinálj

Amikor a felhasználó azt mondja: *„éles adatbázissal indulunk”* / *„építsd fel a CRUD-ot a valós táblákra”*:

1. Olvasd **ezt a fájlt** (§0 + §11) + [admin-oldal.md](admin-oldal.md) + [crud-utmutato.md](crud-utmutato.md).
2. Nézd meg a sémát; mapeld a §0 demó→éles táblázat szerint.
3. Ellenőrizd §0.1 keretrendszer fájlokat; hiányzókat pótolj [uj-projekt.md](uj-projekt.md) szerint.
4. Modulonként §11 checklist; UI/UX: §3–7, §6b–6c.
5. Ne kérdezd újra a Flash / Delete / Tempus / szám / mezőhiba / CounterCache szabályokat — **a `doc/` a forrás**.
6. Lényeges új szabály → frissítsd ezt a fájlt + `admin-konvenciok.md` / `valtozasok.md`.
