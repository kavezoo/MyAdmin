# Form nyelvi TAB-ok (Translate EAV)

Admin add/edit formok fordítási fülei.  
Kapcsolódó: [i18n.md](i18n.md), [admin-konvenciok.md](admin-konvenciok.md) (mentetlen form), **[country-visibilities.md](country-visibilities.md)**.  
Rule: `.cursor/rules/admin-form-i18n-tabs.mdc`

---

## 1. Mikor kell

Ha a Table-en van **Translate** behavior szöveges mezőkkel (`name`, `description`, …):

| Lépés | Hol |
|-------|-----|
| `setFormLanguageTabs()` | Controller add/edit |
| `getWithTranslations($table, $id, $contain)` | edit (összes locale EAV) |
| Element | `templates/element/admin/form_language_fields.php` |

Minták: Samples, Parents.  
Countries: **nincs** nyelvi TAB (seedelt országnevek).  
Cities / Setups: nincs Translate → nincs nyelvi TAB.

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

`defaultLocaleForForm()`: ha az en_GB (Translate default) benne van a listában → az a root mező; különben az első TAB (saját nyelv).

Részlet: [country-visibilities.md](country-visibilities.md).

---

## 3. Element használat

```php
<?= $this->element('admin/form_language_fields', [
    'entity' => $sample,
    'formLanguageTabs' => $formLanguageTabs ?? [],
    'defaultLocale' => $formDefaultLocale ?? \App\Utility\FormLanguages::defaultLocaleForForm(),
    'i18nFields' => [
        ['name' => 'name', 'label' => __('Name:'), 'type' => 'text'],
        ['name' => 'description', 'label' => __('Description:'), 'type' => 'editor', 'rows' => 8],
    ],
]) ?>
```

| Locale | Form mezőnév |
|--------|----------------|
| default (`formDefaultLocale`) | `name`, `description` (entity root) |
| egyéb | `_translations.{locale}.name` stb. |

A label mellett a **gyökér mező** kötelezősége jelenik meg (`Form->requiredMark($fieldName)`), pl. `name` kötelező → minden TAB-on csillag; `description` opcionális → nincs.

---

## 4. Tooltip (országok)

- Tab **gomb**: csak `data-bs-toggle="tab"` — **nincs** `title` / tooltip attribútum a gombon.
- Belső `<span class="js-hover-only-tooltip">` a fül kódja körül: `title` = partner országok (`Name (ISO)<br>…`), UI locale szerint.
- Init: `App.initHoverOnlyTooltips()` (`form.js`) — csak `hover`, explicit `mouseleave` → `hide()`; tab váltáskor `App.hideHoverOnlyTooltipsIn()`.

**Ne** tedd a tooltip `data-bs-toggle="tooltip"`-t ugyanarra az elemre, mint a tabot, és **ne** `App.initTooltips()` a tab gombon (focus trigger → beragadó tooltip).

---

## 4b. TAB váltás → name fókusz

Minden tab gomb: `data-name-target="name"` / `name-hu-hu` / … (az **adott nyelv** name input id-ja).  
Inline JS a `form_language_fields`-ben.

---

## 5. Ellenőrzőlista

- [ ] Tabs = csak az országhoz felvett nyelvek (`country_visibilities`)
- [ ] Saját nyelv első; extras utána
- [ ] `setFormLanguageTabs` + `getWithTranslations` ugyanezt a default locale-t használja
- [ ] Mentés után a TAB-ok a mentett extras szerint frissülnek (új session / új kérés)
