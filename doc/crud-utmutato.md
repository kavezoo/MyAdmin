# Új Admin CRUD modul — útmutató

Új tábla/controller felvételekor kövesd ezt a sorrendet.  
Ha még **nincs** Admin layout / middleware / element a projektben: előbb [uj-projekt.md](uj-projekt.md).

**Célkép (kinézet + működés):** [admin-oldal.md](admin-oldal.md).  
**Éles DB / demó → tartós minták:** [minta-tanulsagok.md](minta-tanulsagok.md) (**§0 playbook** + §11 checklist).  
UI részletek: [admin-konvenciok.md](admin-konvenciok.md). Fordítás: [i18n.md](i18n.md).

## 1. Modell

```bash
bin/cake bake model TableName --no-test --no-fixture -f
```

Utána ellenőrizd:

- Asszociációk helyesek-e (bake néha téves self-ref-et csinál `*_id`-re)
- Reserved entity név (pl. Parent) → átnevezés + `setEntityClass`
- Számláló mezők: **CounterCache** + `relatedChildrenCountField()`; create-nél `0` ha NOT NULL / nincs DB DEFAULT
- HABTM through: CounterCache a through Table-en + `cascadeCallbacks`; join `pos`/`visible` → DB default
- `beforeMarshal` `$data` = **ArrayObject** → `array_key_exists` helyett `getArrayCopy()` ([admin-konvenciok.md](admin-konvenciok.md))
- Üres / hiányzó oszlop DEFAULT-tal: `UsesDatabaseColumnDefaultsTrait` (ne PHP `1000` / `true`)

## 2. Controller (`src/Controller/Admin/`)

- Extend: `App\Controller\Admin\AppController`
- Actionök: `index`, `add`, `edit`, `view`, `delete`, + `recordGet` (JSON a lista modalhoz)
- Select2 „+” mezőnként: `select2Create…` JSON action(ök) — single és multiple is; válasz `{ success, id, text }` (hiba: `{ success: false, message }`). Ha a cél entitás túl összetett → csak multiple választás, nincs „+”.
- HABTM form: `related._ids` + `associated` + üres `_ids` = `[]` + `*_count` frissítés — [admin-konvenciok.md](admin-konvenciok.md) → „HABTM — multiple Select2”
- `view`: `contain` a belongsTo + gyerek asszociációkra (a tab sheet-ekhez kell az adat)
- `add` / `edit` → `$this->render('form')`; mentés **try/catch** + Flash ([admin-oldal.md](admin-oldal.md) → űrlap)
- `$this->set('title', __('…'))` + breadcrumb
- Lista: `applyIndexListState('Alias')` → `applyIndexSearch` → `resolveIndexPageForLastVisited` (clear után) → `$this->paginate($query, $paginateOptions)` — controller tetején `$indexLimit` / `$indexMaxLimit`; `setLastVisitedForIndex('Alias')`
- Save után: `return $this->redirectToIndexList('Alias');` (sort / page / `q` sessionből)
- **Ne** legyen előre beállított `orderBy` az index query-n — sort az URL-ből / sessionből (`?sort=&direction=`)
- Új model: `config/admin_search.php` — szöveges `fields` + `labelsKey` (index + globális kereső / Search modal)
- JSON endpointok: raw `Response` + `json_encode`, DashedRoute action nevek (`record-get`)

### `recordGet` elvárt JSON (modal)

Legalább: siker flag + rekord mezők (és ha kell, beágyazott belongsTo név).  
A frontend a `MyAdmin.config.recordFieldLabels` kulcsai szerint rajzolja ki.

**belongsToMany / hasMany listák a modalban:** az **utoljára módosított max. 20** gyerek (`modified DESC` + limit), megjelenítés **ABC (name ASC)**, klikkable linkek (linked modal):

```php
$entity = $this->Model->get($id, contain: [
    'Related' => $this->containRelatedForModal('Related'),
]);

// recordGet JSON — ne implode string:
'related' => $this->relatedNameLinksForModal($entity->related ?? []),
```

Index: `relatedLinkFields` + `entityFieldLabels` + `admin/modal_linked_record_view`.  
A view tab / teljes lista továbbra is lehet ABC ASC limit nélkül.
## 3. Templatek (`templates/Admin/{Name}/`)

| Fájl | Tartalom |
|------|----------|
| `index.php` | **Teljes** lista-minta: `admin/table_search`, `admin/index_pagination` (First…Last), `admin/index_footer`, `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`, típusoszlopok, sort, `.last-visited`, modal/SweetAlert delete, `pages/index` JS/CSS + config |
| `form.php` | Add/edit közös form; `#name` **autofocus**; HABTM: `related._ids` multiple Select2; „+” csak ha egyszerű create lehetséges |
| `view.php` | Adatlap + related tabs; `$rowDoubleClickAction`; `.record-modal-link`; `pages/index` JS + linked modal |

**Törlés (gyerekvédelem):** `PreventsDeleteWithChildrenTrait` + `relatedChildrenCountField()` — a szám a **CounterCache** `*_count` mezőből jön (ne élő `COUNT()`). HABTM: through Table CounterCache + `cascadeCallbacks => true`. UI: törölhető = danger + Swal question; **nem törölhető** = `btn-secondary` / `btn-outline-secondary` + **disabled** + tooltip ([minta-tanulsagok.md](minta-tanulsagok.md) §3). Trait: `App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait`.

