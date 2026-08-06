# Új projekt — séma alapján kötelező megoldások (agent playbook)

**Cél:** új CakePHP Admin projektnél / éles DB-re építésnél az agent **ne felejtse el** a keretrendszer eddigi megoldásait. Minden funkciót a **valós adatbázis séma** és a **táblakapcsolatok** szerint kell beépíteni — nem a demó Samples mezőneveket másolni.

Kapcsolódó: [uj-projekt.md](uj-projekt.md) (greenfield lépések), [minta-tanulsagok.md](minta-tanulsagok.md) (§0), [crud-utmutato.md](crud-utmutato.md), [README.md](README.md).  
Cursor rule: `.cursor/rules/uj-projekt-sema.mdc` (`alwaysApply`).

---

## 0. Agent indítás — kötelező sorrend

1. **Olvasd a DB sémát** (migration / SQL dump / `DESCRIBE` / Cake schema) — oszlopok, FK, unique, DEFAULT, indexek.
2. **Rajzold fel a kapcsolatokat:** belongsTo / hasMany / belongsToMany (+ through).
3. **Ellenőrizd a keretrendszer fájlokat** — hiányzó: [uj-projekt.md](uj-projekt.md) §2 + [minta-tanulsagok.md](minta-tanulsagok.md) §0.1.
4. **Modulonként** kövesd az alábbi **séma → megoldás** mátrixot + [crud-utmutato.md](crud-utmutato.md).
5. **Checklist** (e fájl §3) modulonként pipáld.
6. **Dokumentálj** (`valtozasok.md` + érintett specek) — `auto-dokumentalas.mdc`.

> Demó `Samples` / `Parents` / `Cities` = minta. Élesben a **viselkedés** kell, a tábla/mezőnevek a sémából jönnek.

---

## 1. Séma → megoldás mátrix

### 1.1 Oszloptípusok

| Séma jel | Admin megoldás | Doc / rule |
|----------|----------------|------------|
| `VARCHAR` / `TEXT` (név, cím) | Form `#name` / első azonosító + `autofocus`; index `string` oszlop | admin-konvenciok |
| Translate-elt szöveg (`i18n` EAV) | Form nyelvi TAB-ok; index: `AdminTranslate` + `indexPaginateOptionsFor` | form-i18n-tabs, i18n, `admin-form-i18n-tabs`, `admin-translate-search-sort` |
| Ország láthatóság (TAB/login) | `country_visibilities` (önref.); aktív = `Users.country_id`; **saját + extras** | country-visibilities, countries-admin |
| Eseménynapló | `event_logs`; HTTP + Behavior + login/logout; officer ország-kereső | event-logs |
| `INT` / `BIGINT` (nem FK) | `.js-input-integer` + `numberFormat`; mentés szám MW | middleware |
| `DECIMAL` / `FLOAT` / pénz | `.js-input-decimal` + `formatCurrency` megjelenítés | middleware, `penznem-formatcurrency` |
| `DATE` / `TIME` / `DATETIME` | Tempus Dominus 6 + `dateFormat`; mentés dátum MW | middleware, admin-konvenciok |
| `BOOLEAN` / `TINYINT(1)` | form-switch; index boolean oszlop | admin-oldal |
| `visible` | Switch; lista végén `visible` → `pos`; Countries: visible-only szűrő | admin-konvenciok, countries-admin |
| `pos` | PHP-ból **békén hagyjuk** (DB **DEFAULT `1000`**); ne állítsuk / ne növeljük — **majd a user** a formon | `pos-db-default` |
| `*_count` | **CounterCache** (ne élő COUNT) | minta-tanulsagok, crud-utmutato |
| `created` / `modified` | Timestamp behavior; form header editnél | — |
| Oszlop DEFAULT (DB) | `UsesDatabaseColumnDefaultsTrait` + séma DEFAULT | minta-tanulsagok |

### 1.2 Kapcsolatok

| Kapcsolat | Index / lista | Form | View | Törlés |
|-----------|---------------|------|------|--------|
| **belongsTo** | Oszlop / join; Translate assoc → locale sort | Select2 lista: `visible` + `pos`/`name`; opcionális „+” ha egyszerű create | Félkövér link → AJAX modal; Edit/View = **szülő** CRUD URL + szülő id (`admin-linked-modal-urls`) | Szülőn CounterCache a gyerekből |
| **hasMany** | Szülőn `*_count` oszlop | — | Gyerek **tab sheet** (üres is) | `PreventsDeleteWithChildrenTrait` ha `*_count > 0` → disabled Delete |
| **belongsToMany** | Mindkét oldalon `*_count` | **Mindkét** formon multiple Select2; üres `_ids` → `[]`; through + `cascadeCallbacks` + `saveStrategy: replace` | Tab / modal lista | Through CounterCache mindkét irány |
| **Nincs gyerek** | — | — | — | Delete mindig danger + Swal |

### 1.3 Translate / ország / locale

