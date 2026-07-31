# Keretrendszer vs. domain / tesztmodulok

Ez a fájl megkülönbözteti, mi **maradjon meg** minden projektben, és mi **cserélhető / törölhető**.  
Új projekt felállításához lásd: [uj-projekt.md](uj-projekt.md).

## Tartós (keretrendszer) — NE töröld

| Terület | Útvonal / megjegyzés |
|---------|----------------------|
| Admin layout | `templates/layout/admin.php` |
| Admin elementek | `templates/element/admin/*` (header, sidebar, breadcrumb, footer, modalok, pagination, **view_related_tabs**) |
| Admin AppController | `src/Controller/Admin/AppController.php` (layout + `hu_HU`) |
| Member AppController | `src/Controller/Member/AppController.php` (ha van Member) |
| Routing prefixek | `config/routes.php` — Admin; opcionálisan Member+`{lang}` |
| Locale middleware | `src/Middleware/LocaleMiddleware.php` |
| Szám normalizálás | `NormalizeLocalizedNumberMiddleware` + `LocaleNumberParser` |
| Dátum normalizálás | `NormalizeLocalizedDateMiddleware` + `LocaleDateParser` |
| Host header | `HostHeaderMiddleware` (ha kell) |
| i18n szabály + .po | [i18n.md](i18n.md), `resources/locales/hu_HU/default.po` |
| Webroot UI assetek | `webroot/css`, `js`, `plugins`, `fontawesome`, `img` |
| Admin JS API | `webroot/js/app.js` (`confirmDelete`, `alert` / `alertError` — **tilos** `window.alert`), `js/pages/index.js`, `js/pages/form.js` |
| Page CSS | `webroot/css/pages/index.css`, `form.css` |
| Dokumentáció | `doc/*` (ezt a specifikációt) |

## Ideiglenes / domain — cserélhető

Bármilyen **üzleti** CRUD (és a keretrendszer kipróbálására használt demó modulok):

| Terület | Példa (demó, eldobható) |
|---------|-------------------------|
| Controllerek | `SamplesController`, `ParentsController`, `CitiesController` |
| Templatek | `templates/Admin/Samples`, `Parents`, `Cities` |
| Modellek | `Sample*`, `Parent*`, `City*`, `CitiesSample*` |
| DB | demó táblák + seed |
| Sidebar menüpontok | demó menü → cseréld élesre |

Éles projektben ezek helyett a **valós** domain modulok állnak — a viselkedés ugyanaz ([crud-utmutato.md](crud-utmutato.md)).

## Éles modul / új projekt checklist

1. Olvasd: [admin-oldal.md](admin-oldal.md) (célkép); ha még nincs keretrendszer → [uj-projekt.md](uj-projekt.md); majd [admin-konvenciok.md](admin-konvenciok.md), [i18n.md](i18n.md), [middleware.md](middleware.md), [crud-utmutato.md](crud-utmutato.md).
2. Bake / írd meg a **valós** modelleket (reserved entity nevek!).
3. Admin controller + `index` / `form` / `view`:
   - lista = teljes index-minta
   - view = fő `dl` + gyerek tabok (`admin/view_related_tabs`); kapcsolt listák **ASC**
   - `recordGet` modal: kapcsolt nevek **ASC**
4. Minden UI szöveg: `__('English')` + `hu_HU` .po.
5. Form számok: `numberFormat` + locale inputmask; szám/dátum middleware automatikus — ne írj controlleres „strip”-et.
6. Sidebar / Dashboard az új domainhez.
7. `valtozasok.md` bejegyzés.

## Middleware sorrend (`Application.php`)

```
ErrorHandler → HostHeader → Asset → Routing → Locale
→ BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
```

A dátum **előbb** fut, mint a szám (hogy a `12.03.2024` ne számként legyen kezelve).
