# Admin keretrendszer — fejlesztői dokumentáció

Ez a `doc/` mappa **hordozható specifikáció**: másold át egy új CakePHP 5 projektbe, és az agent ebből tudja felépíteni / folytatni az Admin UI-t — **a korábbi projekt kódja nélkül is**.

| Ha… | Olvasd először |
|-----|----------------|
| **Új projekt / éles DB — séma szerint minden megoldás** | **[uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md)** (+ rule `uj-projekt-sema.mdc`) |
| **Hogyan nézzen ki / működjön egy Admin oldal?** | **[admin-oldal.md](admin-oldal.md)** (teljes kép) |
| **Login / regisztráció / profil (CakeDC)** | **[users-auth.md](users-auth.md)** |
| **Demó mintából éles projekt / éles DB** | **[minta-tanulsagok.md](minta-tanulsagok.md)** (§0 playbook + §0.1 fájlok + §6–6c form + §11 checklist + §14 agent) |
| Üres / új CakePHP app | [uj-projekt.md](uj-projekt.md) |
| Keretrendszer megvan, új tábla/modul | [crud-utmutato.md](crud-utmutato.md) |
| Típusos beállítások (Setups EAV) | **[setups.md](setups.md)** |
| **CakeDC auth (login / regisztráció / profil)** | **[users-auth.md](users-auth.md)** |
| **Kódba nyúlás — API cheat sheet** | **[MyAdminUsage.md](MyAdminUsage.md)** (`Setup::get`, …) |
| UI / asset / view részletszabály | [admin-konvenciok.md](admin-konvenciok.md) |
| **Countries Admin lista** | **[countries-admin.md](countries-admin.md)** |
| **Languages Admin** | **[languages-admin.md](languages-admin.md)** |
| **Ország→ország láthatóság** | **[country-visibilities.md](country-visibilities.md)** |
| **Eseménynapló** | **[event-logs.md](event-logs.md)** |
| **Login nyelv** | **[login-language.md](login-language.md)** |
| **Tagság jelentkezés** | **[membership.md](membership.md)** — greenfield: **[membership-greenfield.md](membership-greenfield.md)** |
| Fordítás | [i18n.md](i18n.md) |
| **Form nyelvi TAB-ok** | **[form-i18n-tabs.md](form-i18n-tabs.md)** |
| Mentéskori szám/dátum | [middleware.md](middleware.md) |

## Fájlindex

| Fájl | Tartalom |
|------|----------|
| **[admin-oldal.md](admin-oldal.md)** | **Teljes kép**: kinézet + működés (index / form / view / dialógus / oszlopok) |
| **[minta-tanulsagok.md](minta-tanulsagok.md)** | Demó → éles DB: playbook, konfig (`adminLocale`), CounterCache, Delete, Flash, Tempus/szám/mezőhiba, view footer, checklist |
| [uj-projekt.md](uj-projekt.md) | Greenfield: lépésről lépésre Admin keretrendszer nulláról |
| **[uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md)** | **Séma + kapcsolatok → kötelező megoldások** (új/éles projekt agent checklist) |
| [keretrendszer.md](keretrendszer.md) | Tartós vs. törölhető részek; éles checklist |
| [struktura.md](struktura.md) | Könyvtárak, routing, element inventory |
| [admin-konvenciok.md](admin-konvenciok.md) | Layout, assetek, index/form/view, JS API (részlet) |
| [i18n.md](i18n.md) | `__()` szabály, locale, .po, Translate keresés/sort |
| **[form-i18n-tabs.md](form-i18n-tabs.md)** | **Form nyelvi TAB-ok** + ország tooltip + Cake 5.3 buktatók |
| **[countries-admin.md](countries-admin.md)** | **Countries index**: visible-only, oszlopsorrend, CSS, i18n |
| **[languages-admin.md](languages-admin.md)** | **Languages Admin**: login UI locale CRUD, ACL, visible-only |
| **[country-visibilities.md](country-visibilities.md)** | **Saját + plusz nyelvek** (`country_visibilities`); TAB/login |
| **[event-logs.md](event-logs.md)** | **Eseménynapló** (`event_logs`); login/CRUD/HTTP; ország ACL |
| **[login-language.md](login-language.md)** | Login **nyelv** Select2; `languages` + i18n nevek; register = ország |
| **[membership.md](membership.md)** | Tagság: `new` → profil → clubpresident → `member` |
| **[membership-greenfield.md](membership-greenfield.md)** | **Tagság + role panelek greenfield** — lépéssorrend, séma, ACL, checklist |
| [middleware.md](middleware.md) | Locale szám- és dátumnormalizálás |
| [crud-utmutato.md](crud-utmutato.md) | Új Admin CRUD modul lépései |
| [setups.md](setups.md) | Típusos Setups (EAV) modul — widgetek, slug, JSON/tömb |
| **[users-auth.md](users-auth.md)** | **CakeDC Users + role panelek** — baseline új projektekhez; login/role képlékeny |
| **[MyAdminUsage.md](MyAdminUsage.md)** | **Programozói használat** — `Setup::get()` és társai (gyakorlati példák) |
| [valtozasok.md](valtozasok.md) | Változásnapló (projekt-specifikus; új projektben nullázható) |