| Feltétel | Megoldás |
|----------|----------|
| Table-en Translate (`name`, …) | `setFormLanguageTabs()` + `getWithTranslations()` + `form_language_fields`; default locale root mezők |
| Nyelvi TAB tooltip | Összes **`visible`** ország az adott nyelven (`FormLanguages::tabs()` → `countries[]`), egymás alatt |
| TAB váltás | Fókusz az **adott nyelv** name mezőjére (`data-name-target` → `#name` / `#name-hu-hu`) |
| Index keresés/sort | `AdminSearch` + `translationField()`; ORDER BY: translation mező + kanonikus — **tilos `COALESCE` alias** |
| Cake 5.3+ | `getBehavior('Translate')->setLocale()` / `translationField()` — **tilos** Table proxy |
| Form error opció | Soha `'error' => true` (bool); nem-default tab: `'error' => false`; nem-default: `'required' => false` |
| Countries referencia | visible-only switch, oszlopsorrend, ACL — [countries-admin.md](countries-admin.md) |

### 1.4 Form UX (minden add/edit)

| Szabály | Hol |
|---------|-----|
| `#form-horizontal` + `pages/form.js` | Minden form |
| Kötelező mező: piros `*` a label **előtt**, szóköz nélkül | `FormHelper::adminLabel` / `requiredMark` — validator alapján |
| Mentetlen leave Swal | dirty → `confirmLeave`; clean → nincs kérdés |
| Mezőhiba a control **alatt** | AppView Admin templates + `field_error` összetett widgetnél |
| `visible` → `pos` a form végén; **`<hr>` mindig a `visible` fölött** | Ha van `visible` / `pos` oszlop — rule: `admin-form-visible-hr.mdc` |
| Flash | Simple Notify toast (Admin + login) |

### 1.5 Index UX (minden lista)

| Szabály | Hol |
|---------|-----|
| Session `Admin.indexState` + last-visited | AppController helperök |
| Keresés: `config/admin_search.php` | fields + labelsKey |
| Lapozó First…Last + counter footer | `admin-paginator` |
| Delete: danger / disabled+tooltip ha gyerek | `admin-delete-blocked` |
| Oszlopszélességek | admin-oldal §4.3 (`count` min 15rem, …) |

---

## 2. Új CRUD modul — séma-vezérelt checklist

Minden új táblánál:

```
[ ] Séma elolvasva (oszlopok + FK + DEFAULT)
[ ] Kapcsolatok: belongsTo / hasMany / HABTM felvéve Table-en
[ ] Entity + Table + Admin Controller + index/form/view
[ ] CounterCache / *_count ahol van gyerek
[ ] PreventsDeleteWithChildrenTrait ha kell
[ ] Translate? → form TAB + index locale search/sort
[ ] Dátum/szám oszlopok? → Tempus / inputmask + middleware mezők
[ ] belongsTo Select2 (+ „+” ha egyszerű)
[ ] HABTM? → mindkét oldal multiple Select2
[ ] Form: adminLabel, #form-horizontal, visible/pos, unsaved
[ ] Index: admin_search mezők, oszloptípusok, footer
[ ] View: dl + related tabs
[ ] .po msgid-ek + cache clear
[ ] Doc: valtozasok.md (+ specek ha új minta)
```

---

## 3. Greenfield — „minden eddigi megoldás” csomag

Új üres projektbe a `doc/` + `.cursor/rules/` másolása után az agent építse fel:

1. Layout, middleware, AppView, MyAdmin JS API — [uj-projekt.md](uj-projekt.md)
2. Countries + Continents + i18n seed (ha kell ország/locale) — [i18n.md](i18n.md), [countries-admin.md](countries-admin.md)
3. CakeDC auth baseline — [users-auth.md](users-auth.md)
4. Domain CRUD-ok a **séma szerint** (§1–2)
5. Opcionális: Setups EAV — [setups.md](setups.md)

**Ne** hagyd ki: kötelező csillag, nyelvi TAB + tooltip + name fókusz, Translate index sort, unsaved leave, CounterCache, pos DB default, form hibák.

---

## 4. Gyakori hibák (ne ismételd)

| Hiba | Helyes |
|------|--------|
| Demó mezőnevek éles sémára | Séma oszlopnevek |
| `COALESCE(...)` ORDER BY | `AdminTranslate::orderFieldList` |
| `$table->setLocale()` | `$table->getBehavior('Translate')->setLocale()` |
| `'error' => true` Form control | omit / `'error' => false` |
| `pos` PHP-ban növelgetve | DB DEFAULT |
| Élő `COUNT(*)` lista oszlopra | CounterCache `*_count` |
| Nyelvi TAB fókusz mindig `#name` | `data-name-target` az adott locale id-jára |
| Tooltip `span` a tab gombban | `js-hover-only-tooltip` + `initHoverOnlyTooltips` (ne `title` a gombon) |

---

## 5. Doc / rule térkép (gyors)

| Téma | Doc | Rule |
|------|-----|------|
| Ez a playbook | **uj-projekt-sema-playbook.md** | `uj-projekt-sema.mdc` |
| Greenfield lépések | uj-projekt.md | — |
| Demó→éles | minta-tanulsagok.md | — |
| Form i18n TAB | form-i18n-tabs.md | admin-form-i18n-tabs.mdc |
| Kötelező `*` | admin-konvenciok.md | admin-form-required.mdc |
| Unsaved leave | admin-konvenciok.md | admin-form-unsaved.mdc |
| Translate search/sort | i18n.md | admin-translate-search-sort.mdc |
| Countries index | countries-admin.md | admin-countries-index.mdc |
| Auth | users-auth.md | users-auth.mdc |
| Setups | setups.md | setups-eav.mdc |
| Auto doksi | — | auto-dokumentalas.mdc |
