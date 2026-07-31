# Új Admin CRUD modul — útmutató

Új tábla/controller felvételekor kövesd ezt a sorrendet.  
Ha még **nincs** Admin layout / middleware / element a projektben: előbb [uj-projekt.md](uj-projekt.md).

**Célkép (kinézet + működés):** [admin-oldal.md](admin-oldal.md).  
UI részletek: [admin-konvenciok.md](admin-konvenciok.md). Fordítás: [i18n.md](i18n.md).

## 1. Modell

```bash
bin/cake bake model TableName --no-test --no-fixture -f
```

Utána ellenőrizd:

- Asszociációk helyesek-e (bake néha téves self-ref-et csinál `*_id`-re)
- Reserved entity név (pl. Parent) → átnevezés + `setEntityClass`
- Számláló mezők: `allowEmptyString` + default 0 create-nél
- HABTM through extra kötelező mezők → `beforeSave` / `beforeMarshal` defaultok
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
- Lista: `$this->paginate($query, $this->indexPaginateOptions(['sortableFields' => [...]]))` — controller tetején `$indexLimit` / `$indexMaxLimit`; `setLastVisitedForIndex('Alias')`
- **Ne** legyen előre beállított `orderBy` az index query-n — sort az URL-ből (`?sort=&direction=`)
- JSON endpointok: raw `Response` + `json_encode`, DashedRoute action nevek (`record-get`)

### `recordGet` elvárt JSON (modal)

Legalább: siker flag + rekord mezők (és ha kell, beágyazott belongsTo név).  
A frontend a `MyAdmin.config.recordFieldLabels` kulcsai szerint rajzolja ki.

**belongsToMany / hasMany listák a modalban (és view tabon / fő dl „list” sor):** a megjelenített nevek **ABC (ASC)** sorrendben, és **klikkable linkek** (második / linked modal):

```php
$entity = $this->Model->get($id, contain: [
    'Related' => function ($q) {
        return $q->orderBy(['Related.name' => 'ASC']);
    },
]);

// recordGet JSON — ne implode string:
'related' => array_map(
    fn ($row) => ['id' => $row->id, 'name' => $row->name],
    $entity->related ?? []
);
```

Index: `relatedLinkFields` + `entityFieldLabels` + `admin/modal_linked_record_view`.  
Ne a join tábla `id` / beszúrási sorrendje legyen a megjelenítési sorrend, hacsak a domain azt nem követeli meg.
## 3. Templatek (`templates/Admin/{Name}/`)

| Fájl | Tartalom |
|------|----------|
| `index.php` | **Teljes** lista-minta: `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`, típusoszlopok, sort, `.last-visited`, modal/SweetAlert delete, `pages/index` JS/CSS + config |
| `form.php` | Add/edit közös form; `#name` **autofocus**; HABTM: `related._ids` multiple Select2; „+” csak ha egyszerű create lehetséges |
| `view.php` | Adatlap + related tabs; `$rowDoubleClickAction`; `.record-modal-link`; `pages/index` JS + linked modal |

**Törlés (gyerekvédelem):** ha `*_count > 0` (vagy élő gyerekszám), a model `beforeDelete` **megtagadja** a törlést (`Cannot delete this record because it has related child records.`). Index/view: törlés gomb **disabled** + tooltip. Üres gyereknél törölhető; HABTM join sorok `dependent => true` (csak ha a törlés engedélyezett). Rejtett delete: **`Form->create` form** (`#delete-form-{id}`), ne `postLink` (az `id` az `<a>`-ra kerülne). Trait: `App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait`.

**Oszlop DEFAULT-ok (`pos`, `visible`, `logikai`, …):** az érték a **adatbázis DEFAULT**-jából jön. Controller / Select2 / `beforeSave` **ne** hardkódoljon. Trait: `UsesDatabaseColumnDefaultsTrait` + controller `newEntityWithSchemaDefaults()`. Üres formmező → `beforeMarshal` unset → DB default. `beforeMarshal` ArrayObject: `getArrayCopy()`. NOT NULL számlálók DEFAULT nélkül (`*_count`): ideiglenesen `0` a controllerben.

**Form fókusz:** `#name` (vagy a fő címke mező) `autofocus` + `form.js` focus — [admin-konvenciok.md](admin-konvenciok.md) → „Name mező”.

Elementek:

- `admin/index_pagination`
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

## 5. Asset szabály

- Layoutba **ne** tegyél oldalspecifikus plugint
- Form Select2 Parent lista: `visible = true`, order `pos` ASC + `name` ASC (edit: aktuális szülő akkor is a listában)
- Index / view: `pages/index` (+ indexen config `scriptBlock`)
- Form: csak a használt pluginek (Select2 / date / editor igény szerint) — **`pages/form` JS mindig** (name fókusz)
- Select2: single **és** multiple; „+” ahol egyszerű create van — [admin-konvenciok.md](admin-konvenciok.md) → Select2 / HABTM multiple
- HABTM: mindkét oldal formján multiple Select2 (`Cities` ↔ `Samples`)
- Hibák / megerősítések: **SweetAlert** (`MyAdmin.alertError` / `confirmDelete`) — ne `window.alert`
- Minden új UI szöveg **`__('English')`**; fordítás: `hu_HU/default.po`
- Szám / dátum: middleware normalizál — [middleware.md](middleware.md); form számmezők: `numberFormat` + `.js-input-decimal` / `.js-input-integer`
- Pénznem cellák: `LocaleNumberParser::currencySymbol()` → **Ft** (ne `HUF`) — [admin-konvenciok.md](admin-konvenciok.md)

## 6. Dokumentáció frissítés

1. `doc/valtozasok.md` — mit és miért
2. `doc/struktura.md` — ha új keretrendszer-fájl (nem minden domain modulnál kötelező)
3. `doc/admin-konvenciok.md` / `uj-projekt.md` — ha UI/asset szabály változik

Domain DB sémát ne dokumentáld a keretrendszer-doksiba, hacsak a csapat külön nem kéri.

## Referencia-viselkedés

A „referencia” = az **első teljes** Admin CRUD a projektben (index + form + view + recordGet).  
Új modulok ezt a **viselkedést** kövessék (nem feltétlenül ugyanazokat a mezőket).