## Cursor rules (`.cursor/rules/`) — agent playbook

| Rule | Tartalom |
|------|----------|
| `setups-eav.mdc` | Setups EAV típus / slug / widget |
| `users-auth.mdc` | CakeDC auth + role panelek — baseline; role/login projektenként változhat |
| `auto-dokumentalas.mdc` | Minden változás után `doc/` (+ tartós mintánál rule) frissítés |
| `admin-kereses-index-allapot.mdc` | Keresés, session index, last-visited, Search UI |
| `admin-paginator.mdc` | First…Last lapozó + counter/footer |
| `penznem-formatcurrency.mdc` | Pénz = `formatCurrency()` (HUF, ICU) |
| `admin-form-unsaved.mdc` | Mentetlen add/edit form → leave Swal |
| `admin-form-i18n-tabs.mdc` | Form nyelvi TAB + tooltip + Cake 5.3 Translate API |
| `admin-translate-search-sort.mdc` | Index keresés/sort UI locale (`AdminTranslate`, tilos COALESCE) |
| `admin-countries-index.mdc` | Countries lista: visible-only, oszlopok, count szélesség |
| `admin-form-required.mdc` | Kötelező mező piros `*` (adminLabel) |
| `uj-projekt-sema.mdc` | **alwaysApply** — új/éles projekt: séma + kapcsolatok szerint minden megoldás |
| `pos-db-default.mdc` | `pos` mindig DB DEFAULT |
| `membership-greenfield.mdc` | Tagság + role panelek greenfield — `membership-greenfield.md` |
| `panel-member-index.mdc` | President / Clubpresident taglista index minta |

Új projektbe másold a `doc/` **és** a `.cursor/rules/` mappát.

## Rögzített döntések (minden projektben)

