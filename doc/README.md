# Admin keretrendszer — fejlesztői dokumentáció

Ez a `doc/` mappa **hordozható specifikáció**: másold át egy új CakePHP 5 projektbe, és az agent ebből tudja felépíteni / folytatni az Admin UI-t — **a korábbi projekt kódja nélkül is**.

| Ha… | Olvasd először |
|-----|----------------|
| **Hogyan nézzen ki / működjön egy Admin oldal?** | **[admin-oldal.md](admin-oldal.md)** (teljes kép) |
| Üres / új CakePHP app | [uj-projekt.md](uj-projekt.md) |
| Keretrendszer megvan, új tábla/modul | [crud-utmutato.md](crud-utmutato.md) |
| UI / asset / view részletszabály | [admin-konvenciok.md](admin-konvenciok.md) |
| Fordítás | [i18n.md](i18n.md) |
| Mentéskori szám/dátum | [middleware.md](middleware.md) |

## Fájlindex

| Fájl | Tartalom |
|------|----------|
| **[admin-oldal.md](admin-oldal.md)** | **Teljes kép**: kinézet + működés (index / form / view / dialógus / oszlopok) |
| [uj-projekt.md](uj-projekt.md) | Greenfield: lépésről lépésre Admin keretrendszer nulláról |
| [keretrendszer.md](keretrendszer.md) | Tartós vs. törölhető részek; éles checklist |
| [struktura.md](struktura.md) | Könyvtárak, routing, element inventory |
| [admin-konvenciok.md](admin-konvenciok.md) | Layout, assetek, index/form/view, JS API (részlet) |
| [i18n.md](i18n.md) | `__()` szabály, locale, .po |
| [middleware.md](middleware.md) | Locale szám- és dátumnormalizálás |
| [crud-utmutato.md](crud-utmutato.md) | Új Admin CRUD modul lépései |
| [valtozasok.md](valtozasok.md) | Változásnapló (projekt-specifikus; új projektben nullázható) |

## Rögzített döntések (minden projektben)

- Framework: **CakePHP 5.4+**, Admin URL: `/admin/...`
- UI szövegek: `__('English msgid')`; Admin locale mindig **`hu_HU`**; admin headerben **nincs** nyelvválasztó
- Layout = csak közös CSS/JS; index/form/view oldalspecifikus asseteket a template tölti
- Admin dialógusok: **SweetAlert2** (`MyAdmin.alert` / `alertError` / `confirmDelete`) — soha `window.alert`
- **Index oszlopok:** `string` rugalmas; fix: `id` 4.75 / `pos` 5.5 / `number` 6.5 / `currency` 12 / `count` 5.5 / `boolean|visible` 7.5 / `date` 8.5 / `datetime` 10.5 / `time` 5 rem ([admin-oldal.md](admin-oldal.md) §4.3)
- Index config: `$rowDoubleClickAction`, `$numberDecimals`, `$showIdColumn` / `$showCountColumn` / `$showVisibleColumn` / `$showCreatedColumn` / `$showModifiedColumn`; **`$indexLimit` / `$indexMaxLimit`**; utolsó rekord: session + **`.last-visited`**
- Számok megjelenítése: `LocaleNumberParser::format()` / `formatCount()`; pénznem: **`currencySymbol()` → Ft** (ne HUF); form: `numberFormat` + locale inputmask; mentés: middleware
- View: bake-szerű `dl` + gyerek **tab sheet**; belongsTo/HABTM/name **félkövér link** → AJAX modal (Close/Edit/View/Delete+Swal); `$rowDoubleClickAction` a kapcsolt táblán
- Form: `#name` autofókusz + `pages/form.js`; Select2 „+” ahol egyszerű create; **HABTM multiple** mindkét oldalon (`samples._ids` / `cities._ids`); **belongsTo lista**: `visible` + `pos`/`name` sorrend; `fetchTable()`, ne Association
- Oszlop DEFAULT (`pos`, `visible`, …): **DB séma** + `UsesDatabaseColumnDefaultsTrait` — ne PHP hardcode
- Modal / view kapcsolt listák: **ABC (ASC)** név szerint
- Member (opcionális): `/{lang}/member/...`

## UI forrás

A kinézet egy Pike Admin / Bootstrap 5 admin sablon assetjeiből jön (CSS/JS/plugins/fontawesome/img).  
Konkrét mappaút **nem** kötelező a doksihoz — új projektben másold be a sablon `assets`/`webroot` tartalmát, majd alakítsd a [uj-projekt.md](uj-projekt.md) + [admin-oldal.md](admin-oldal.md) / [admin-konvenciok.md](admin-konvenciok.md) szerint.

## Agent szabályok

1. Módosítás előtt: **[admin-oldal.md](admin-oldal.md)** (célkép), majd a releváns részlet-doksik.
2. Új projekt / hiányzó layout → [uj-projekt.md](uj-projekt.md) 2. szakasz.
3. Kód kövesse a konvenciókat (`__()`, asset szabály, middleware, view tabs, oszlopszélességek).
4. Lényeges változás után frissítsd a `valtozasok.md`-t **és** az érintett spec fájlokat (különösen `admin-oldal.md` / `admin-konvenciok.md`, ha a kinézet vagy működés változik).
5. Domain DB sémát ne keverd a keretrendszer-doksi közé (teszt táblák eldobhatók).
6. A felhasználónak ne kelljen újra elmondania a fenti döntéseket — a `doc/` a forrás.
