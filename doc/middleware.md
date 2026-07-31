# Middleware — szám- és dátumnormalizálás

Keretrendszer-rész (minden projektben): a formról érkező **locale szerinti** számok és dátumok egységes DB-formátumra hozása.  
Greenfield: [uj-projekt.md](uj-projekt.md) → middleware lépés. UI együttműködés: [admin-oldal.md](admin-oldal.md) §6 + [admin-konvenciok.md](admin-konvenciok.md).

## Cél

| Middleware | Bemenet (példa) | Kimenet a controller / ORM felé |
|------------|-----------------|----------------------------------|
| `NormalizeLocalizedDateMiddleware` | `12.03.2024`, `03/12/2024`, `2024-03-12 09:15`, `08:00` | `2024-03-12`, `2024-03-12 09:15:00`, `08:00:00` |
| `NormalizeLocalizedNumberMiddleware` | `1 234,56` (hu), `1,234.56` (en) | `1234.56` |

Így az inputmask / daterangepicker / kézi locale formátumok menthetők MySQL `DECIMAL` / `DATE` / `DATETIME` / `TIME` mezőkbe.

## Fájlok

| Fájl | Szerep |
|------|--------|
| `src/Middleware/NormalizeLocalizedDateMiddleware.php` | Request body bejárás, dátum mezők |
| `src/Middleware/NormalizeLocalizedNumberMiddleware.php` | Request body bejárás, szám mezők |
| `src/Utility/LocaleDateParser.php` | Locale → dátum sorrend, parse |
| `src/Utility/LocaleNumberParser.php` | Locale → ezres/tizedes, parse |
| `src/Middleware/LocaleMiddleware.php` | Locale beállítás **előbb** (Admin → mindig `hu_HU`) |
| `src/Application.php` | Middleware queue bekötés |

## Mikor fut?

- Csak `POST` / `PUT` / `PATCH` / `DELETE`
- Rekurzívan a `getData()` tömbön
- Kihagyott kulcsok: `_csrfToken`, `_Token`, `_method`, jelszó mezők, és minden `_` prefixű kulcs

## Locale szabályok (parser)

### Szám (`LocaleNumberParser`)

| Locale | Tizedes | Ezres (eltávolítandó) |
|--------|---------|------------------------|
| `hu_HU`, `sk_SK`, `fr_FR` | `,` | szóköz (alt: NBSP, `.`) |
| `de_DE` | `,` | `.` (alt: szóköz, NBSP) |
| `en_US`, `en_GB` | `.` | `,` (alt: szóköz, NBSP) |

- Kimenet: kanonikus string (`1234.56`), előjel megmarad.
- **Nem** nyúl dátum-/idő-szerű stringekhez (pl. `12.03.2024`, `08:00`, `2024-03-12`).

### Dátum (`LocaleDateParser`)

| Locale | Elsődleges sorrend | Megjegyzés |
|--------|-------------------|------------|
| `hu_HU` | YMD (UI: `yyyy-mm-dd`) | Emellett elfogad DMY-t is (`12.03.2024`) |
| `de_DE`, `sk_SK`, `fr_FR`, `en_GB` | DMY | |
| `en_US` | MDY | |

Elválasztók: `/` `.` `-` szóköz.  
Kimenet:

- dátum → `Y-m-d`
- dátum+idő → `Y-m-d H:i:s`
- csak idő → `H:i:s`

## LocaleMiddleware + Admin

Az Admin URL-ben nincs `{lang}` szegmens. A `LocaleMiddleware` az `Admin` prefixnél **mindig** `hu_HU`-t állít, még a normalizáló middleware-ek előtt — így a hu ezres/tizedes és dátum szabályok érvényesülnek mentéskor.

Member: a `{lang}` paraméter állítja a locale-ot (pl. `/en/member` → `en_GB`).

## Új locale hozzáadása

1. Bővítsd a `$formats` tömböt a két parser osztályban.
2. (Opcionális) írj assertet a `tmp/test_locale_parsers.php`-be.
3. Dokumentáld itt.

## Form / UI együttműködés

- A form **locale szerinti** számformátumot használ (inputmask + megjelenített value).
- Admin `hu_HU`: tizedes **`,`**, ezres **szóköz** (alt: `.`) — pl. `1 234,56`.
- Form config: `MyAdmin.config.numberFormat` = `LocaleNumberParser::jsConfig()` (`decimal`, `thousand`, `locale`).
- Mező osztályok: `.js-input-decimal`, `.js-input-integer`; value: `LocaleNumberParser::format(...)`.
- A megjelenítés (index/view/modal): `LocaleNumberParser::format()` — Admin `hu_HU` pl. `1 234,56`.
- Form input: locale inputmask + `format()` value; mentés: middleware → `1234.56`.
- Éles modulnál ne távolítsd el ezeket a middleware-eket a queue-ból.

### Form példa

```php
$config['numberFormat'] = \App\Utility\LocaleNumberParser::jsConfig();
// …
'class' => 'form-control js-input-decimal',
'value' => LocaleNumberParser::format($entity->netto, decimals: 2),
```

```js
// pages/form.js — radixPoint / groupSeparator a numberFormat-ból
```

## Teszt

```bash
php tmp/test_locale_parsers.php
```