- Framework: **CakePHP 5.4+**, Admin URL: `/admin/...`
- **Terméknév (látható brand):** `config/app.php` → **`App.Name`** / **`App.Title`** (alap: **PipeOffice**; env: `APP_NAME` / `APP_TITLE`); login H1 + böngésző title + welcome — `App\Utility\AppBrand`
- UI szövegek: `__('English msgid')`; panel locale: **bejelentkezés után** login session/cookie, majd user ország (`BrowserLocale::forLoggedIn`); headerben **nincs** nyelvválasztó; **nincs** URL `/{lang}`
- **Szerepkör panelek:** `/admin`, `/new`, `/member`, `/clubpresident`, `/president` — közös Admin chrome; `new` csak `/new` — [users-auth.md](users-auth.md) §0–2
- **Tagság (ha kell):** `new` → `/complete-profile` → approve → `member`; klubelnök, tagdíj, role ACL — [membership-greenfield.md](membership-greenfield.md) + [membership.md](membership.md); rule: `membership-greenfield.mdc`
- **CakeDC auth baseline:** ValiAdmin login; email login; ország + `country_id`; Flash toast; RoleHome afterLogin (`setResult`); header Belépve + Profile…; search role-gated — **projektenként változhat** (role/form/SSO) — [users-auth.md](users-auth.md); rule: `users-auth.mdc`
- Országnevek: DB `countries` + Translate `i18n`; földrész: `continents` + `countries.continent_id` + Continents i18n (CLDR); Admin lista UI: **[countries-admin.md](countries-admin.md)**; ACL: superuser teljes / admin `visible`+`pos` — seed: `php tmp/seed_continents.php` ([i18n.md](i18n.md))
- Layout = csak közös CSS/JS; index/form/view oldalspecifikus asseteket a template tölti
- **Vissza a tetejére:** `#btn-scroll-top` jobb alsó; csak lejjebb görgetve (`MyAdmin.initScrollTop`)
- **Index card footer:** mindig `admin/index_footer` (= `index_counter` + `index_pagination`); lapozó: **First | Previous | számok | Next | Last** (disabled a széleken) — [admin-konvenciok.md](admin-konvenciok.md) Lapozó; rule: `admin-paginator.mdc`
- Admin dialógusok: **SweetAlert2** (`MyAdmin.swal` / `alert` / `alertError` / `confirmDelete` / `confirmLeave` / `flashSwal`) — soha `window.alert`; Bootstrap modal FocusTrap pause; popup **árnyék** + z-index 20000 (`style.css`)
- **Mentetlen form:** `#form-horizontal` dirty → leave Swal (`confirmLeave`); változatlan → nincs kérdés — [admin-konvenciok.md](admin-konvenciok.md); rule: `admin-form-unsaved.mdc`
- **Form / view lábléc gombok:** ikon+szöveg egyben (`nowrap`); szűk helyen egész gomb új sorba (`flex-wrap`) — [admin-konvenciok.md](admin-konvenciok.md); rule: `admin-form-footer-buttons.mdc`
- **Form kötelező `*`:** label előtt, piros, validatorból — `FormHelper::adminLabel` — rule: `admin-form-required.mdc`
- **Új/éles projekt:** séma + kapcsolatok szerint **minden** keretrendszer-megoldás — [uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md); rule: `uj-projekt-sema.mdc`
- **Index keresés/sort Translate:** `indexPaginateOptionsFor` + `AdminSearch` (UI locale); **ne** `COALESCE` az ORDER BY-ban — [i18n.md](i18n.md); rule: `admin-translate-search-sort.mdc`
- Admin Flash: alap **Simple Notify** toast; modal Flash: `$this->flashSwal()` / `flash/*_swal.php`; **auth (`login` layout) Flash is toast** — [users-auth.md](users-auth.md)
- Form dátum/idő: **Tempus Dominus 6** — formátum + naptárnyelv + hétkezdet a `dateFormat` / `App.adminLocale` szerint; mentés: dátum middleware
- Form számok: `numberFormat` + inputmask; mentés: szám middleware (`1 234,56` → `1234.56`); mezőhiba a control **alatt** (piros félkövér; összetett widget: `admin/field_error`)
- Törlés UI: törölhető = danger + Swal **question**; **nem törölhető** = `btn-secondary` + **disabled** + tooltip
- **Index oszlopok:** `string` rugalmas (kivéve pl. Countries `.continent`/`.iso2`/`.locale`); kötött: `id` 4.75 / `pos` 5.5 / `number` 6.5 / `currency` 12 / **`count` min-width 15rem (`width:1%`)** / `boolean|visible` 7.5 / `date` 8.5 / `datetime` 10.5 / `time` 5 rem ([admin-oldal.md](admin-oldal.md) §4.3)
- Index config: `$rowDoubleClickAction`, `$numberDecimals`, `$show*Column`; **`$indexLimit = 100` / `$indexMaxLimit = 1000`**; session **`Admin.indexState`** (sort/page/`q`); utolsó rekord: **`Admin.lastVisited`** + **`.last-visited`** + scroll
- **Keresés (minden projekt):** `config/admin_search.php` (`fields` + `labelsKey`; globális limitok); index + header; `/admin/search`; header search **csak** superuser/admin/president/vicepresident; clear → last-visited — [uj-projekt.md](uj-projekt.md) §2.8; rule: `admin-kereses-index-allapot.mdc`
- **Setups (ha kell):** EAV `setups` + `SetupValue`; slug csak `a-z0-9_`; olvasás: `Setup::get('slug', $default)` — [setups.md](setups.md); rule: `setups-eav.mdc`
- Számok megjelenítése: `LocaleNumberParser::format()` / `formatCount()`; pénz: **`formatCurrency()`** (HUF, locale pozíció: hu `… Ft`, en `HUF …`) — rule: `penznem-formatcurrency.mdc`
- View: bake-szerű `dl` + gyerek **tab sheet**; belongsTo/HABTM/name **félkövér link** → AJAX modal; Edit lábléc: **`.record-view-footer-actions`** (adatoszlop alatt); `$rowDoubleClickAction` a kapcsolt táblán
- **Linked / szülő modal:** `data-id` + `data-edit-url` / `data-view-url` / `data-delete-url` = **a megnyitott rekord** saját CRUD-ja (pl. taglista klub → `Clubs/edit/{klubId}`); **tilos** a lista sor URL-jére visszahullani — rule: `admin-linked-modal-urls.mdc`
- Form: `#name` autofókusz + `pages/form.js`; Select2 „+” ahol egyszerű create; **HABTM multiple** mindkét oldalon; **belongsTo lista**: `visible` + `pos`/`name` sorrend; form végén **`visible` → `pos`**; **`<hr>` mindig közvetlenül a `visible` fölött** (soha más mező fölött) — rule: `admin-form-visible-hr.mdc`; `fetchTable()`, ne Association
- Oszlop DEFAULT (`pos`, `visible`, …): **DB séma** + `UsesDatabaseColumnDefaultsTrait` — ne PHP hardcode
- **`pos`:** PHP-ból **békén hagyjuk** — mindig DB DEFAULT (**általában `1000`**); **tilos** állítani / növelgetni (`1`/`10`/`$pos += 10`). **Majd a felhasználó**, ha akarja, a formon megnöveli (rule: `.cursor/rules/pos-db-default.mdc`)
- **`*_count`:** **CounterCache** (hasMany → gyerek Table; HABTM → through + `cascadeCallbacks`); törlésvédelem: `PreventsDeleteWithChildrenTrait` + `relatedChildrenCountField()`; **ne** élő COUNT / controller `count(_ids)`
- Modal kapcsolt névlisták: utolsó **20** (`modified DESC`), megjelenítés **ABC ASC**; view tab lehet teljes ABC lista

