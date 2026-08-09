# Form nyelvi TAB-ok (Translate EAV)

Admin add/edit formok fordítási fülei.  
Kapcsolódó: [i18n.md](i18n.md), [admin-konvenciok.md](admin-konvenciok.md) (mentetlen form), **[country-visibilities.md](country-visibilities.md)**.  
Rule: `.cursor/rules/admin-form-i18n-tabs.mdc`

**Cake konvenció:** a form mezők / nyelvi TAB markup a **`templates/{Prefix}/{Controller}/form.php`** fájlban legyen (ne külön custom element). Referencia másoláshoz: [snippets/form_language_fields.php.example](snippets/form_language_fields.php.example).

---

## 1. Mikor kell

Ha a Table-en van **Translate** behavior szöveges mezőkkel (`name`, `description`, …):

| Lépés | Hol |
|-------|-----|
| `setFormLanguageTabs()` | Controller add/edit |
| `getWithTranslations($table, $id, $contain)` | edit (összes locale EAV) |
| Markup | **ugyanazon** controller `form.php`-jában (nem `element/admin/…`) |

Countries: **nincs** nyelvi TAB (seedelt országnevek).  
Cities / Setups form: nincs nyelvi TAB a formon (Setups `name` Translate seedelt).  
**Competitions** (Admin + President): Translate EAV + nyelvi TAB — `name`, `title`, `subtitle`, `subtitle2`, `description`, `racing_pipe_*_title`. Shared markup: `element/competitions/form_i18n_tabs` (két prefix). Helper: `FormLanguageTabsTrait`.  
**Description card-header TAB:** `fullWidth => true` — JeffAdmin5 text pane (`col-sm-12`, nincs oldalsó label oszlop); a Basic data nyelvi TAB-ok maradnak a klasszikus `col-md-10` layouton.  
**Competition text templates** ugyanígy (Description fül).  
**Email templates** (Admin / President): nincs Cake Translate — nyelvi TAB a `templates/{Prefix}/EmailTemplates/form.php`-ban; egy DB sor / (`country_id` + `language_id` + `slug`); mezők: `subject`, **`body_html` (`.editor` / Trumbowyg)**, `body_text` (plain); `name` admin címke.

---

## 2. Tabs adat — `FormLanguages::tabs()`

```php
$this->setFormLanguageTabs();
// → formLanguageTabs = aktív ország country_visibilities nyelvei
// → formDefaultLocale = FormLanguages::defaultLocaleForForm()
```

| Mező | Jelentés |
|------|----------|
| `locale` | Translate kulcs (`hu_HU`, `sk_SK`, `en_GB`, …) |
| `code` | Fül felirat (`HU`, `SK`, `EN`, …) |
| `iso2` / `country_id` / `country_name` | A „nyertes” (első) ország az adott nyelven |
| `countries` | Tooltip lista ugyanarra a nyelvre |

**Forrás (kötelező):** az aktív `Users.country_id` **`country_visibilities`** sorai — saját ország nyelve + a Countries formon felvett **Additional languages**.  
Csak ezek jelennek meg TAB-ként. Dedup: egy rövid nyelvkód = egy fül. Saját nyelv mindig első.

`defaultLocaleForForm()`: ha az **EAV Translate `defaultLocale` (`en_GB`)** benne van a listában → az a root mező (fő tábla oszlopok); különben az első TAB (saját nyelv).  
**Ne** az `App.defaultLocale` (pl. `hu_HU`) legyen a form root, ha az eltér a Table Translate `defaultLocale`-jától — a Cake `beforeMarshal` az `_translations.en_GB.*` üres mezőit a gyökérre emeli és felülírja a kötelező szövegeket.

Részlet: [country-visibilities.md](country-visibilities.md).

---

## 3. Form.php — mezők

Másold a [snippets/form_language_fields.php.example](snippets/form_language_fields.php.example) mintát a CRUD `form.php`-jába, és állítsd az `i18nFields` listát:

```php
$i18nFields = [
    ['name' => 'name', 'label' => __('Name:'), 'type' => 'text'],
    ['name' => 'description', 'label' => __('Description:'), 'type' => 'editor', 'rows' => 8],
];
```

| Locale | Form mezőnév |
|--------|----------------|
| default (`formDefaultLocale`) | `name`, `description` (entity root) |
| egyéb | `_translations.{locale}.name` stb. |

HTML mező (`type` = `editor`): `textarea` + class **`editor`** + Summernote / Trumbowyg assetek a **ugyanazon** `form.php` tetején (`pages/form.js` initeli).

**JeffAdmin5 text TAB (Description):** ha az editor saját card-header fülön van, add át `'fullWidth' => true` az `form_i18n_tabs` hívásnak — teljes szélességű szerkesztő (`col-12`), Translations: + nyelvi fülek egy sorban, mezőcímke elrejtve az editor fölött. CSS: `.form-i18n-tabs--full` (`pages/form.css`).

A label mellett a **gyökér mező** kötelezősége jelenik meg (`Form->requiredMark($fieldName)`).

---

## 4. Tooltip (országok)

- Tab **gomb**: csak `data-bs-toggle="tab"` — **nincs** `title` / tooltip attribútum a gombon.
- Belső `<span class="js-hover-only-tooltip">` a fül kódja körül: `title` = partner országok (`Name (ISO)<br>…`), UI locale szerint.
- Init: `App.initHoverOnlyTooltips()` (`form.js`) — csak `hover`, explicit `mouseleave` → `hide()`; tab váltáskor `App.hideHoverOnlyTooltipsIn()`.

**Ne** tedd a tooltip `data-bs-toggle="tooltip"`-t ugyanarra az elemre, mint a tabot, és **ne** `App.initTooltips()` a tab gombon (focus trigger → beragadó tooltip).

---

## 4b. TAB váltás → name fókusz

Minden tab gomb: `data-name-target="name"` / `name-hu-hu` / … (az **adott nyelv** name input id-ja).  
A fókusz JS a CRUD `form.php`-jában (vagy a snippetben).

---

## 5. Ellenőrzőlista

- [ ] Tabs = csak az országhoz felvett nyelvek (`country_visibilities`)
- [ ] Saját nyelv első; extras utána
- [ ] `setFormLanguageTabs` + `getWithTranslations` ugyanezt a default locale-t használja
- [ ] Markup a `form.php`-ban — **nincs** külön form field element
- [ ] Mentés után a TAB-ok a mentett extras szerint frissülnek (új session / új kérés)
