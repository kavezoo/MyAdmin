# Keretrendszer vs. domain / tesztmodulok

Ez a fájl megkülönbözteti, mi **maradjon meg** minden projektben, és mi **cserélhető / törölhető**.  
Új projekt felállításához lásd: [uj-projekt.md](uj-projekt.md).

## Tartós (keretrendszer) — NE töröld

| Terület | Útvonal / megjegyzés |
|---------|----------------------|
| Admin layout | `templates/layout/admin.php` |
| Admin elementek | `templates/element/admin/*` (header, sidebar, breadcrumb, footer, **index_footer**, **index_counter**, **index_pagination** First…Last, modalok, **view_related_tabs**) |
| Admin AppController | `src/Controller/Admin/AppController.php` (CRUD helpers + panel chrome) |
| Panel AppController | `src/Controller/PanelAppController.php` — New/Member/Clubpresident/President |
| RoleHome / AppRoles | `src/Auth/RoleHome.php`, `AppRoles.php` — role → `/new`…`/admin` |
| Routing prefixek | `config/routes.php` — Admin + New + Member + Clubpresident + President (**nincs** `/{lang}`) |
| Admin keresés config | `config/admin_search.php` — header search csak elnök+ role ([users-auth.md](users-auth.md)) |
| CakeDC auth UI | `doc/users-auth.md` — **baseline** új projektekhez; role/login képlékeny |
| Form hibák (Admin) | `src/View/AppView.php` + `templates/element/admin/field_error.php` |
| Szám normalizálás | `NormalizeLocalizedNumberMiddleware` + `LocaleNumberParser` (`format`, `formatCount`, `formatCurrency`) |
| Dátum normalizálás | `NormalizeLocalizedDateMiddleware` + `LocaleDateParser` (`jsConfig` + locale picker) |
| Host header | `HostHeaderMiddleware` (ha kell) |
| i18n szabály + .po | [i18n.md](i18n.md), `resources/locales/hu_HU/default.po` |
| Webroot UI assetek | `webroot/css`, `js`, `plugins`, `fontawesome`, `img` |
| Admin JS API | `webroot/js/app.js` (`confirmDelete`, `alert` / `flashSwal` — **tilos** `window.alert`), `js/pages/index.js`, `js/pages/form.js` |
| Flash | Simple Notify (Admin **és** auth/`login` layout) + SweetAlert2 SWAL (`flash/*_swal`); `admin/script_flash`; Swal popup: z-index 20000 + **árnyék** (`style.css`) |
| Dátum picker | Tempus Dominus 6 + `popper.js` (JeffAdmin5 formátumok) |
| Gyerekvédelem törléskor | `PreventsDeleteWithChildrenTrait` + CounterCache; UI: secondary disabled |
| CounterCache | hasMany: gyerek Table; HABTM: through Table + `cascadeCallbacks`; újraépítés: `bin/cake rebuild_counter_caches` |
| `pos` DB default | `UsesDatabaseColumnDefaultsTrait` — séma DEFAULT; üres mező unset; ArrayObject → `getArrayCopy()` |
| Form UX / hibák | `#name` autofocus + `form.js`; mezőhiba control alatt; Tempus/Select2: `field_error`; `newEntityWithSchemaDefaults()`; try/catch → Flash |
| Modal helpers | `$modalRelatedLimit`, `containRelatedForModal()`, `relatedNameLinksForModal()` (utolsó 20 modified → ABC) |
| CounterCache rebuild | `src/Command/RebuildCounterCachesCommand.php` |
| Page CSS | `webroot/css/pages/index.css`, `form.css` |
| Dokumentáció | `doc/*` — különösen [minta-tanulsagok.md](minta-tanulsagok.md) éles építéshez |
| PDF generálás | Composer **`mpdf/mpdf`** (^8.3) — HTML/CSS → PDF, UTF-8; tempDir: `tmp/mpdf` (még nincs domain wrapper — későbbi PDF feladatokhoz) |

## Ideiglenes / domain — cserélhető

Bármilyen **üzleti** CRUD. A keretrendszer kipróbálására használt **Samples / Parents / Cities** demó modulok **ebből a projektből el lettek távolítva** (2026-08-06) — a tanulságok [minta-tanulsagok.md](minta-tanulsagok.md)-ben maradnak.

| Terület | Megjegyzés |
|---------|------------|
| Controllerek / templatek / modellek | Éles domain (Countries, Clubs, Users, Setups, …) |
| DB | Demó táblák (`samples`, `parents`, `cities`, `cities_samples`) drop migrációval törölve |
| Sidebar / Dashboard | Csak valós menüpontok |

Új CRUD: [crud-utmutato.md](crud-utmutato.md) + [minta-tanulsagok.md](minta-tanulsagok.md) (viselkedés, nem demó mezőnevek).

## Éles modul / új projekt checklist

1. Olvasd: [admin-oldal.md](admin-oldal.md) + [minta-tanulsagok.md](minta-tanulsagok.md) **§0 playbook**; ha még nincs keretrendszer → [uj-projekt.md](uj-projekt.md); auth → [users-auth.md](users-auth.md); majd [admin-konvenciok.md](admin-konvenciok.md), [i18n.md](i18n.md), [middleware.md](middleware.md), [crud-utmutato.md](crud-utmutato.md).
2. Bake / írd meg a **valós** modelleket (reserved entity nevek!; CounterCache + `PreventsDeleteWithChildrenTrait`).
3. Admin controller + `index` / `form` / `view`:
   - lista = teljes index-minta
   - view = fő `dl` + gyerek tabok; kapcsolt listák **ASC**
   - `recordGet` modal: kapcsolt nevek = utolsó **20** modified → **ABC**; `can_delete`
4. Minden UI szöveg: `__('English')` + `hu_HU` .po; `App.adminLocale` = **`hu_HU`**.
5. Form számok/dátumok: `numberFormat` / `dateFormat` + middleware; HABTM `_ids` + CounterCache (ne kézi count); mezőhiba § [minta-tanulsagok.md](minta-tanulsagok.md) §6c.
6. Sidebar / Dashboard az új domainhez.
7. `valtozasok.md` bejegyzés; tartós szabályváltozás → `minta-tanulsagok.md` / `admin-konvenciok.md` is.

## Middleware sorrend (`Application.php`)

```
ErrorHandler → HostHeader → SanitizeAuthRedirect → Asset → Routing → Locale
→ BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
```

A dátum **előbb** fut, mint a szám (hogy a `12.03.2024` ne számként legyen kezelve).