## UI forrás

A kinézet egy Pike Admin / Bootstrap 5 admin sablon assetjeiből jön (CSS/JS/plugins/fontawesome/img).  
Konkrét mappaút **nem** kötelező a doksihoz — új projektben másold be a sablon `assets`/`webroot` tartalmát, majd alakítsd a [uj-projekt.md](uj-projekt.md) + [admin-oldal.md](admin-oldal.md) / [admin-konvenciok.md](admin-konvenciok.md) szerint.

## Agent szabályok

1. Agent szabályok: **új/éles DB**: **[uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md)** (séma→megoldás); **[admin-oldal.md](admin-oldal.md)** (célkép); **éles DB / demó→éles**: **[minta-tanulsagok.md](minta-tanulsagok.md)** (§0 playbook); **auth / login**: **[users-auth.md](users-auth.md)**; **Translate form / index locale**: [form-i18n-tabs.md](form-i18n-tabs.md) + [i18n.md](i18n.md); **Countries lista**: [countries-admin.md](countries-admin.md); majd a releváns részlet-doksik.
2. Új projekt / hiányzó layout → [uj-projekt.md](uj-projekt.md) 2. szakasz (+ §2.9 CakeDC auth; tagság: §2.9b + [membership-greenfield.md](membership-greenfield.md)).
3. Kód kövesse a konvenciókat (`__()`, asset szabály, middleware, CounterCache, view tabs, oszlopszélességek).
4. **Automatikus dokumentálás (kötelező):** minden lényeges kód/viselkedés-változás **ugyanabban a körben** frissítse a `valtozasok.md`-t **és** az érintett speceket (`admin-oldal.md` / `admin-konvenciok.md` / `minta-tanulsagok.md` / `middleware.md` / `i18n.md` / …). Tartós playbook → `.cursor/rules/*.mdc` is. A felhasználónak **ne kelljen** külön „dokumentáld” üzenetet írnia. Cursor rule: `.cursor/rules/auto-dokumentalas.mdc`.
5. Domain DB sémát ne keverd a keretrendszer-doksi közé (teszt táblák eldobhatók — a tanulságok a `minta-tanulsagok.md`-ben maradnak).
6. A felhasználónak ne kelljen újra elmondania a fenti döntéseket — a `doc/` a forrás.
