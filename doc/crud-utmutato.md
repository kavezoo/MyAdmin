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

## 2. Controller (`src/Controller/Admin/`)

- Extend: `App\Controller\Admin\AppController`
- Actionök: `index`, `add`, `edit`, `view`, `delete`, + `recordGet` (JSON a lista modalhoz)
- Select2 „+” mezőnként: `select2Create…` JSON action(ök) — single és multiple is; válasz `{ success, id, text }`
- `view`: `contain` a belongsTo + gyerek asszociációkra (a tab sheet-ekhez kell az adat)
- `add` / `edit` → `$this->render('form')`
- `$this->set('title', __('…'))` + breadcrumb
- Lista: `$this->paginate($query, ['limit' => 10, 'sortableFields' => [...]])`
- **Ne** legyen előre beállított `orderBy` az index query-n — sort az URL-ből (`?sort=&direction=`)
- JSON endpointok: raw `Response` + `json_encode`, DashedRoute action nevek (`record-get`)

### `recordGet` elvárt JSON (modal)

Legalább: siker flag + rekord mezők (és ha kell, beágyazott belongsTo név).  
A frontend a `MyAdmin.config.recordFieldLabels` kulcsai szerint rajzolja ki.

**belongsToMany / hasMany listák a modalban (és view tabon):** a megjelenített nevek / sorok **ABC (ASC)** sorrendben legyenek:

```php
$entity = $this->Model->get($id, contain: [
    'Related' => function ($q) {
        return $q->orderBy(['Related.name' => 'ASC']);
    },
]);
```

Ne a join tábla `id` / beszúrási sorrendje legyen a megjelenítési sorrend, hacsak a domain azt nem követeli meg.
## 3. Templatek (`templates/Admin/{Name}/`)

| Fájl | Tartalom |
|------|----------|
| `index.php` | **Teljes** lista-minta: `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`, típusoszlopok, sort, modal/SweetAlert delete, `pages/index` JS/CSS + config |
| `form.php` | Add/edit közös form, csak a **valós DB mezők**; Select2 single **és** multiple mellett `.btn-select2-add` + modal + create JSON action |
| `view.php` | Bake-szerű adatlap (`dl.record-view-fields`) + gyerek asszociációk **tab sheet**-ben (`admin/view_related_tabs`); CSS: `pages/index` |

Elementek:

- `admin/index_pagination`
- `admin/modal_record_view` (kötelező indexhez)
- `admin/modal_linked_record_view` (ha van kapcsolt link)
- `admin/view_related_tabs` (kötelező view-hoz, ha van hasMany / belongsToMany)

**View:** belongsTo a fő adatlapon; gyerek táblák index-szerű táblával tabonként; üres tab is megjelenik (`__('No related records.')`).  
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
- Index / view: `pages/index` (+ indexen config `scriptBlock`)
- Form: csak a használt pluginek (Select2 / date / editor igény szerint)
- Select2: single **és** multiple „+” gomb — [admin-konvenciok.md](admin-konvenciok.md) → „Select2 +”
- Hibák / megerősítések: **SweetAlert** (`MyAdmin.alertError` / `confirmDelete`) — ne `window.alert`
- Minden új UI szöveg **`__('English')`**; fordítás: `hu_HU/default.po`
- Szám / dátum: middleware normalizál — [middleware.md](middleware.md); form számmezők: `numberFormat` + `.js-input-decimal` / `.js-input-integer` (locale szerinti maszk)

## 6. Dokumentáció frissítés

1. `doc/valtozasok.md` — mit és miért
2. `doc/struktura.md` — ha új keretrendszer-fájl (nem minden domain modulnál kötelező)
3. `doc/admin-konvenciok.md` / `uj-projekt.md` — ha UI/asset szabály változik

Domain DB sémát ne dokumentáld a keretrendszer-doksiba, hacsak a csapat külön nem kéri.

## Referencia-viselkedés

A „referencia” = az **első teljes** Admin CRUD a projektben (index + form + view + recordGet).  
Új modulok ezt a **viselkedést** kövessék (nem feltétlenül ugyanazokat a mezőket).