**Dátum / idő mezők a formon:** Tempus Dominus 6 (`.js-tempus-picker`, JeffAdmin5 formátumok) — [minta-tanulsagok.md](minta-tanulsagok.md) §6. Mentés: [middleware.md](middleware.md).

**Flash:** alap Notify toast; fontos modal üzenet: `$this->flashSwal('success'|'error'|…, $msg)` — [minta-tanulsagok.md](minta-tanulsagok.md) §5.

**Oszlop DEFAULT-ok (`pos`, `visible`, `logikai`, …):** az érték a **adatbázis DEFAULT**-jából jön. Controller / Select2 / `beforeSave` **ne** hardkódoljon. Trait: `UsesDatabaseColumnDefaultsTrait` + controller `newEntityWithSchemaDefaults()`. Üres formmező → `beforeMarshal` unset → DB default. `beforeMarshal` ArrayObject: `getArrayCopy()`. NOT NULL számlálók DEFAULT nélkül (`*_count`): ideiglenesen `0` a controllerben (CounterCache a join/gyerek mentéskor frissít).

**Form fókusz:** `#name` (vagy a fő címke mező) `autofocus` + `form.js` focus — [admin-konvenciok.md](admin-konvenciok.md) → „Name mező”.

Elementek:

- `admin/index_footer` (card lábléc: `index_counter` + First…Last lapozó)
- `admin/index_pagination` (fejléc lapozó; a footer is ezt hívja)
- `admin/modal_record_view` (kötelező indexhez)
- `admin/modal_linked_record_view` (ha van kapcsolt link)
- `admin/view_related_tabs` (kötelező view-hoz, ha van hasMany / belongsToMany)

**View:** belongsTo / HABTM nevek **félkövér** `.record-modal-link` → AJAX `#modalLinkedRecordView` (Close / Edit / View details / Delete+Swal).  
Kapcsolt tab: `.related-records-table` + name link + `$rowDoubleClickAction` (`modal`/`edit`/`none`).  
Részletek: [admin-konvenciok.md](admin-konvenciok.md) → „View (megnézés) UI”.

**Ne** legyen „egyszerűsített” index (modal / sort / típusoszlopok nélkül) — minden modul ugyanazt a keretrendszer-viselkedést kapja.

### Index tooltip / `__()` figyelmeztetés

```php
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
// A záró ) kötelező — hiányzik → ParseError a templateben.
```

## 4. Menü

`templates/element/admin/sidebar.php` — új menüpont.

## 5. Speciális modul: Setups (típusos EAV)

Ha a projektnek kell **alkalmazásbeállítás** tábla: kövesd a **[setups.md](setups.md)** speceket (ne találj ki új sémát).

Röviden:

- Schema: `config/schema/setups.sql`
- `SetupValue` + típusfüggő form widgetek + slug **`_`** elválasztóval (nem `-`)
- Teljes Admin CRUD + `admin_search` + sidebar Settings
- Rule: `.cursor/rules/setups-eav.mdc`

## 6. Asset szabály

- Layoutba **ne** tegyél oldalspecifikus plugint
- Form Select2 Parent lista: `visible = true`, order `pos` ASC + `name` ASC (edit: aktuális szülő akkor is a listában)
- Index / view: `pages/index` (+ indexen config `scriptBlock`)
- Form: csak a használt pluginek (Select2 / date / editor igény szerint) — **`pages/form` JS mindig** (name fókusz)
- Select2: single **és** multiple; „+” ahol egyszerű create van — [admin-konvenciok.md](admin-konvenciok.md) → Select2 / HABTM multiple
- HABTM: mindkét oldal formján multiple Select2 (`Cities` ↔ `Samples`)
- Hibák / megerősítések: **SweetAlert** (`MyAdmin.alertError` / `confirmDelete`) — ne `window.alert`
- Minden új UI szöveg **`__('English')`**; fordítás: `hu_HU/default.po`
- Szám / dátum: middleware normalizál — [middleware.md](middleware.md); form számmezők: `numberFormat` + `.js-input-decimal` / `.js-input-integer`
- Pénz cellák: `LocaleNumberParser::formatCurrency()` (HUF, ICU) — [admin-konvenciok.md](admin-konvenciok.md)

## 7. Dokumentáció frissítés

1. `doc/valtozasok.md` — mit és miért
2. `doc/struktura.md` — ha új keretrendszer-fájl (nem minden domain modulnál kötelező)
3. `doc/admin-konvenciok.md` / `uj-projekt.md` / `setups.md` — ha UI/asset / Setups szabály változik

Domain DB sémát ne dokumentáld a keretrendszer-doksiba, hacsak a csapat külön nem kéri.

## Referencia-viselkedés

A „referencia” = az **első teljes** Admin CRUD a projektben (index + form + view + recordGet).  
Új modulok ezt a **viselkedést** kövessék (nem feltétlenül ugyanazokat a mezőket).
