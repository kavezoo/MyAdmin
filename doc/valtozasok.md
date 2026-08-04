# Változásnapló

Minden lényeges projektmódosítás után **ide írj bejegyzést** (dátum, mi változott, érintett fájlok).  
Új CakePHP projektbe másolt `doc/` esetén: ezt a fájlt **nullázhatod** / új bejegyzéssel indíthatod; a többi spec (`uj-projekt.md`, `admin-konvenciok.md`, …) a tartós tudás.

---

## 2026-08-04 — UI nyelv = login nyelv

### Mi változott / miért
A bejelentkezés utáni felület nyelve a login képernyőn választott nyelv (session/cookie), nem a user `country_id` locale. Az ország locale csak fallback, ha nincs session/cookie.

### Érintett
- `BrowserLocale::forLoggedIn` — sorrend: session → cookie → user country → detect
- `UsersController::applyStoredUserLocalePreferences`
- Doc: `login-language.md`, `users-auth.md`, `i18n.md`

---

## 2026-07-31 — Countries modal: további nyelvek linkelve

### Mi változott / miért
Az ország részletek modalban megjelennek a felvett Additional languages, klikkelhető kapcsolt rekordként (mint a Cities→Samples).

### Érintett
- `CountriesController::recordGet` + `relatedAdditionalLanguagesForModal`
- `templates/Admin/Countries/index.php` `relatedLinkFields`

---

## 2026-07-31 — Form TAB-ok = country_visibilities nyelvek

### Mi változott / miért
A fordítási fülek kizárólag az aktív országhoz felvett nyelvek (saját + Additional languages). A form default locale nem fix en_GB, hanem a lista szerinti.

### Érintett
- `FormLanguages::defaultLocaleForForm()`, `setFormLanguageTabs`, `getWithTranslations`
- Doc: `form-i18n-tabs.md`, `country-visibilities.md`

---

## 2026-07-31 — Countries form: nincs nyelvi TAB

### Mi változott / miért
Országnevek fordításai seedelve vannak; a Countries add/edit formon nincs `form_language_fields` — egyetlen Name mező.

### Érintett
- `templates/Admin/Countries/form.php`, `CountriesController` (nincs `setFormLanguageTabs` / `_translations`)
- Doc: `countries-admin.md`

---

## 2026-07-31 — country_visibilities: saját + plusz nyelvek

### Mi változott / miért
Junction újraértelmezés: minden országhoz kötelező self (saját TAB nyelv); a formon csak **Additional languages** (extras). en_GB lock eltávolítva a visibility listából.

### Érintett
- `tmp/seed_country_visibilities.php` — TRUNCATE + self-only
- `CountriesTable::ensureSelfFirst`, `visibleCountryIdsFor`, `seedDefaultVisibilitiesForCountry`
- Countries form Select2; `FormLanguages` — saját nyelv első
- Doc: `country-visibilities.md`, `form-i18n-tabs.md`

---

## 2026-07-31 — Event logs: adatváltozás megjelenítés (from → to)

### Mi változott / miért
Lista és részletező egyértelműen mutatja: melyik mező miről mire változott.

### Érintett
- `EventLogChanges` utility; `element/admin/event_log_changes`
- Admin + saját event log index: **Adatváltozások** oszlop
- View: kiemelt Data changes kártya

---

## 2026-07-31 — Event logs: nincs oldalnézet napló

### Mi változott / miért
Megtekintés / böngészés nem kerül az `event_logs`-ba — csak login/logout + entity CRUD (from→to).

### Érintett
- `EventLogRequestMiddleware` eltávolítva a middleware queue-ból (és a fájl)
- Doc: `event-logs.md`

---

## 2026-07-31 — Event logs: mező diff (from → to)

### Mi változott / miért
Entity mentéskor a dirty mezők régi és új értéke bekerül a naplóba; jelszó/token csak „változott” jelzéssel.

### Érintett
- `EventLogBehavior` — `changes[field]={from,to}`; description összefoglaló
- `EventLogger::isSecretField`; view: Changed fields tábla
- Doc: `event-logs.md`

---

## 2026-07-31 — Event logs: user szűrő + kevesebb zaj

### Mi változott / miért
Officer event log indexen user Select2 AJAX szűrő; nyelvi táblák és olvasási HTTP zaj kikerül a naplóból (csak adatváltozás).

### Érintett
- `Admin\EventLogsController::userOptions` + index `user_id` filter; `event_logs_index.js`
- `EventLogBehavior` skip: Languages, I18n
- `EventLogRequestMiddleware`: csak POST/PUT/PATCH/DELETE; skip Dashboard/Locales/Pages/Search
- Doc: `event-logs.md`

---

## 2026-07-31 — Tagság: new → profil → clubpresident → member

### Mi változott / miért
Frissen regisztrált (`new`) userek kötelező profilkiegészítése login után; kész profil → email a clubpresidentnek; jelentkezők lista + Approve → `member` + email login linkkel.

### Érintett
- Séma: `config/schema/clubs.sql`, `users_membership.sql`; seed: `tmp/seed_membership.php`
- `MembershipProfile`, `MembershipService`, `MembershipMailer` + email templatek
- `UsersController::completeProfile`, `New\AppController` gate, afterLogin redirect
- `Clubpresident\ApplicantsController` + sidebar
- Permissions: `completeProfile`
- Doc: `membership.md`, `users-auth.md`

---

## 2026-08-04 — Login: nyelv választó (nem ország)

### Mi változott / miért
Login képernyőn nyelv Select2 (böngésző felismerés + i18n nyelvnevek); regisztrációnál marad az ország.

### Érintett
- `languages` tábla + Translate `name` → `i18n`; `AdminLanguage`, `tmp/seed_languages.php`
- `UsersController` login/register szétválasztás; `templates/Users/login.php`; `users_auth_locale.js`
- Doc: `login-language.md`, `users-auth.md`

---

## 2026-08-04 — event_logs: felhasználói eseménynapló

### Mi változott / miért
Minden auth + HTTP + adatváltozás naplózása ország szerint; officer kereső; saját napló minden usernek.

### Érintett
- `config/schema/event_logs.sql`, migráció `CreateEventLogs`
- `EventLogsTable` / Entity, `EventLogger`, `EventLogBehavior`, `EventLogRequestMiddleware`
- `Admin\EventLogsController`, `UsersController::eventLog`, sidebars, permissions
- Doc: `event-logs.md`, `README.md`, `users-auth.md`

---

## 2026-08-04 — users.enabled: belépés tiltás / engedély

### Mi változott / miért
CakeDC `active` mellett az app `enabled` mező is kapu: admin/president kizárhat usert. Login + session közben érvényes.

### Érintett
- `UsersTable::findActive` (`active=1` **és** `enabled=1`)
- `App\Controller\Component\LoginComponent` — disabled Flash
- `UsersMiddlewareQueueLoader` + `RequireUserEnabledMiddleware`
- `config/users.php`, migráció `AddEnabledToUsers`
- Doc/rule: `users-auth.md`, `users-auth.mdc`

---

## 2026-08-04 — country_visibilities: országonkénti láthatóság

### Mi változott / miért
Form nyelvi TAB-ok és login országlista ország→ország kapcsolótáblából; Countries formon állítható. **en_GB** mindig első és nem kapcsolható ki.

### Érintett
- `config/schema/country_visibilities.sql`, `tmp/seed_country_visibilities.php`
- `CountryVisibilitiesTable`, `CountriesTable` finderek / HABTM `VisibleCountries`
- `FormLanguages`, `AdminCountry`, `BrowserLocale`, `CurrentUser::countryId()`
- `CountriesController` + `Countries/form.php` Select2 + en_GB/self lock
- Doc/rule: `country-visibilities.md`, `countries-admin.md`, `form-i18n-tabs.md`, `README.md`, `uj-projekt-sema-playbook.md`, `admin-form-i18n-tabs.mdc`, `admin-countries-index.mdc`

---

## 2026-08-04 — Agent playbook: új projekt = séma + minden megoldás

### Mi változott / miért
Új / éles projektnél az agent a **DB séma és táblakapcsolatok** alapján építse be az összes eddigi Admin megoldást (ne demó mezőket másoljon).

### Érintett
- **`doc/uj-projekt-sema-playbook.md`** — oszlop/kapcsolat mátrix + checklist
- **`.cursor/rules/uj-projekt-sema.mdc`** — `alwaysApply`
- Index: `README.md`, `uj-projekt.md`, `minta-tanulsagok.md` §0, `auto-dokumentalas.mdc`

---

## 2026-08-04 — Parents TAB → name fókusz (inline script)

### Mi változott / miért
Parents editnél a fülváltás után nem ment a fókusz a name-re (`fade` + tooltip focus + form.js cache).

### Javítás
- `form_language_fields`: inline JS (shown.bs.tab + capture click); tab-pane **fade nélkül**
- Tooltip: csak `hover` (`form.js`)

### Érintett
- `templates/element/admin/form_language_fields.php`, `webroot/js/pages/form.js`

---

## 2026-08-04 — Parents TAB fókusz javítás

### Mi változott / miért
`/admin/parents/edit/…` TAB váltáskor nem ment a name fókusz: a tooltip belső `span` (`data-bs-toggle=tooltip`) + a fókusz a tab gombon maradt.

### Javítás
- Tooltip a tab **gombon**; `js-i18n-name` osztály
- jQuery `shown.bs.tab` + click fallback; focus 0/50/200 ms
- Pane `tabindex="-1"`

### Érintett
- `templates/element/admin/form_language_fields.php`, `webroot/js/pages/form.js`
- Doc/rule: `form-i18n-tabs.md`, `admin-form-i18n-tabs.mdc`

---

## 2026-08-04 — Session zárás: kötelező csillag, TAB tooltip, name fókusz

### Mi változott / miért
Admin form UX kör lezárása: kötelező mező jelölés, nyelvi TAB tooltip (visible országok), TAB váltáskor name fókusz.

### Funkciók
1. **Kötelező `*`** — piros, a label **előtt**, szóköz nélkül (`FormHelper::adminLabel` / `requiredMark`; validator alapján).
2. **„Translations:”** → hu: *Fordítások:*
3. **Nyelvi TAB tooltip** — adott nyelv összes `visible` országa egymás alatt; live `find('visibleTranslated')`.
4. **TAB váltás → name fókusz** — natív BS5 `shown.bs.tab` + `[data-i18n-name]` (Samples, Parents, Countries full edit). jQuery `.on('shown.bs.tab')` nem megbízható Bootstrap 5-tel.
5. **Countries** full edit: nyelvi TAB-ok a `name` fordításához (`setFormLanguageTabs` + `getWithTranslations`).

### Érintett (fő)
- `src/View/Helper/FormHelper.php`, `templates/Admin/**/form.php`, `form_language_fields.php`
- `src/Utility/FormLanguages.php`, `webroot/js/pages/form.js`, `webroot/css/style.css`
- `CountriesController`, `Country` entity `_translations`
- `resources/locales/hu_HU/default.po`
- Doc/rule: `admin-konvenciok.md`, `form-i18n-tabs.md`, `admin-form-required.mdc`, `admin-form-i18n-tabs.mdc`

---

## 2026-08-04 — Form nyelvi TAB: váltáskor name fókusz (javítás + Countries)

### Mi változott / miért
TAB → name fókusz **minden** nyelvi fülös formon: natív Bootstrap 5 esemény (a jQuery binding nem mindig futott). Countries full edit is kapott nyelvi TAB-okat.

### Érintett
- `webroot/js/pages/form.js` — `addEventListener('shown.bs.tab')` + `data-i18n-name`
- `templates/element/admin/form_language_fields.php`
- `templates/Admin/Countries/form.php`, `CountriesController`, `Country` entity
- Doc: `form-i18n-tabs.md`, `admin-form-i18n-tabs.mdc`

---

## 2026-08-04 — Form nyelvi TAB tooltip: összes visible ország

### Mi változott / miért
Nyelvi fül tooltip: az adott nyelvet beszélő **összes** `visible` ország egymás alatt (UI locale név + ISO), nem csak a „nyertes” ország.

### Érintett
- `src/Utility/FormLanguages.php` — `countries[]` lista
- `templates/element/admin/form_language_fields.php` — `data-bs-html` + `<br>`
- Doc/rule: `form-i18n-tabs.md`, `admin-form-i18n-tabs.mdc`

---

## 2026-08-04 — Kötelező mező: piros csillag a labelnél

### Mi változott / miért
Admin formokon a kötelező mezők címkéjén automatikus piros `*` (validator alapján); opcionálisnál nincs.

### Érintett
- `src/View/Helper/FormHelper.php` — `adminLabel()`, `requiredMark()`, `isFieldRequired()`
- `templates/Admin/**/form.php`, `templates/element/admin/form_language_fields.php`
- `webroot/css/style.css` — `.required`
- Doc: `admin-konvenciok.md`

---

## 2026-08-04 — Session napló: tartós playbookok (agent)

### Miért
A mai kör (Countries UI, mentetlen form, locale keresés/sort, nyelvi TAB tooltip, Cake 5.3 hibák) **chat nélkül** újraépíthető legyen.

### Tartós specek / rules (olvasd ezeket először)

| Téma | Doc | Rule |
|------|-----|------|
| Countries index (visible-only, oszlopok, CSS) | [countries-admin.md](countries-admin.md) | `admin-countries-index.mdc` |
| Form nyelvi TAB + ország tooltip + error/setLocale buktatók | [form-i18n-tabs.md](form-i18n-tabs.md) | `admin-form-i18n-tabs.mdc` |
| Index keresés/sort UI locale | [i18n.md](i18n.md) | `admin-translate-search-sort.mdc` (+ `admin-kereses-index-allapot.mdc`) |
| Mentetlen form leave Swal | [admin-konvenciok.md](admin-konvenciok.md) | `admin-form-unsaved.mdc` |
| Gyakori hibák | [minta-tanulsagok.md](minta-tanulsagok.md) §13 | — |

### Agent checklist új Translate-es CRUD-nál

1. `admin_search.php` fields + `indexPaginateOptionsFor` (+ assoc Translate táblák)
2. Form: `setFormLanguageTabs` + `getWithTranslations` + `form_language_fields`
3. `#form-horizontal` + `pages/form.js` (unsaved leave)
4. `getBehavior('Translate')->setLocale` — soha Table proxy
5. `.po` msgid-ek + `cache clear _cake_translations_`
6. Doc/rule frissítés (`auto-dokumentalas.mdc`)

---

## 2026-08-04 — Form nyelvi TAB: ország tooltip (UI locale)

### Mi változott / miért
- Nyelvi fülek (EN/HU/…): hover tooltip = ország neve az **oldal nyelvén** + ISO (pl. „Magyarország (HU)”).
- `FormLanguages::tabs()` betölti a Translate `name`-et; tooltip a tab felirat `<span>`-jén (`data-bs-toggle=tooltip`, a tab gomb `data-bs-toggle=tab` marad).

### Érintett
- `src/Utility/FormLanguages.php`
- `templates/element/admin/form_language_fields.php`

---

## 2026-08-04 — Countries index ORDER BY: ne COALESCE (Paginator _prefix)

### Mi változott / miért
- `COALESCE(Alias_x.content, Alias.name)` order key → Cake `_prefix()` az első `.`-nál vág → SQL syntax error.
- `AdminTranslate`: rendezés = `*_translation.content` + másodlagos kanonikus `Alias.field`.

### Érintett
- `src/Utility/AdminTranslate.php`, `CountriesTable::findVisibleTranslated`, docs

---

## 2026-08-04 — Samples edit: Cake 5.3 setLocale deprecation + form error bool

### Mi változott / miért
- `getWithTranslations`: `$table->setLocale()` → `getBehavior('Translate')->setLocale()` (Cake 5.3 deprecation).
- `AdminTranslate` / `AdminSearch`: `translationField()` ugyanez a path.
- `form_language_fields`: `'error' => $isDefault` (bool `true`) → FormHelper TypeError; csak nem-default tabon `error => false`.

### Érintett
- `src/Controller/Admin/AppController.php`
- `src/Utility/AdminTranslate.php`, `src/Utility/AdminSearch.php`
- `templates/element/admin/form_language_fields.php`

---

## 2026-08-04 — Index keresés / rendezés UI locale szerint (Translate)

### Mi változott / miért
- Keresés és ABC rendezés eddig a **kanonikus angol** `name` oszlopon ment; megjelenítés viszont Translate-elt.
- Most: `AdminTranslate` + `AdminSearch` → LIKE / ORDER a UI locale fordításán (`translationField` + kanonikus másodlagos sort).
- Controllers: `indexPaginateOptionsFor()` (Countries, Samples, Parents, Cities, Setups).
- Cities / Setups (nincs Translate): viselkedés változatlan.

### Érintett
- `src/Utility/AdminTranslate.php` (új)
- `src/Utility/AdminSearch.php`, `src/Utility/AdminCountry.php`
- `src/Controller/Admin/AppController.php` (`indexPaginateOptionsFor`)
- `CountriesController`, `SamplesController`, `ParentsController`, `CitiesController`, `SetupsController`
- `CountriesTable::findVisibleTranslated`
- `config/admin_search.php`, `doc/i18n.md`, `doc/admin-konvenciok.md`

---

## 2026-08-04 — Countries index UI (összefoglaló dokumentáció)

### Mi változott / miért
Teljes Countries lista UX + tartós spec (agent playbook):

1. **Visible-only switch** — `__('Only visible countries')` / hu: *Csak a látható országok*; session `Admin.countriesVisibleOnly`; default be.
2. **Header elválasztó** — `|` (`.index-header-sep`, nagy margó) a switch és a kereső között.
3. **Oszlopsorrend** — Continent → Name → ISO → Locale → …
4. **Kötött szélességek** — `.continent` 10.5rem, `.iso2` 5rem, `.locale` 8.5rem.
5. **user_count** — címke `__('Number of users')` / *Felhasználók száma*; `.count` = `width:1%` + `min-width:15rem` (+ `pages/index.css`, inline) — tábla cella nem zsugorítja.
6. **i18n** — `Locale` → *Nyelvi kód*; leave-Swal msgid-ek a `.po`-ban.
7. **Mentetlen form** — `#form-horizontal` dirty → `MyAdmin.confirmLeave` (korábbi kör).

### Spec / rule
- **[countries-admin.md](countries-admin.md)** — tartós playbook
- `.cursor/rules/admin-countries-index.mdc`
- [admin-konvenciok.md](admin-konvenciok.md), [i18n.md](i18n.md), [admin-oldal.md](admin-oldal.md)

### Érintett kód
- `src/Controller/Admin/CountriesController.php`
- `templates/Admin/Countries/index.php`
- `webroot/css/style.css`, `webroot/css/pages/index.css`
- `webroot/js/pages/form.js`, `webroot/js/app.js`, `templates/layout/admin.php`
- `resources/locales/hu_HU/default.po`, `resources/locales/default.pot`

---

## 2026-08-04 — Countries index oszlopsorrend / szélesség

### Mi változott / miért
- Sorrend: **Continent → Name → ISO → Locale** (ISO a név után; földrész a név előtt).
- Fix CSS: `.iso2` / `.locale` / `.continent` (lásd countries-admin.md; count: min-width 15rem).

### Érintett
- `templates/Admin/Countries/index.php`, `webroot/css/style.css`

---

## 2026-08-04 — Countries Visible only: elválasztó + hu fordítás

### Mi változott / miért
- Switch és kereső között `.index-header-sep` (`|`).
- `Only visible countries` → `Csak a látható országok` (`hu_HU/default.po`).

### Érintett
- `templates/Admin/Countries/index.php`, `webroot/css/style.css`
- `resources/locales/hu_HU/default.po`, `resources/locales/default.pot`, `doc/i18n.md`

---

## 2026-08-04 — Index `.count` oszlop szélesebb

### Mi változott / miért
- `.count`: nem elég a fix rem — **`width:1%` + `min-width:15rem`** (+ `pages/index.css`); Countries címke: *Felhasználók száma*.

### Érintett
- `webroot/css/style.css`, `webroot/css/pages/index.css`, `doc/admin-oldal.md`, `doc/admin-konvenciok.md`

---

## 2026-08-04 — Countries index: Visible only szűrő

### Mi változott / miért
- Országlista fejléc switch; session `Admin.countriesVisibleOnly`; query `visible_only=1|0`; default on.

### Érintett
- `src/Controller/Admin/CountriesController.php`
- `templates/Admin/Countries/index.php`

---

## 2026-08-04 — Mentetlen form: Swal leave confirm

### Mi változott / miért
- Add/edit (`#form-horizontal` + `pages/form.js`): ha mező változott a betöltés óta, navigáláskor `MyAdmin.confirmLeave` (Swal); ha nem dirty → szabad elmenni.
- Submit → nincs kérdés; tab zárás dirty-nál → natív `beforeunload`.

### Érintett
- `webroot/js/pages/form.js`, `webroot/js/app.js` (`confirmLeave`)
- `templates/layout/admin.php` (unsaved* messages)
- `doc/admin-konvenciok.md`, `doc/minta-tanulsagok.md`, `doc/README.md`
- `.cursor/rules/admin-form-unsaved.mdc`

---

## 2026-08-04 — is_superuser flag újra ACL-ben

### Mi változott / miért
- `CurrentUser::isSuperuser()`: `role===superuser` **vagy** CakeDC `is_superuser` (szigorú 1/true/"1").
- Flag `0` → nem superuser; flag `1` + logout/login → igen (Countries teljes jog, profil badge).

### Érintett
- `src/Auth/CurrentUser.php`, `src/Auth/CountryAccess.php`, `doc/users-auth.md`, `.cursor/rules/users-auth.mdc`

---

## 2026-08-04 — Superuser ACL = Users.role (nem CakeDC flag)

### Mi változott / miért
- `CurrentUser::isSuperuser()` **csak** `role === superuser` (nem `is_superuser` oszlop).
- Countries / Setups jogok ehhez igazodnak; `admin` + `is_superuser=0|1` → nincs teljes ország CRUD.
- Profil badge: szigorú truthy + friss DB `is_superuser` olvasás.

### Érintett
- `src/Auth/CurrentUser.php`, `src/Auth/CountryAccess.php`
- `templates/Users/profile.php`, `src/Controller/UsersController.php`

---

## 2026-08-04 — Countries view related Users/Setups; is_superuser javítva

### Mi változott / miért
- Countries `view`: kapcsolt **Users** + **Setups** tabok (mint Parents/Samples).
- `zsolt@saghysat.hu` `is_superuser` DB-ben `0` (korábban még `1` volt → badge + teljes ország edit).

### Érintett
- `templates/Admin/Countries/view.php`
- `src/Controller/Admin/CountriesController.php`, `src/Model/Table/CountriesTable.php`, `src/Model/Entity/Country.php`

---

## 2026-08-04 — Countries.user_count CounterCache (Users)

### Mi változott / miért
- `UsersTable` CounterCache → `Countries.user_count` (regisztráció / országváltás / törlés).
- Ország törlés gombja eddig stale `user_count=0` miatt aktív maradt user mellett.
- `bin/cake rebuild_counter_caches` bővítve; Translate ideiglenes kikapcsolás a rebuild alatt (ambiguous `ORDER BY id`).

### Érintett
- `src/Model/Table/UsersTable.php`, `src/Model/Table/CountriesTable.php`
- `src/Command/RebuildCounterCachesCommand.php`

---

## 2026-08-04 — Tiltott törlés gomb: `btn-secondary disabled`

### Mi változott / miért
- Gyerek rekordnál a Delete gomb egységesen **`btn-secondary disabled`** + tooltip („related child records”) — index, view related, breadcrumb.
- Korábbi `btn-outline-secondary` mintát lecserélve.
- Új Cursor rule: `.cursor/rules/admin-delete-blocked.mdc`.

### Érintett
- `templates/Admin/{Samples,Cities,Parents,Countries}/index.php`
- `templates/Admin/{Samples,Cities,Parents}/view.php` (related actions)
- `doc/admin-konvenciok.md`, `doc/minta-tanulsagok.md`
- `.cursor/rules/admin-delete-blocked.mdc`

---

## 2026-08-04 — Auth/role baseline dokumentálva új projektekhez

### Mi változott / miért
- `doc/users-auth.md` újraírva: **§0 baseline** (stabil vs képlékeny), RoleHome panelek, email login, search role-gate, afterLogin `setResult`.
- Greenfield / keret / README / `users-auth.mdc` igazítva: nincs `/{lang}`; panelek kötelező kiindulópont; role/login form projektenként változhat („majd leírjuk”).

### Érintett
- `doc/users-auth.md`, `doc/uj-projekt.md`, `doc/keretrendszer.md`, `doc/struktura.md`, `doc/README.md`
- `.cursor/rules/users-auth.mdc`

---

## 2026-08-04 — Szerepkör panelek; nincs URL nyelv-prefix

### Mi változott / miért
- Eltávolítva a `/{lang}/member/...` nyelv-prefix.
- Új panelek (Admin chrome): `/new`, `/member`, `/clubpresident`, `/president` (+ meglévő `/admin`).
- Regisztrált `new` role **csak** `/new`-ba léphet; login → `RoleHome` szerint.
- Locale: session / user ország (nem URL).

### Érintett
- `config/routes.php`, `config/permissions.php`, `config/users.php`
- `src/Auth/RoleHome.php`, `src/Controller/PanelAppController.php`
- `src/Controller/{New,Member,Clubpresident,President}/`
- `templates/{New,Member,Clubpresident,President}/`, `templates/element/{new,member,clubpresident,president}/sidebar.php`
- `src/Application.php` (EVENT_AFTER_LOGIN), `src/Controller/LocalesController.php`, `LocaleMiddleware`
- `templates/layout/admin.php`, `templates/element/admin/header.php`

---

## 2026-08-04 — Profile → Admin layout (view stílus)

### Mi változott / miért
- `/profile` a `admin` layoutot használja (nem login); template Admin `view.php` mintára (`dl.record-view-fields`, footer Change password).
- Breadcrumb: vissza gomb címkéje állítható (`breadcrumbBackLabel` → Dashboard).

### Érintett
- `templates/Users/profile.php`, `src/Controller/UsersController.php`
- `templates/element/admin/breadcrumb.php`
- `doc/users-auth.md`

---

### Mi változott / miért
- Loginon is országlista (Select2, kereshető); váltás → `?country_id=` + locale cookie; nincs a login POST-ban.
- Közös JS: `users_auth_country.js` (login + register).

### Érintett
- `templates/Users/login.php`, `templates/Users/register.php`
- `webroot/js/pages/users_auth_country.js` (régi `users_register.js` eltávolítva)
- `src/Controller/UsersController.php`

---

### Mi változott / miért
- UI nyelv megjegyzés: session + cookie `AppUiLocale` (~400 nap).
- Bejelentkezéskor a `users.country_id` → `countries.locale` felülírja a login űrlap vendégnyelvét (cookie/session).
- Admin: bejelentkezett user country locale (fallback `App.adminLocale`).

### Érintett
- `src/Utility/BrowserLocale.php`, `src/Middleware/LocaleMiddleware.php`
- `src/Controller/UsersController.php` (`login` + register persist)
- `src/Controller/Admin/AppController.php`
- `doc/users-auth.md`, `doc/i18n.md`

---

### Mi változott / miért
- Látható `countries.locale` értékekhez auth UI fordítások (`Login`, `Register`, ország, jelszó, …).
- Országváltáskor azonnal az adott locale `.po` szövegei jelennek meg.
- Generátor: `php tmp/build_auth_locale_pos.php`; `default.pot` auth msgid-ekkel kiegészítve.

### Érintett
- `resources/locales/{cs_CZ,da_DK,de_*,en_*,fi_FI,fr_FR,gl_ES,hr_HR,hu_HU,it_*,lb_LU,nl_*,pl_PL,sk_SK,sl_SI,sr_RS,uk_UA}/default.po`
- `resources/locales/default.pot`
- `tmp/build_auth_locale_pos.php`, `doc/i18n.md`, `doc/users-auth.md`

---

### Mi változott / miért
- Regisztráció `country_id`: Select2 + bootstrap-5 theme, kereshető lista (Setups working country mintára).

### Érintett
- `templates/Users/register.php`, `webroot/js/pages/users_register.js`, `webroot/css/pages/users_auth.css`
- `doc/users-auth.md`, `.cursor/rules/users-auth.mdc`

---

### Mi változott / miért
- A ValiAdmin `.login-form` absolute fill + `min-height: 0` összecsukta / szétcsúsztatta a boxot.
- Minta szerint: `.login-box.local-login` + `.local-login-form`; a face flow layoutban, magasság = tartalom.

### Érintett
- `templates/layout/login.php`, `templates/Users/*`, `webroot/css/pages/users_auth.css`
- `doc/users-auth.md`, `.cursor/rules/users-auth.mdc`

---

### Mi változott / miért
- Teljes auth playbook: `doc/users-auth.md` + rule `users-auth.mdc`; greenfield §2.9; README / struktura / keretrendszer / i18n / setups frissítve.
- Login box: tartalomhoz igazodó magasság; nincs kis logo; ValiAdmin `local-login` elkerülése (láthatatlan form).
- Register: ország első mező; cookie ≥ 1 év (`+400 days`); mentés `users.country_id`.
- Header: Profile + Change password; szélesebb `.profile-dropdown` (nowrap).
- Auth Flash: Simple Notify toast (Admin mintára); `AppView::usesFlashToast()`.

### Érintett
- `doc/users-auth.md`, `doc/uj-projekt.md`, `doc/README.md`, `doc/struktura.md`, `doc/keretrendszer.md`, `doc/i18n.md`, `doc/setups.md`
- `.cursor/rules/users-auth.mdc`, `auto-dokumentalas.mdc`
- `templates/layout/login.php`, `templates/Users/*`, `templates/element/admin/header_profile.php`
- `src/Controller/UsersController.php`, `src/View/AppView.php`, `src/Utility/AdminCountry.php`
- `webroot/css/pages/users_auth.css`, `webroot/css/style.css`, `templates/element/flash/*`

---

## 2026-08-04 — CakeDC auth UI (login layout + register country)

### Mi változott / miért
- ValiAdmin-stílusú `templates/layout/login.php`; App override: `templates/Users/*` (nem plugin path).
- Regisztráció: név, email, jelszó, ország (`visible`); lista: `Név (ISO) — locale`.
- Ország választáskor a `countries.locale` lesz az oldal nyelve; `users.country_id` migráció.
- Permissions: App Users `plugin` null — ne `plugin => false`; `SanitizeAuthRedirectMiddleware`.

### Érintett
- `templates/layout/login.php`, `templates/Users/*`
- `src/Controller/UsersController.php`, `UsersTable`, `AdminCountry`, `BrowserLocale`
- `config/users.php`, `config/permissions.php`, migráció

---

## 2026-08-04 — CakeDC Users: CakePHP 5.3 behavior deprecation fix

### Mi változott / miért
- CakeDC `sendLoginLink` / `loginWithToken` tábla-proxy hívása 5.3-ban deprecation → HTML warning → „Unable to emit headers”.
- `App\Model\Table\UsersTable` explicit metódusokkal `getBehavior('OneTimeLoginLink')`-re delegál; `config/users.php` + `Users.config`.

### Érintett
- `src/Model/Table/UsersTable.php`, `config/users.php`, `src/Application.php`

---

## 2026-08-04 — Szerepkörök + Setups jogosultság + i18n címkék

### Mi változott / miért
- `AppRoles` / `CurrentUser` / `SetupAccess` — Setups menü, URL, create/delete/country/meta csak a megfelelő szerepeknek.
- `edit_by`: `superuser` | `admin` | `president` (régi `officers` → `president`).
- Szerepkör megjelenés: `kulcs — Lefordított név` (`__()` → pot/po).

### Érintett
- `src/Auth/*`, `SetupEditBy`, `SetupsController`, templates, `config/roles.php`
- `resources/locales/default.pot`, `hu_HU/default.po`
- `doc/setups.md`, `MyAdminUsage.md`, `setups-eav.mdc`

---

## 2026-08-04 — Countries menü → Settings

### Mi változott / miért
- Sidebar: Countries a Settings csoport alá került (Setups mellé).

### Érintett
- `templates/element/admin/sidebar.php`

---

## Országnevek listázása (Select2 / AdminCountry)

- **Nem** nyelvváltás: az Admin oldal locale-ja (`App.adminLocale` / `I18n`) szerint jelennek meg a nevek (Translate `i18n`).
- Központi API: `AdminCountry::options()` → `Countries->find('visibleTranslated')`.
- Csak `Countries.visible = true`.
- Locale alias: pl. `en_UK` → `en_GB` (`AdminCountry::normalizeTranslateLocale`).
- ABC sorrend a **fordított** név szerint.

```php
use App\Utility\AdminCountry;

// Select2: id => "Magyarország (HU)" ha adminLocale = hu_HU
$options = AdminCountry::options();
```

---

## 2026-08-04 — Setups: ország-scope, multi-create, edit_by, secret típus

### Mi változott / miért
- Index: csak working country; címsor „Listing settings for {ország}”; Select2; default HU.
- Új felvitel: `createForAllCountries()` — minden látható országra ugyanaz a slug.
- `edit_by` (`admin` | `officers`) — jövőbeli Users szerepkörökhöz.
- Új típus: `secret` (password mező, Security::encrypt tárolás).
- Unique `(country_id, slug)`; leírás mező nincs a sémában.

### Érintett
- DB `setups`, `SetupsTable` / `Setup`, `SetupsController`, templates, `SetupValue`, `SetupEditBy`, `AdminCountry`
- `doc/setups.md`, `MyAdminUsage.md`, `setups-eav.mdc`, `.po`

---

## 2026-08-03 — AdminCountry: ambiguous `visible` (Countries + i18n)

### Mi változott / miért
- Az `i18n` táblának is van `visible` oszlopa; Translate join mellett a minősítetlen `visible` / `iso2` SQL hibát adott.
- `AdminCountry` lekérdezései: `Countries.visible`, `Countries.iso2`, `Countries.id`.

### Érintett
- `src/Utility/AdminCountry.php`

---

## 2026-08-03 — MyAdminUsage.md (programozói cheat sheet)

### Mi változott / miért
- `doc/MyAdminUsage.md`: gyakorlati útmutató a kódba nyúláshoz — első bejegyzés: `Setup::get()`.

### Érintett
- `doc/MyAdminUsage.md`, `doc/README.md`, `doc/setups.md`

---

## 2026-08-03 — Setup::get() — értékolvasás bárhonnan

### Mi változott / miért
- `App\Utility\Setup::get($slug, $default)` — static facade a `SetupsTable::getValue()` fölött, hívható controller / view / utility / CLI-ből `fetchTable` nélkül.

### Érintett
- `src/Utility/Setup.php`, `doc/setups.md`, `admin-konvenciok.md`, `setups-eav.mdc`

---

## 2026-08-03 — Setups specek teljes dokumentálás

### Mi változott / miért
- `doc/setups.md` bővítve (slug `_`, típusok, fájllista, form/controller, hibák).
- Hivatkozások: README, admin-oldal, admin-konvenciok, crud-utmutato, i18n, minta-tanulsagok, auto-dokumentalas, `setups-eav.mdc`.

### Érintett
- `doc/setups.md`, `admin-oldal.md`, `i18n.md`, `minta-tanulsagok.md`, `valtozasok.md`
- `.cursor/rules/setups-eav.mdc`, `auto-dokumentalas.mdc`
- `resources/locales/default.pot` (Setups msgid-ek)

---

## 2026-08-03 — Setups slug: `_` elválasztó (nem `-`)

### Mi változott / miért
- Slug csak `a-z0-9` + aláhúzás; javaslat és validáció ennek megfelelően.

### Érintett
- `SetupValue`, `SetupsTable`, `setups_form.js`, form, `doc/setups.md`, `setups-eav.mdc`, `.po`

---

## 2026-08-03 — Setups: típusos beállítások CRUD

### Mi változott / miért
- Új `setups` EAV modul: type-függő érték widget (string/text/int/float/bool/date/time/datetime/json/array), slug validáció + név javaslat, teljes Admin CRUD + keresés.
- Spec a későbbi projektekhez: `doc/setups.md`.

### Érintett
- `config/schema/setups.sql`, `SetupValue`, `SetupsTable` / `Setup`, `SetupsController`
- `templates/Admin/Setups/*`, `pages/setups_form.js|css`, sidebar, `admin_search.php`, Search labels
- `doc/setups.md`, specek

---

## 2026-08-03 — Index URL: page mindig + első oldal fix

### Mi változott / miért
- Cake elhagyta a `page=1`-et → session visszaírta a régi oldalt (első oldal gomb „nem működött”).
- `App\View\Helper\PaginatorHelper`: `page=1` is az URL-ben; kanonikus redirect; üres index → mentett URL.
- Lapozáskor (page változás) → `clearLastVisited`; listaállapot könyvjelzőzhető.

### Érintett
- `src/View/Helper/PaginatorHelper.php`, `Admin/AppController.php`, Samples/Parents/Cities/Countries `index`
- docs + `admin-kereses-index-allapot.mdc`, `admin-paginator.mdc`

---

## 2026-08-03 — Vissza a tetejére gomb

### Mi változott / miért
- Jobb alsó FA felfelé nyíl; csak lejjebb görgetve látszik; kattintásra az oldal tetejére görget.

### Érintett
- `templates/layout/admin.php`, `webroot/js/app.js`, `webroot/css/style.css`, `hu_HU/default.po`

---

## 2026-08-03 — Lapozó: FA ikonok + last-visited törlés + keresés→1. oldal

### Mi változott / miért
- Paginator: szöveg helyett FA `angle-double-left` / `angle-left` / `angle-right` / `angle-double-right` (aria/title marad fordítva).
- Lapozáskor (`?page=`) törlődik a last-visited kiemelés.
- Keresés submit (form: `q` van, `page` nincs) → mindig 1. oldal (javítás).

### Érintett
- `templates/element/admin/index_pagination.php`, `Admin/AppController.php`
- `.cursor/rules/admin-paginator.mdc`, `admin-kereses-index-allapot.mdc`
- `doc/admin-konvenciok.md`, `admin-oldal.md`, `uj-projekt.md`

---

## 2026-08-03 — Keresőmező: kurzor a szöveg végén keresés után

### Mi változott / miért
- Keresés után a fókusz a keresőmezőn marad, kurzor a lekérdezés végén (továbbszerkesztés).

### Érintett
- `webroot/js/app.js` (`focusActiveSearchField`), `webroot/js/pages/index.js` (aktív keresésnél nincs last-visited scroll), `templates/Admin/Search/index.php`

---

## 2026-08-03 — Dokumentáció: keresés / lapozó / pénz specek + cursor rules

### Mi változott / miért
- Összefoglaló specek a jövőbeli agent munkához: lapozó First…Last, globális keresés UI + config kulcsok, `labelsKey`, element inventory.
- Új rule: `.cursor/rules/admin-paginator.mdc`; bővítve: `admin-kereses-index-allapot.mdc`, `auto-dokumentalas.mdc` (rule+doc együtt).

### Érintett
- `doc/README.md`, `admin-konvenciok.md`, `admin-oldal.md`, `uj-projekt.md`, `i18n.md`, `keretrendszer.md`, `minta-tanulsagok.md`, `struktura.md`
- `.cursor/rules/admin-paginator.mdc`, `admin-kereses-index-allapot.mdc`, `auto-dokumentalas.mdc`
- `config/admin_search.php` (komment)

---

## 2026-08-03 — Paginator: First / Previous / Next / Last

### Mi változott / miért
- `admin/index_pagination`: First + Previous + számsor + Next + Last; első/utolsó oldalon disabled (Cake `hasPrev` / `hasNext`).

### Érintett
- `templates/element/admin/index_pagination.php`, `hu_HU/default.po`

---

## 2026-08-03 — Globális keresés lapozás + `index_counter` element

### Mi változott / miért
- `/admin/search` eddig nem lapozott → `PaginatedResultSet` + `admin/index_pagination` (fent) + `admin/index_footer` (lent).
- Config: `globalPageLimit` (20), `globalLimitPerModel` (200), `globalMaxResults` (1000).
- Footer bal összesítő külön element: `admin/index_counter` (a lapozó már `index_pagination` volt).

### Érintett
- `SearchController`, `AdminSearch`, `admin_search.php`, `Search/index.php`
- `templates/element/admin/index_counter.php`, `index_footer.php`

---

## 2026-08-03 — Globális keresés UI: Google-szerű találatok + modal

### Mi változott / miért
- `/admin/search` találatok: cite (tábla · #id), kék cím → AJAX modal (összes mező + **Table** sor), szem ikon = modal, ceruza = edit.
- `pages/search.css`; `data-source-table` a linked modalban; `admin_search.php` → `labelsKey`.

### Érintett
- `templates/Admin/Search/index.php`, `webroot/css/pages/search.css`, `pages/index.js`, `admin_search.php`, layout messages

---

## 2026-08-03 — Keresés/index állapot: greenfield kötelező specek

### Mi változott / miért
- A keresés + session index állapot + clear→last-visited **minden új projekt** kötelező része.
- Playbook: `uj-projekt.md` §2.8; agent rule frissítve; README / minta-tanulsagok / admin-konvenciok checklist.

### Érintett
- `doc/uj-projekt.md`, `README.md`, `minta-tanulsagok.md`, `admin-konvenciok.md`
- `.cursor/rules/admin-kereses-index-allapot.mdc`

---

## 2026-08-03 — Keresés törlése → last-visited oldal

### Mi változott / miért
- `clear_search` után nem `page=1`, hanem a **last-visited** rekord oldala a szűretlen listában (`resolveIndexPageForLastVisited` + `findRecordPageNumber`).
- Ha nincs last-visited: a keresés előtti oldal (`_pageBeforeSearch`).
- Scroll a meglévő `.last-visited` logikával.

### Érintett
- `Admin/AppController.php`; Samples/Parents/Cities/Countries `index()`

---

## 2026-08-03 — Keresés törlése gomb (index + header)

### Mi változott / miért
- Nagyító mellett **×** gomb: `__('Clear search')` → „Keresés törlése”.
- Index: `?clear_search=1` → session `q` törlése, `page=1`, szűretlen lista (sort megmarad).
- Header / Search oldal: üres `/admin/search`. Üres keresésnél disabled.
- Tooltip magyarázat (HTML): index → „Search in the text fields of this list.”; globális → „…of all configured tables.” (+ clear magyarázatok); hu `.po`.

### Érintett
- `templates/element/admin/{table_search,header_search}.php`, `Admin/Search/index.php`
- `Admin/AppController::applyIndexListState`, `style.css`, `.po`

---

## 2026-08-03 — Index / globális keresés + lista-állapot session + last-visited scroll

### Mi változott / miért
- **Index kereső** (`admin/table_search`): az adott model szöveges mezőiben (`config/admin_search.php`) — szűrt lista.
- **Header kereső** (`admin/header_search`): összes model összes szöveges mezője → `/admin/search`.
- Nagyító gomb + tooltip `__('Start search')` / hu: „Keresés indítása”.
- Session `Admin.indexState[Alias]`: sort, direction, page, `q` — visszatérés ugyanarra az oldalra / szűrőre; edit/add save → `redirectToIndexList()`.
- Last-visited sor: görgetés a breadcrumb alá (~mt-3).
- Új projekt: `admin_search.php` mezőlista kötelező az első felépítéskor.

### Spec
- [admin-konvenciok.md](admin-konvenciok.md), [uj-projekt.md](uj-projekt.md), [struktura.md](struktura.md)

### Érintett
- `config/admin_search.php`, `config/bootstrap.php`
- `src/Utility/AdminSearch.php`, `Admin/AppController.php`, `Admin/SearchController.php`
- CRUD `index()` + save redirect; `templates/element/admin/{table_search,header_search,breadcrumb}.php`
- `templates/Admin/Search/index.php`, `webroot/js/pages/index.js`, `webroot/css/style.css`
- `.cursor/rules/admin-kereses-index-allapot.mdc`

---

## 2026-08-03 — Agent rule: pénznem `formatCurrency`

### Mi változott / miért
- Cursor rule az agentnek: pénz UI → `LocaleNumberParser::formatCurrency()` (HUF, ICU); tilos kézi Ft/HUF összerakás.

### Érintett
- `.cursor/rules/penznem-formatcurrency.mdc`

---

## 2026-08-03 — Pénznem (netto): ICU `formatCurrency` (HUF, locale pozíció)

### Mi változott / miért
- Angol UI-n a `netto` eddig mindig „… Ft” volt (suffix + hardcode); ICU szerint **en → `HUF 12,345.67`**, hu → `12 345,67 Ft`, de/fr/sk → összeg + `HUF`.
- Új: `LocaleNumberParser::formatCurrency()` — teljes pénzstring (pozíció, szóköz, szimbólum).
- `currencySymbol()` ICU-ból (`@currency=HUF`): hu → `Ft`, en → `HUF` (önmagában ritkán kell; megjelenítéshez `formatCurrency`).

### Spec
- [admin-konvenciok.md](admin-konvenciok.md), [i18n.md](i18n.md), [admin-oldal.md](admin-oldal.md)

### Érintett
- `src/Utility/LocaleNumberParser.php`
- `templates/Admin/Samples/{index,view}.php`, `Cities/view.php`, `Parents/view.php`
- `src/Controller/Admin/SamplesController.php` (`recordGet`)

---

## 2026-08-03 — Számmezők: egységes locale formázás (ezres + tizedes)

### Mi változott / miért
- `LocaleNumberParser::formIntegerOptions()` / `formDecimalOptions()` — minden Admin szám input: `type=text`, class, **locale value**, placeholder (`1 234` / `1 234,56`).
- `jsConfig()` bővítve: `groupSize`, `decimalDigits`, placeholderek; inputmask `autoGroup` + `groupSize: 3`.

### Spec
- [admin-konvenciok.md](admin-konvenciok.md); [middleware.md](middleware.md)

### Érintett
- `src/Utility/LocaleNumberParser.php`, `webroot/js/pages/form.js`
- `templates/Admin/{Samples,Parents,Cities,Countries}/form.php`

---

## 2026-08-03 — Form számmezők: inputmask minden Admin formon

### Mi változott / miért
- Minden Admin `form.php` betölti az **inputmask** plugint + `numberFormat`.
- Egész szám: `.js-input-integer` (és `#pos` / `name=pos` fallback a `form.js`-ben) — csak számjegyek, locale ezres csoportosítás.
- Tizedes: `.js-input-decimal` (Samples `netto`).
- Parents `pos` eddig maszk nélkül volt; Cities/Countries-nél a plugin hiányzott.

### Spec
- [admin-konvenciok.md](admin-konvenciok.md) számmezők; [minta-tanulsagok.md](minta-tanulsagok.md) §6

### Érintett
- `webroot/js/pages/form.js`
- `templates/Admin/{Parents,Cities,Countries}/form.php`

---

## 2026-08-03 — Index footer: Cake bake Paginator::counter (i18n)

### Mi változott / miért
- `admin/index_footer` bal oldala: bake sablon szövege  
  `__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')`  
  → `$this->Paginator->counter(...)` (placeholderek a msgid-ben maradnak; `.po` fordítja; számok locale formázással).
- hu: `{{page}}. oldal / {{pages}}, megjelenítve: {{current}} rekord, összesen: {{count}}`

### Spec
- [admin-konvenciok.md](admin-konvenciok.md) Index card footer; [admin-oldal.md](admin-oldal.md); [i18n.md](i18n.md)

### Érintett
- `templates/element/admin/index_footer.php`
- `resources/locales/hu_HU/default.po`

---

## 2026-08-03 — Countries/Continents kód ↔ séma szinkron

### Mi változott / miért
- ORM / controller / template igazítva a DB sémához: `countries.continent_id` → `belongsTo Continents`; nincs `continent` string mező; modalban nincs i18n lista.
- `CountriesTable` / `ContinentsTable`: teljes oszlop-validáció + `iso2`/`code` unique + `existsIn(continent_id)`.
- Template mezősorrend a séma szerint: iso2 → name → locale → continent → visible → pos → user_count.

### Érintett
- `src/Model/{Entity,Table}/Country*`, `Continent*`
- `src/Model/Entity/I18nTranslation.php`
- `src/Controller/Admin/CountriesController.php`
- `templates/Admin/Countries/{index,form,view}.php`

---

## 2026-08-03 — Modal: i18n fordításlista tiltva (minden recordGet)

### Mi változott / miért
- A gyorsnézet modal **ne** listázza a kapcsolt `i18n` EAV sorokat (Countries `translations` mező kikerült a `recordGet`-ből és a `recordFieldLabels`-ből).
- Tartós szabály: Translate csak a megjelenített mező locale szerinti értékére kell; a modalban nincs fordítás-felsorolás.

### Spec
- [i18n.md](i18n.md) → Országnevek

### Érintett
- `src/Controller/Admin/CountriesController.php`
- `templates/Admin/Countries/index.php`

---

## 2026-08-03 — Countries ID újraszámozás (1..N) + i18n sync

### Mi változott / miért
- `countries.id` **267…532** → **1…266** (hézagmentes); `AUTO_INCREMENT = 267`.
- `i18n.foreign_key` (`model=Countries`) ugyanezzel az offsettel frissítve — fordítások megmaradtak (67032 sor, 0 árva).

```bash
php tmp/renumber_countries_ids.php
```

### Spec
- [i18n.md](i18n.md) (kapcsolat: `foreign_key` = szülő `id`)

### Érintett
- DB: `countries`, `i18n`
- `tmp/renumber_countries_ids.php`

---

## 2026-08-03 — i18n árva rekordok tisztítása

### Mi változott / miért
- `bin/cake cleanup_i18n_orphans` (+ `--dry-run`): törli azokat az `i18n` sorokat, ahol a `foreign_key` már nem létezik a szülő táblában (`Countries`→`countries`, `Continents`→`continents`); ismeretlen `model` → teljes wipe.
- Aktuális DB: **0 árva** (Countries 266 fk / Continents 7 fk egyezik a szülőkkel; 68796 sor maradt).

### Spec
- [i18n.md](i18n.md) → Árva i18n sorok tisztítása

### Érintett
- `src/Command/CleanupI18nOrphansCommand.php`
- `tmp/cleanup_i18n_orphans.php`

---

## 2026-08-03 — Index card footer: közös `admin/index_footer` (minden listán)

### Mi változott / miért
- Countries (és minden Admin index) bal alsó láblécében hiányzott a **X–Y / Z records | N. page / M** infó (Samples-en megvolt).
- Új element: `templates/element/admin/index_footer.php` — bal summary + jobb `index_pagination`.
- Samples / Parents / Cities / Countries: inline footer → `<?= $this->element('admin/index_footer') ?>`.

### Spec
- [admin-konvenciok.md](admin-konvenciok.md) Index card footer; [admin-oldal.md](admin-oldal.md) §4; [struktura.md](struktura.md); [README.md](README.md) Rögzített döntések

### Érintett
- `templates/element/admin/index_footer.php`
- `templates/Admin/{Samples,Parents,Cities,Countries}/index.php`

---

## 2026-08-03 — Összefoglaló: Countries / Continents / Admin lista (mai kör)

### Döntések
| Téma | Döntés |
|------|--------|
| Countries Admin | Csak **visible** + **pos** szerkeszthető; nincs add/delete; név/locale/continent seed |
| Országnév | `countries.name` (EN) + Translate `i18n` (`model=Countries`) — UI locale szerint |
| Földrész | Külön **`continents`** tábla + `countries.continent_id`; Translate `model=Continents` (CLDR, minden locale) |
| Lapozás | `$indexLimit = 100`, `$indexMaxLimit = 1000` (AppController + CRUD-ok) |
| Index footer | Kötelező `admin/index_footer` (rekord/oldal infó + lapozó) |
| Modal fordítások | **Ne** listázd az i18n EAV sorokat a modalban (`recordGet` / `recordFieldLabels`) |
| `pos` | Mindig DB DEFAULT (`.cursor/rules/pos-db-default.mdc`) |

### Seed
```bash
php tmp/seed_continents.php   # continents + i18n + continent_id migráció
```

### Spec fájlok
- [i18n.md](i18n.md) — Országnevek + Continents
- [struktura.md](struktura.md) — Countries / Continents / elementek
- [admin-konvenciok.md](admin-konvenciok.md) / [admin-oldal.md](admin-oldal.md) — index footer, limit

---

## 2026-08-03 — Continents tábla + countries.continent_id (Translate)

### Mi változott / miért
- Új `continents` tábla (`code`, angol `name`, `visible`, `pos`); Cake Translate EAV (`model=Continents`).
- CLDR territory kódokból i18n fordítás **minden** Countries-locale-ra (252 × 7).
- `countries.continent` string mező helyett `continent_id` FK (`belongsTo Continents`).
- Admin lista/view/form/modal: `$country->continent->name` az UI locale szerint.

### Spec
- [i18n.md](i18n.md); `config/schema/continents.sql`, `countries.sql`

### Érintett
- `tmp/seed_continents.php`, `tmp/cldr_territories/*`
- `src/Model/{Table,Entity}/Continent*`
- `CountriesTable` / `Country` / `CountriesController`
- `templates/Admin/Countries/*`

---

## 2026-08-03 — Countries: `continent` (földrész) mező

### Mi változott / miért
- `countries.continent` — angol földrésznév (UN M49 + legacy ISO aliasok); a lista későbbi csoportosításához.
- Feltöltés: `php tmp/update_countries_continent.php` (+ `tmp/iso3166_regions.csv`).
- Admin: oszlop / view / form (read-only) / modal; alap rendezés: continent ASC, name ASC.
- UI címke + érték `__()` (hu: Földrész / Európa, …).

### Spec
- [i18n.md](i18n.md) → Országnevek; `config/schema/countries.sql`

### Érintett
- `config/schema/countries.sql`
- `tmp/update_countries_continent.php`, `tmp/iso3166_regions.csv`
- `src/Model/Entity/Country.php`, `CountriesController`
- `templates/Admin/Countries/{index,view,form}.php`
- `resources/locales/hu_HU/default.po`

---

## 2026-08-03 — Index lapozás: 100 / 1000

### Mi változott / miért
- Admin alap: `$indexLimit = 100`, `$indexMaxLimit = 1000` (`AppController` + Samples / Parents / Cities / Countries).

### Spec
- [admin-konvenciok.md](admin-konvenciok.md), [admin-oldal.md](admin-oldal.md)

### Érintett
- `src/Controller/Admin/AppController.php`
- `SamplesController`, `ParentsController`, `CitiesController`, `CountriesController`

---

## 2026-08-03 — Modal: Countries fordítások megjelenítése ([object Object] javítás)

### Mi változott / miért
- A gyorsnézet modalban a `translations` mező `[object Object],…` szöveget mutatott, mert objektumtömbre `String()` futott.
- `recordGet` most `[{locale, name, visible}, …]` listát ad (i18n tábla, ABC locale).
- `index.js`: `renderTranslationList` + `content`/`name` fallback; objektum/tömb soha nem `String()`-gel.

### Spec
- [i18n.md](i18n.md) → Országnevek (modal: csak olvasható lista)

### Érintett
- `src/Controller/Admin/CountriesController.php`
- `templates/Admin/Countries/index.php`
- `webroot/js/pages/index.js`

---

## 2026-08-03 — Countries Admin: csak visible + pos (i18n UI nélkül)

### Mi változott / miért
- Országok referenciaadat (seed): Adminban **csak** `visible` és `pos` módosítható.
- i18n fordítások **nem** jelennek meg / szerkeszthetők a formon — a Translate csak arra kell, hogy mindenki a saját nyelvén lássa az országnevet.
- Nincs add / delete a Countries UI-n.

### Spec
- [i18n.md](i18n.md) → Országnevek; [struktura.md](struktura.md)

### Érintett
- `src/Controller/Admin/CountriesController.php`, `CountriesTable.php`, `Entity/Country.php`
- `templates/Admin/Countries/*`

---

## 2026-08-03 — Countries locale lista: összes ország (nem csak en/hu/GB)

### Mi változott / miért
- A form / modell nem szűkült `['en_US','hu_HU','en_GB']`-re: `translationLocales()` = Member nyelvek + **minden ország** primary `locale`-ja; `primaryLocaleOptions()` ugyanez a selecthez.
- Seed: i18n minden country-primary locale-ra is (ICU).

### Spec
- [i18n.md](i18n.md) → Országnevek

### Érintett
- `src/Model/Table/CountriesTable.php`, `src/Controller/Admin/CountriesController.php`
- `tmp/seed_countries.php`

---

## 2026-08-03 — Szabály: `pos` = csak DB DEFAULT (örök)

### Mi változott / miért
- Rögzítve: a `pos` értékét az agent **soha** ne állítsa / ne írja felül — mindig a séma DEFAULT; a felhasználó módosítja ha kell.
- Cursor rule: `.cursor/rules/pos-db-default.mdc` (`alwaysApply`).

### Spec
- [README.md](README.md) Rögzített döntések; meglévő: [admin-konvenciok.md](admin-konvenciok.md), [crud-utmutato.md](crud-utmutato.md), [struktura.md](struktura.md)

### Érintett
- `.cursor/rules/pos-db-default.mdc`

---

## 2026-08-03 — Tempus edit: mentett érték megjelenik (JeffAdmin5 setValue)

### Mi változott / miért
- Dátum / dátumidő szerkesztéskor üres maradt: a Tempus 6 natívan nem parse-olta a locale display stringet.
- JeffAdmin5 minta: init előtt input ürítés ha van ISO `data-picker-value`; `parseInput(moment(...).toDate())` + `setValue`; saját moment `parseInput` / `formatInput`.

### Spec
- [minta-tanulsagok.md](minta-tanulsagok.md) §6 (Edit init érték) + gyakori hibák

### Érintett
- `webroot/js/pages/form.js`

---

## 2026-08-03 — Countries Admin CRUD + nyelvenkénti láthatóság

### Mi változott / miért
- Teljes Admin modul: `CountriesController` + index/form/view; sidebar **Countries**.
- Form: angol név + primary locale + **minden nyelv** fordítása és **Visible** kapcsoló (`i18n.visible`).
- Ország szintű `visible` / `pos`; törlésvédelem `user_count`-tal.
- Modal: fordításlista megjelenítés (`index.js`).

### Spec
- [i18n.md](i18n.md) → Országnevek; `config/schema/i18n.sql` (`visible`)

### Érintett
- `src/Controller/Admin/CountriesController.php`
- `src/Model/Table/CountriesTable.php`, `Entity/Country.php`, `I18nTranslation.php`
- `templates/Admin/Countries/*`, `element/admin/sidebar.php`
- `webroot/js/pages/index.js`, `resources/locales/hu_HU/default.po`

---

## 2026-08-03 — Countries + i18n seed (összes ország, fordítások, locale)

### Mi változott / miért
- `countries` feltöltve (~266 ISO régió/ország): `iso2`, angol `name`, ország **primary** `locale` (pl. HU→`hu_HU`).
- CakePHP Translate EAV `i18n`: minden ország neve a `config/languages.php` nyelvein + `en_US` / `hu_HU` (ICU `Locale::getDisplayRegion`) — pl. HU: Magyarország / Hungary / Ungarn.
- Model: `CountriesTable` + `Translate` (`EavStrategy`), `Country` entitás; `I18nTable` / `I18nTranslation`.
- Séma: `iso2` UNIQUE; `name` varchar(150); seed: `php tmp/seed_countries.php`.

### Spec
- [i18n.md](i18n.md) → „Országnevek (DB Translate)”
- `config/schema/countries.sql`, `config/schema/i18n.sql`

### Érintett
- DB: `countries`, `i18n`
- `src/Model/Table/CountriesTable.php`, `I18nTable.php`
- `src/Model/Entity/Country.php`, `I18nTranslation.php`
- `tmp/seed_countries.php`

---

## 2026-08-03 — Tempus AM/PM: látható gomb (kék-a-kéken fix)

### Mi változott / miért
- 12h módban az AM/PM csak hoverre látszott: a light téma kék gombháttere + a mi `color:#0d6efd` szövege = azonos szín.
- Override: világoskék háttér + kék szöveg + keret (`button[data-action=toggleMeridiem]`), hover: sötétebb kék.

### Spec
- [minta-tanulsagok.md](minta-tanulsagok.md) §6 (AM/PM gomb) + gyakori hibák

### Érintett
- `webroot/css/style.css`

---

## 2026-08-03 — SweetAlert2: popup árnyék

### Mi változott / miért
- A SWAL panelnek legyen látható mélysége (mint a Bootstrap linked modal / Tempus), ne „lapos” fehér doboz.
- CSS: `.swal2-popup` box-shadow; `.swal2-container` z-index továbbra is `20000`.

### Spec
- [admin-konvenciok.md](admin-konvenciok.md) → SweetAlert „Kinézet”
- [admin-oldal.md](admin-oldal.md) §8, [minta-tanulsagok.md](minta-tanulsagok.md) §5 / §8, [keretrendszer.md](keretrendszer.md), [README.md](README.md)

### Érintett
- `webroot/css/style.css`

---

## 2026-08-03 — Tempus: idő és dátumidő óra egy család

### Mi változott
- Közös `tempusClockComponents` (time + datetime); ugyanazok az ikonok/gombok; time: explicit `calendar:false` + `clock:true`.
- CSS: `.time-container-clock` egységes (számok, AM/PM, szeparátor); datetime SBS: bal szegély az óra mellett.

### Érintett
- `webroot/js/pages/form.js`, `webroot/css/style.css`, `doc/minta-tanulsagok.md`

---

## 2026-08-03 — Tempus idő: locale AM/PM vs 24h

### Mi változott
- `dateFormat.useTwentyFourHour`: `en_US` → 12h + **AM/PM**; hu/de/… → 24h (nincs DE/DU).
- DateTime `setLocale(intl)` — a meridiem a picker nyelvét követi.
- Mentés: `LocaleDateParser` elfogadja az `2:30:00 PM` formátumot is.

### Érintett
- `src/Utility/LocaleDateParser.php`, `webroot/js/pages/form.js`
- `doc/middleware.md`, `doc/minta-tanulsagok.md`

---

## 2026-08-03 — View/index/modal: dátum locale szerint

### Mi változott
- `LocaleDateParser`: `datetime_short` / `time_short` (lista/view/modal, mp nélkül).
- View / index / form fejléc / `recordGet`: nincs hardcode `Y.m.d.` — `LocaleDateParser::format()` + meglévő számformázók.

### Érintett
- `src/Utility/LocaleDateParser.php`
- `templates/Admin/{Samples,Parents,Cities}/{view,index}.php`, form fejlécek
- `SamplesController` / `ParentsController` / `CitiesController` JSON
- `doc/minta-tanulsagok.md`, `doc/middleware.md`

---

## 2026-08-03 — Agent: automatikus dokumentálás (mindig)

### Mi változott
- Kötelező: lényeges módosítás → `doc/` frissítés **ugyanabban a körben**; ne kelljen külön kérni.
- Cursor rule: `.cursor/rules/auto-dokumentalas.mdc` (`alwaysApply`).
- `doc/README.md` Agent szabályok frissítve.

### Érintett
- `.cursor/rules/auto-dokumentalas.mdc`, `doc/README.md`

---

## 2026-08-03 — Dokumentáció: éles DB playbook frissítve

### Mi változott
- `minta-tanulsagok.md`: §0.1 fájllista, §0.2 `App.adminLocale`, §6–6c (Tempus locale/hétkezdet, szám MW, mezőhiba), view footer, §11–14 agent éles indulás.
- Szinkron: `README`, `i18n`, `keretrendszer`, `uj-projekt`, `admin-oldal`, `struktura`, `admin-konvenciok`, `middleware`.

### Érintett
- `doc/minta-tanulsagok.md` + fenti specek

---

## 2026-08-03 — View Edit gomb: adatoszlop alatt

### Mi változott
- View lábléc nem `offset-md-2` (az form labelhez való); `.record-view-footer-actions` → `9rem + 1rem` (dt + gap), az értékoszloppal egy vonalban.

### Érintett
- `templates/Admin/{Samples,Parents,Cities}/view.php`
- `webroot/css/style.css`, `doc/admin-konvenciok.md`

---

## 2026-08-03 — Tempus picker: locale a nyelvből (en/hu)

### Mi változott
- `LocaleDateParser::jsConfig()`: `intl`, `moment`, `startOfTheWeek` (követi `App.adminLocale`).
- `form.js`: ne hardcode `hu` — naptár Intl + mezőformátum a `dateFormat` configból; mentés továbbra is middleware.

### Érintett
- `src/Utility/LocaleDateParser.php`, `webroot/js/pages/form.js`, `doc/middleware.md`

---

## 2026-08-03 — Szám middleware + Form errorClass deprecation

### Mi változott / miért
- `errorClass` config deprecated (Cake 5.2+) → `templates.errorClass` = `is-invalid`.
- `LocaleNumberParser::looksLikeNumber`: a `1 234 567` típusú ezres csoport **ne** dátumnak számítson (korábban kihagyta a normalizálást → „must be an integer”).
- Form inputmask: `autoUnmask` + `removeMaskOnSubmit` (middleware továbbra is fallback).

### Érintett
- `src/View/AppView.php`
- `src/Utility/LocaleNumberParser.php`
- `webroot/js/pages/form.js`
- `doc/middleware.md`

---

## 2026-08-03 — Form mezőhibák: wrapper alatt (Select2 / Tempus / checkbox)

### Mi változott
- Összetett widgetnél (`input-group`, `select2-with-add`, checkbox) a hiba a wrapper **alatt**: `'error' => false` + `element('admin/field_error')`.
- CSS: `.error-message` kötelezően piros / félkövér; flex sorban nem szorul mellé.

### Érintett
- `templates/element/admin/field_error.php`
- `templates/Admin/{Samples,Parents,Cities}/form.php`
- `src/View/AppView.php`, `webroot/css/style.css`, `doc/admin-konvenciok.md`

---

## 2026-08-03 — Tempus picker: világos panel, keret, árnyék

### Mi változott
- Tempus `display.theme: 'light'` (ne rendszer dark auto).
- Popup: fehér háttér, `#ced4da` szegély, lágy box-shadow.

### Érintett
- `webroot/js/pages/form.js`, `webroot/css/style.css`

---

## 2026-08-03 — Form mezőhibák: mező alatt, piros félkövér

### Mi változott
- Admin Form helper (`AppView`): `errorClass=is-invalid`; hiba a control után (`.error-message`).
- CSS: piros (`#dc3545`), `font-weight: 700`; input-groupban teljes sorba tördelve.

### Érintett
- `src/View/AppView.php`, `webroot/css/style.css`
- `templates/Admin/{Samples,Cities}/form.php` (helyi template override eltávolítva)

---

## 2026-08-03 — Dátum mentés: locale middleware + Tempus formatInput

### Mi változott / miért
- Tempus 6.0 `formatInput` Intl hu → `2024. 03. 15.` (szóköz) — Cake `date(ymd)` elutasította.
- `LocaleDateParser`: szóközös / DMY / MDY / ISO; `format()` + `jsConfig()`.
- `form.js`: moment `formatInput` felülírás + `MyAdmin.config.dateFormat`.
- Szám parser nem eszi meg a szóközös dátumot.

### Érintett
- `src/Utility/LocaleDateParser.php`, `LocaleNumberParser.php`
- `webroot/js/pages/form.js`, `templates/Admin/Samples/form.php`
- `doc/middleware.md`, `minta-tanulsagok.md`

---

## 2026-08-03 — Form szám maszk: nagy számok (inputmode)

### Mi változott
- Inputmask: `inputmode: 'text'` (ne `decimal`/`numeric`) — ezres csoportosítással a nagy számok begépelése nem akad el ~3 jegy után.
- `shortcuts: null`; templateből kikerült a `inputmode` attr a Samples számmezőkről.

### Érintett
- `webroot/js/pages/form.js`, `templates/Admin/Samples/form.php`
- Spec: `admin-konvenciok.md`, `middleware.md`

---

## 2026-08-03 — View/form footer: `offset-md-2`

### Mi változott
- View Edit és form Save/Cancel: `col-12 col-md-10 col-xxl-9 offset-md-2` (label oszloppal egy vonalban).

### Érintett
- `templates/Admin/{Samples,Parents,Cities}/view.php` (+ Parents/Cities `form.php`)

---

## 2026-08-03 — Form hr: csak `visible` fölött, mezőszélességgel

### Mi változott
- Samples: a `logikai` fölötti `<hr>` eltávolítva; az elválasztó csak a **`visible`** fölött.
- Markup minden formon: `<div class="row"><div class="col-12 col-xxl-11"><hr class="my-4"></div></div>` (nem teljes szélességű bare `<hr>`).

### Érintett
- `templates/Admin/{Samples,Parents,Cities}/form.php`
- Spec: `admin-konvenciok.md`, `admin-oldal.md`, `minta-tanulsagok.md`

---

## 2026-08-03 — View footer: nincs „Back to list”

### Mi változott
- View card footer: csak **Edit**; a „Back to list” a breadcrumbben marad (ne duplikáld).

### Érintett
- `templates/Admin/{Samples,Parents,Cities}/view.php`
- Spec: `admin-konvenciok.md`, `admin-oldal.md`

---

## 2026-08-03 — Form: `visible` + `pos` elválasztva, sorrend rögzítve

### Mi változott
- Minden Admin formon (`Samples`, `Parents`, `Cities`): a `visible` / `pos` blokk a **többi mező után** áll; felettük `<hr class="my-4">`.
- Sorrend **minden esetben**: először **visible**, alatta **pos**.

### Érintett
- `templates/Admin/{Samples,Parents,Cities}/form.php`
- Spec: `admin-konvenciok.md`, `admin-oldal.md`, `minta-tanulsagok.md`, `README.md`

---

## 2026-08-03 — Éles DB dokumentáció frissítve

### Mi változott
- **[minta-tanulsagok.md](minta-tanulsagok.md)** bővítve: §0 éles-adatbázis playbook, Delete UI, Flash Notify/SWAL, Tempus Dominus, modul checklist (§11).
- Összehangolva: `README.md` (rögzített döntések), `uj-projekt.md`, `crud-utmutato.md`, `middleware.md`, `admin-oldal.md`, `admin-konvenciok.md`, `keretrendszer.md`.

### Miért
Következő éles DB CRUD-nál az agent a `doc/`-ból (főleg `minta-tanulsagok.md` §0–11) építsen, ne a chatelőzményből.

---

## 2026-08-03 — Törlés gomb: secondary + disabled ha nem törölhető

### Mi változott
- Nem törölhető rekord: Delete = **`btn-secondary` / `btn-outline-secondary` + `disabled`** (tooltip a tilalomról).
- Törölhető: továbbra is danger + Swal question megerősítés.
- Érintett: index sorok, view related, breadcrumb, record/linked modal.

---

## 2026-08-03 — Flash: Simple Notify + SweetAlert2 (JeffAdmin5)

### Mi változott
- Admin Flash alapértelmezés: **Simple Notify** toast (több üzenet egyszerre) — JeffAdmin5 `flash/` + `script_flash`.
- Második típus: **SWAL** (`flash/*_swal.php`) — SweetAlert2 modal; egyszerre egy (több sorban).
- Legacy: `flash_/` + jquery-toastmessage assetek (opcionális).
- JS: simple-notify **1.0.6**, sweetalert2 **11.26.25**.
- Helper: `$this->flashSwal('success'|'error'|…, $msg)`.

### Érintett fájlok
- `templates/element/flash/*`, `flash_/*`, `templates/element/admin/script_flash.php`
- `templates/layout/admin.php`, `webroot/js/app.js` (`MyAdmin.flashSwal`)
- `webroot/plugins/simple-notify/`, `sweetalert2/`, `jquery-toastmessage/`
- `src/Controller/Admin/AppController.php`

---

## 2026-08-03 — Dátum/idő picker: Tempus Dominus (JeffAdmin5)

### Mi változott
- Form date / time / datetime: **daterangepicker + inputmask** helyett **Tempus Dominus 6** ([zsfoto/jeffadmin5](https://packagist.org/packages/zsfoto/jeffadmin5) beállításokkal).
- Formátumok: `yyyy.MM.dd.` / `HH:mm:ss` / `yyyy.MM.dd HH:mm:ss` (hu); mentés továbbra is `LocaleDateParser` → SQL.
- Asset: `webroot/plugins/tempus-dominus/`, `webroot/js/popper.js`

### Érintett fájlok
- `templates/Admin/Samples/form.php`, `webroot/js/pages/form.js`, `webroot/css/style.css`
- `src/Utility/LocaleDateParser.php` (trailing `.` a hu dátumhoz)

---

## 2026-08-03 — Törlés UX: kattintható + SweetAlert question

### Mi változott
- Tiltott Delete: Bootstrap `.disabled` helyett **`is-delete-blocked`** (kattintható → Swal hibaüzenet; tooltip működik).
- Engedélyezett törlés: `MyAdmin.confirmDelete` **`icon: 'question'`** mindenhol (index sor, breadcrumb, record/linked modal, related tab).
- View related táblák: látható **trash** gomb (`btn-row-delete`) + rejtett form / blocked állapot.

### Érintett fájlok
- `webroot/js/app.js`, `webroot/js/pages/index.js`, `webroot/css/style.css`
- `templates/element/admin/breadcrumb.php`
- Index + view: Samples / Parents / Cities

---

## 2026-08-03 — Demó tanulságok naplózva (éles építéshez)

### Mi változott
- Új tartós spec: **[minta-tanulsagok.md](minta-tanulsagok.md)** — Samples/Parents/Cities minta → CounterCache, modal 20/ABC, törlésvédelem, JS/SweetAlert, AppController helperek, gyakori hibák.
- Frissítve: `README.md`, `keretrendszer.md`, `uj-projekt.md` (agent szabályok + checklist) — a demó eldobható, a szabályok megmaradnak.

### Miért
A MyAdmin demó DB / CRUD csak minta. Új éles projektnél ebből a `doc/`-ból (különösen `minta-tanulsagok.md`) kell építkezni, nem a chatelőzményből.

---

## 2026-08-03 — CounterCache véglegesítés (működő elvárások)

### Mi változott
- HABTM: `belongsTo` → majd CounterCache; `cascadeCallbacks` + `saveStrategy => replace`
- Trait: `*_count` friss DB olvasás törlés / `canDelete` előtt
- Samples form: üres `cities._ids` → `[]` (mint Cities)
- `bin/cake rebuild_counter_caches` — Parents/Samples/Cities számlálók újraépítése
- Számlálók újraszámolva a demó DB-n

### Érintett fájlok
- Table-ek + `PreventsDeleteWithChildrenTrait`, `SamplesController`, `RebuildCounterCachesCommand`
- Doc: `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-08-03 — CounterCache a `*_count` mezőkhöz (ne élő COUNT)

### Mi változott / szabály
- **Igaz:** a `*_count` mezőket a CakePHP **CounterCache** behavior tartja karban.
- `countRelatedChildren()` többé **nem** futtat `find()->count()` — a CounterCache oszlopot olvassa (`relatedChildrenCountField()`).
- HABTM: CounterCache a **through** Table-en (`CitiesSamples`); `belongsToMany` + `cascadeCallbacks => true`.
- hasMany: CounterCache a **gyerek** Table-en (`Samples` → `Parents.sample_count`).
- Controller: törölve a manuális `city_count` / `sample_count = count(_ids)`.

### Érintett fájlok
- `PreventsDeleteWithChildrenTrait.php`, `SamplesTable`, `ParentsTable`, `CitiesTable`, `CitiesSamplesTable`
- `SamplesController`, `CitiesController` (normalize* eltávolítva)
- Doc: `admin-konvenciok.md` (CounterCache szekció), `crud-utmutato.md`, `keretrendszer.md`, `valtozasok.md`

---

## 2026-08-03 — Modal gyereklista: utolsó 20 modified + ABC

### Mi változott
- Modal JSON (`recordGet` / `parentGet`): kapcsolt nevek = **utoljára módosított max. 20**, megjelenítés **name ASC**.
- `Admin\AppController`: `$modalRelatedLimit`, `containRelatedForModal()`, `relatedNameLinksForModal()`.

### Érintett fájlok
- `AppController.php`, `ParentsController`, `SamplesController`, `CitiesController`
- Doc: `admin-konvenciok.md`, `valtozasok.md`, `admin-oldal.md`, `crud-utmutato.md`

---

## 2026-08-03 — Modal Delete: minden modal + SweetAlert Bootstrap fölött

### Mi változott
- `MyAdmin.swal()`: Bootstrap Modal **FocusTrap** pause/resume + z-index 20000 (record, linked, Select2 hibaüzenetek).
- Record + linked Delete: event delegation; linked `deleteUrl` soha nem a saját `#delete-form-{id}`-re megy.
- CSS: `.swal2-container` z-index, `.btn-label { pointer-events: none }`, modal footer stacking.
- Samples index Parent link: `data-delete-form-prefix="parent"`.

### Érintett fájlok
- `webroot/js/app.js`, `webroot/js/pages/index.js`, `webroot/css/style.css`
- `templates/Admin/Samples/index.php`
- Doc: `valtozasok.md`

---

## 2026-08-03 — Modal Delete: tooltip + kattintható tiltott állapot

### Mi változott
- Modal Delete gomb: **ne** natív `disabled` (az blokkolta a kattintást és a tooltipet).
- `aria-disabled` + `.disabled` + tooltip; tiltott kattintás → SweetAlert hibaüzenet.
- Linked modal: `can_delete` csak a JSON-ból (ne a más entitású index-sor `data-can-delete`-jéből).
- SweetAlert z-index a Bootstrap modal fölött (`didOpen` → 20000).

### Érintett fájlok
- `webroot/js/pages/index.js`, `webroot/js/app.js`, `webroot/css/style.css`
- Doc: `valtozasok.md`, `admin-konvenciok.md`

---

## 2026-08-03 — Parent modal: Sample list linkekkel

### Mi változott
- Samples index Parent link / `parentGet`: Parent modalban **Sample list** (`[{id,name}]` ASC) → kattintás → Sample linked modal (Edit/View/Delete).
- Parents `recordGet` + index ugyanígy; Parents view fő `dl`: Sample list linkek.

### Érintett fájlok
- `SamplesController::parentGet`, `ParentsController::recordGet` / `view`
- `templates/Admin/Samples/index.php`, `Parents/{index,view}.php`
- Doc: `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Cities form: Samples HABTM Select2 multiple

### Mi változott
- Cities add/edit: `samples._ids` multiple Select2 (mint Samples → Cities fordítva).
- `setFormOptions()` + `patchEntity` `associated => ['Samples']` + `sample_count` a kiválasztottakból.
- Nincs Select2 „+” Sample-hez (sok kötelező mező); tags csak ha van create gomb.
- Placeholder: `data-placeholder` / `Select samples...`.

### Érintett fájlok
- `templates/Admin/Cities/form.php`, `Samples/form.php` (placeholder)
- `CitiesController.php`, `webroot/js/pages/form.js`, `layout/admin.php`
- `resources/locales/hu_HU/default.po`
- Doc: `admin-konvenciok.md` (HABTM multiple Select2), `admin-oldal.md`, `crud-utmutato.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Modal: HABTM lista linkekkel (Cities ↔ Samples)

### Mi változott
- Cities `recordGet`: `samples` = `[{id, name}, …]` ASC; index modal „Sample list” + linked modal (Sample details).
- Samples `recordGet`: `cities` ugyanez a formátum (nem implode string) + linkek.
- JS: `relatedLinkFields` config → `.record-modal-link` a modal mezőkben.
- Cities view fő `dl`: Sample list linkek (mint Samples City list).

### Érintett fájlok
- `webroot/js/pages/index.js`
- `CitiesController` / `SamplesController` `recordGet`
- `templates/Admin/Cities/{index,view}.php`, `Samples/index.php`
- `resources/locales/hu_HU/default.po` (`Sample list`)
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `valtozasok.md`

---

## 2026-07-31 — Napi zárás (péntek) → folytatás hétfőn

### Mai nap összefoglalója (rögzítve a specekben is)
1. **Form `#name` autofókusz** — minden Admin form + Select2 „+” modal (`pages/form.js` kötelező).
2. **Séma DEFAULT-ok** — `UsesDatabaseColumnDefaultsTrait`; `pos`/`visible`/`logikai` DB-ből; `cities_samples.pos` DEFAULT 1000; `*_count` még PHP `0`.
3. **Mentés hibák** — try/catch + Flash; `beforeMarshal` ArrayObject → `getArrayCopy()`.
4. **Index lapozás** — `$indexLimit` (10) / `$indexMaxLimit` (100) + `indexPaginateOptions()`.
5. **Utolsó rekord** — session `Admin.lastVisited` + index `.last-visited` (zöld); később bővíthető.

### Hol a tudás
- Célkép: [admin-oldal.md](admin-oldal.md)
- Részletek: [admin-konvenciok.md](admin-konvenciok.md) (`.last-visited`, limit, form fókusz, DB default)
- Új modul: [crud-utmutato.md](crud-utmutato.md)
- Agent checklist: [uj-projekt.md](uj-projekt.md) §5

### Hétfői nyitott / lehetséges folytatás
- `.last-visited` bővítés (pl. scroll a sorhoz, linked-modal finomítás)
- `*_count` oszlopokra DB `DEFAULT 0` → PHP `0` eltávolítása
- Index keresés bekötése (UI megvan)
- Egyéb UI/CRUD finomítások a felhasználó szerint

---

## 2026-07-31 — Index: utolsó rekord (`.last-visited` + session)

### Mi változott
- Session `Admin.lastVisited`: model alias + id (és `_last`); mentés view / edit betöltés / sikeres save / `recordGet`.
- Index: `$lastVisitedId` → sor `class="last-visited"` (meglévő zöld CSS a `style.css`-ben).
- Helper: `rememberLastVisited()`, `setLastVisitedForIndex()` — később bővíthető.

### Érintett fájlok
- `src/Controller/Admin/AppController.php`
- Samples / Parents / CitiesController
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `keretrendszer.md`, `valtozasok.md`

---

## 2026-07-31 — Index: `$indexLimit` / `$indexMaxLimit`

### Mi változott
- Minden Admin CRUD controller tetején: `$indexLimit` (alap sor/oldal, default **10**) és `$indexMaxLimit` (felső korlát, default **100**) — URL `?limit=` hack ellen.
- `AppController::indexPaginateOptions()` adja a Cake Paginator `limit` + `maxLimit` értékeit.

### Érintett fájlok
- `src/Controller/Admin/AppController.php`
- `Samples` / `Parents` / `Cities`Controller
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `keretrendszer.md`, `valtozasok.md`

---

## 2026-07-31 — Oszlop DEFAULT-ok a sémából (`pos`, `visible`, …)

### Mi változott
- Élő DB: `pos` DEFAULT 1000 (cities, parents, samples); `cities_samples.pos` is DEFAULT 1000 (korábban hiányzott); `visible` / `logikai` DEFAULT 1.
- Új trait: `UsesDatabaseColumnDefaultsTrait` (régi `OmitsEmptyPos…` helyett) — üres mező unset + `applySchemaDefaults()` a séma alapján.
- Controller: `newEntityWithSchemaDefaults()`; nincs hardkodolt `visible=true` / `logikai=true`; Select2 create nem küld `visible`-t.
- `*_count` továbbra is `0` a PHP-ban (NOT NULL, nincs DB DEFAULT).

### Érintett fájlok
- `src/Model/Table/Concerns/UsesDatabaseColumnDefaultsTrait.php`
- `CitiesTable`, `ParentsTable`, `SamplesTable`, `CitiesSamplesTable`
- `Admin/AppController`, Cities/Parents/SamplesController
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `keretrendszer.md`, `struktura.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Form: `name` mező autofókusz + doksi szinkron

### Mi változott
- **Minden** Admin `form.php`: `#name` `autofocus` + kötelező `pages/form` JS; `focusPrimaryFormField()` a Select2/inputmask **után** (ne lopja el a fókuszt).
- Cities / Parents is betölti a `form.js`-t (korábban csak CSS).
- Select2 „+” modal name input: `autofocus`.
- Spec: célkép + konvenciók + CRUD / új projekt / keretrendszer.

### Érintett fájlok
- `templates/Admin/{Cities,Parents,Samples}/form.php`
- `webroot/js/pages/form.js`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `keretrendszer.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Fix: `beforeDelete` CakePHP 5.2 deprecation

### Mi változott
- `PreventsDeleteWithChildrenTrait::beforeDelete`: ne `return false` — `$event->stopPropagation()` + `$event->setResult(false)` (CakePHP ≥5.2).

### Érintett fájlok
- `src/Model/Table/Concerns/PreventsDeleteWithChildrenTrait.php`
- Doc: `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Index: `$showIdColumn`

### Mi változott
- Minden Admin CRUD `index.php` elején: `$showIdColumn = true|false` — az `id` (`#`) oszlop ki/be.
- `$indexColspan` számítás figyelembe veszi.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `uj-projekt.md`, `README.md`, `valtozasok.md`

---

## 2026-07-31 — Pénznem megjelenítés: `Ft` (nem HUF)

### Mi változott
- `LocaleNumberParser::currencySymbol()` — Admin `hu_HU` → **`Ft`** (magyar szokás; nem ISO `HUF`).
- Index / view / `recordGet`: minden `HUF` hardkód cseréje a helperre.
- Későbbi EUR: a helper `match` ágát bővíteni — ne template hardkód.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php` (`currencySymbol`)
- `templates/Admin/Samples/index.php`, `Samples/view.php`, `Parents/view.php`, `Cities/view.php`
- `SamplesController::recordGet`
- Doc: **`admin-konvenciok.md`**, **`admin-oldal.md`**, **`i18n.md`**, **`middleware.md`**, `README.md`, `struktura.md`, `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

### Példa
```php
<?= h(LocaleNumberParser::format($row->netto, decimals: 2)) ?>
<?= h(LocaleNumberParser::currencySymbol()) ?>
// → 12 345,67 Ft
```

---

## 2026-07-31 — Fix: Table-en nincs `getTableLocator`

### Mi változott
- `CitiesTable` / `SamplesTable` `countRelatedChildren`: join számlálás `getAssociation(…)->junction()`-nel (CakePHP 5 Table-en nincs `getTableLocator()`).

### Érintett fájlok
- `src/Model/Table/CitiesTable.php`, `SamplesTable.php`, `doc/valtozasok.md`

---

## 2026-07-31 — Form Parent lista: visible + pos, name sorrend

### Mi változott
- Sample form Parent Select2/list: csak `visible = true`; rendezés `pos` ASC, `name` ASC.
- Edit: a jelenlegi `parent_id` akkor is megjelenik, ha a szülő nem visible.
- **Általános konvenció** belongsTo listákra dokumentálva.

### Érintett fájlok
- `src/Controller/Admin/SamplesController.php` (`setFormOptions`)
- Doc: **`admin-oldal.md`** §6, **`admin-konvenciok.md`** (Form → Kapcsolt lista), `crud-utmutato.md`, `uj-projekt.md` §5–6, `README.md`, `valtozasok.md`

### Példa (rövid)
```php
->where(['OR' => [['Parents.visible' => true], ['Parents.id' => $sample->parent_id]]])
->orderBy(['Parents.pos' => 'ASC', 'Parents.name' => 'ASC'])
```

---

## 2026-07-31 — `beforeMarshal` ArrayObject + mentés hibakezelés

### Mi változott
- Bugfix: `array_key_exists('pos', $data)` TypeError PHP 8+-on, mert Cake `ArrayObject`-et ad (`OmitsEmptyPosForDbDefaultTrait`, `CitiesSamplesTable`). Megoldás: `$data->getArrayCopy()`.
- Cities / Parents / Samples `add`/`edit`: váratlan kivétel → Flash („The record could not be saved…”), nem nyers PHP hiba.
- Select2 inline create: validációs első hibaüzenet JSON-ban; Throwable → udvarias üzenet.

### Érintett fájlok
- `src/Model/Table/Concerns/OmitsEmptyPosForDbDefaultTrait.php`
- `src/Model/Table/CitiesSamplesTable.php`
- `src/Controller/Admin/{Cities,Parents,Samples}Controller.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `keretrendszer.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — `pos`: csak DB default, ne PHP 1000

### Mi változott
- Eltávolítva minden programozott `pos = 1000` (controller add, Select2 create, `normalizeCounters`, CitiesSamples beforeSave/Marshal force).
- `OmitsEmptyPosForDbDefaultTrait`: üres `pos` → unset → INSERT-nél a séma DEFAULT érvényesül.
- Validáció: `pos` `allowEmptyString`.

### Érintett fájlok
- `src/Model/Table/Concerns/OmitsEmptyPosForDbDefaultTrait.php`
- `ParentsTable`, `SamplesTable`, `CitiesTable`, `CitiesSamplesTable`
- `Samples`/`Parents`/`Cities`Controller
- Doc: `struktura.md`, `crud-utmutato.md`, `keretrendszer.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — `*_count` oszlop: 0/null ne jelenjen meg

### Mi változott
- `LocaleNumberParser::formatCount()` — null vagy 0 → üres string.
- Index / view / `recordGet` / modal: count mezők nem írnak ki `0`-t.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php`
- `templates/Admin/{Samples,Parents,Cities}/index.php`, `view.php`
- `*Controller` recordGet / parentGet
- `webroot/js/pages/index.js`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `valtozasok.md`

---

## 2026-07-31 — Törlés: gyerekvédelem + működő delete form

### Mi változott
- Model: `PreventsDeleteWithChildrenTrait` — gyerek/join van → `beforeDelete` false + `_delete` hibaüzenet; gyerek nélkül törölhető (HABTM `dependent => true` a joinra).
- **Bugfix:** `Form->postLink` `id` az `<a>`-n volt → JS nem submitolt. Most `Form->create` `#delete-form-{id}`.
- UI: `*_count > 0` → Delete gomb disabled + tooltip; modal `can_delete`; breadcrumb Swal + `#delete-form-current`.
- Controller: `deleteEntityOrFail`, `setCanDeleteFlag`; Flash a model üzenettel.

### Érintett fájlok
- `src/Model/Table/Concerns/PreventsDeleteWithChildrenTrait.php`
- `ParentsTable` / `SamplesTable` / `CitiesTable`
- `Admin/AppController`, `*Controller` delete/view/edit/recordGet
- Index/view templatek, `breadcrumb.php`, `app.js`, `pages/index.js`, `hu_HU/default.po`
- Doc: `admin-konvenciok.md`, `admin-oldal.md`, `crud-utmutato.md`, `valtozasok.md`

---

## 2026-07-31 — View: kapcsolt rekordok modal link + dupla klikk

### Mi változott
- View: belongsTo / HABTM / kapcsolt tab **name** → félkövér `.record-modal-link` → `#modalLinkedRecordView` (AJAX `recordGet`).
- Modal gombok: Close, Edit, View details, Delete; Delete → SweetAlert `confirmDelete`.
- View elején `$rowDoubleClickAction` (`modal`/`edit`/`none`) a `.related-records-table` soraira.
- `pages/index.js`: generikus linked context (`data-*` URL-ek, `entityFieldLabels`); delete form prefix / CSRF POST.
- Delete actionök: redirect `referer`-re (view-ról törlés után vissza).
- Samples view: Parent link + City list + Cities tab name linkek.

### Érintett fájlok
- `webroot/js/pages/index.js`, `webroot/css/style.css`
- `templates/Admin/{Samples,Parents,Cities}/view.php`, `Samples/index.php` (`parentDeleteUrl`)
- `src/Controller/Admin/{Samples,Parents,Cities}Controller.php` (delete referer)
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `README.md`, `valtozasok.md`

---

## 2026-07-31 — Currency oszlop szélesebb (`12rem`)

### Mi változott
- `.table th/td.currency`: `8.5rem` → `12rem` (~4–5 számjegy plusz + „HUF”).

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-oldal.md`, `doc/admin-konvenciok.md`, `doc/README.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index: `row mt-3` a tartalom tetején

### Mi változott
- Minden Admin CRUD `index.php` külső sora: `class="row mt-3"` (térköz a breadcrumb / Flash alatt).

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-oldal.md`, `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Dokumentáció: Admin oldal teljes kép

### Mi változott
- Új **`doc/admin-oldal.md`**: egyben leírja, hogyan nézzen ki és működjön az Admin (layout, index/form/view, oszlopszélességek, interakciók, dialógus, i18n, checklist).
- `README.md` és a kapcsolódó spec fájlok erre mutatnak első olvasmánynak; rögzített döntések kiegészítve a teljes oszlopszélesség-táblával.

### Érintett fájlok
- `doc/admin-oldal.md` (új), `doc/README.md`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/crud-utmutato.md`, `doc/keretrendszer.md`, `doc/struktura.md`, `doc/valtozasok.md`

### Szabály
Lényeges UI/viselkedés változás után frissítsd az `admin-oldal.md`-t is (ne csak a részlet-doksit), amíg a célkép konzisztens marad.

---

## 2026-07-31 — Index oszlopszélességek: minta + szám/pénz/logikai fix

### Mi változott
- MyPluginTemplate `style.css` szerinti fix: `date` 8.5 / `datetime` 10.5 / `time` 5 / `times` 9 / `count` 5.5 / `visible|valid|boolean` 7.5 rem.
- MyAdmin kiegészítés (mintában nem volt width): `id` 4.75, `pos` 5.5, általános `number` 6.5, `currency` 8.5 rem.
- `string` oszlopok továbbra is rugalmasak.

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index: `$numberDecimals` tizedesjegyek

### Mi változott
- Minden `index.php` elején: `$numberDecimals = ['integer' => 0, 'decimal' => 2]`.
- Lista számkiírás: `LocaleNumberParser::format(..., decimals: $numberDecimals['integer'|'decimal'])`.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- Doc: `admin-konvenciok.md`, `uj-projekt.md`, `crud-utmutato.md`, `valtozasok.md`

---

## 2026-07-31 — Cities/index: id + pos fix szélesség; agent szabályok összefoglalva

### Mi változott
- CSS: `.table th/td.pos` = `5rem` (max 5 jegy + locale ezres, pl. `12 345`); `id` megmarad `4.75rem`; sort link `id`/`pos` oszlopban `width: 100%` (ne nyíljon szét).
- `uj-projekt.md` §5: tartós agent checklist — új projektnél ne kelljen újra elmondani a szabályokat.

### Érintett fájlok
- `webroot/css/style.css`, `doc/admin-konvenciok.md`, `doc/uj-projekt.md`, `doc/valtozasok.md`

---

## 2026-07-31 — Index számkiírás locale + fix oszlopszélességek

### Mi változott
- Index / view / `recordGet`: számok `LocaleNumberParser::format()` (Admin `hu_HU` → `1 234,56`); címkék továbbra is `__('English')`.
- Fix CSS: `th/td.id` = `4.75rem` (~7–8 jegy); date/datetime/time a MyPluginTemplate értékei (`8.5` / `10.5` / `5` rem) — dokumentálva.

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`, `view.php`
- `SamplesController::recordGet`, `webroot/css/style.css`
- Doc: `admin-konvenciok.md`, `i18n.md`, `valtozasok.md`

---

## 2026-07-31 — Modal: HABTM lista ABC (ASC) sorrend

### Mi változott
A gyorsnézet modalban (és a view kapcsolt tabon) a **több-több / hasMany** megjelenített lista **név szerint ABC (ASC)** sorrendben jelenik meg.

Példa (Samples → Cities):

```php
$sample = $this->Samples->get($id, contain: [
    'Parents',
    'Cities' => function ($q) {
        return $q->orderBy(['Cities.name' => 'ASC']);
    },
]);
// recordGet: implode(', ', $city->name …) → ABC sorrendű felsorolás
```

**Tartós szabály:** minden Admin `recordGet` / view, ahol kapcsolt rekordnevek listája látszik → `contain` + `orderBy(['Alias.name' => 'ASC'])` (vagy a megjelenített címkemező).

### Érintett fájlok
- `src/Controller/Admin/SamplesController.php` (`recordGet`, `view`)

### Doc frissítve
- [x] `crud-utmutato.md` — recordGet szakasz
- [x] `admin-konvenciok.md` — index kötelező elem + modal kapcsolt listák
- [x] `uj-projekt.md` / `README.md` — rögzített döntés
- [x] `valtozasok.md`

---

## 2026-07-31 — Form számmezők locale (hu) + parser javítás

### Mi változott
- Inputmask locale-aware: `MyAdmin.config.numberFormat` (`LocaleNumberParser::jsConfig()`); hu: tizedes `,`, ezres szóköz.
- Mező osztályok: `.js-input-decimal` / `.js-input-integer`; value: `LocaleNumberParser::format()`.
- Parser: vegyes `1,234.56` / `1.234,56` helyes; mentéskor nem vesznek el számjegyek.
- Gyökérok: angol maszk (`1,234.56`) + hu middleware → Cake float cast = `1`.

### Érintett fájlok
- `src/Utility/LocaleNumberParser.php`, `webroot/js/pages/form.js`, `templates/Admin/Samples/form.php`, `tmp/test_locale_parsers.php`

### Doc frissítve
- [x] `middleware.md`, `admin-konvenciok.md` (Számmezők), `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Admin: window.alert → SweetAlert (`MyAdmin.alert`)

### Mi változott (tartós szabály új projektnél is)

Az Adminban **tilos** a natív `window.alert` / `confirm` / `prompt`. Minden felhasználói üzenet **SweetAlert2**:

| API | Szerep |
|-----|--------|
| `MyAdmin.confirmDelete({ onConfirm })` | Törlés megerősítés |
| `MyAdmin.alert({ icon, title, text })` | Általános dialógus |
| `MyAdmin.alertError(text)` | Hiba (pl. Select2 mentés) |

- Implementáció: `webroot/js/app.js`
- Select2 hibák: `webroot/js/pages/form.js` → `App.alertError(...)`
- Szövegek: layout `MyAdmin.messages` (`errorTitle`, `okButton`, `failedToSave`, `saveNewValueFailed`, …) + `hu_HU/default.po`

Greenfield / következő projekt: kövesd [admin-konvenciok.md](admin-konvenciok.md) „SweetAlert” + [uj-projekt.md](uj-projekt.md) 2.6 / agent szabály 6.

### Érintett fájlok
- `webroot/js/app.js`, `webroot/js/pages/form.js`, `templates/layout/admin.php`
- Doc: `admin-konvenciok.md`, `uj-projekt.md`, `i18n.md`, `keretrendszer.md`, `README.md`, `valtozasok.md`

---
## 2026-07-31 — Fix: select2CreateCity Table vs Association

### Mi változott
- `select2Create` / `select2CreateCity`: `$this->fetchTable('Parents'|'Cities')` — nem `$this->Samples->Cities` (BelongsToMany Association ≠ Table).

### Érintett fájlok
- `SamplesController.php`, `doc/admin-konvenciok.md`

---

## 2026-07-31 — Modal gombok: Edit előbb, „View details”

### Mi változott
- Rekord / linked modal lábléc: **Edit** a **View details** előtt.
- Címke: `__('View details')` → hu: „Részletek megtekintése” (breadcrumb, tooltip, view actions is).

### Érintett fájlok
- `templates/element/admin/modal_record_view.php`, `modal_linked_record_view.php`, `breadcrumb.php`
- index/view templatek tooltip/title; `resources/locales/hu_HU/default.po`

---

## 2026-07-31 — Index opcionális oszlopok ($show*Column)

### Mi változott
Az `index.php` elején, a `$rowDoubleClickAction` **után** négy kapcsoló vezérli, mely opcionális oszlopok jelenjenek meg a listában:

```php
$showCountColumn = true;    // *_count (gyerek rekordok száma)
$showVisibleColumn = true;  // visible
$showCreatedColumn = true;  // created — önállóan
$showModifiedColumn = true; // modified — önállóan

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
// $indexColspan = kötelező oszlopok + bekapcsolt opcionálisak (üres lista sor)
```

| Változó | Hatás |
|---------|--------|
| `$showCountColumn` | `city_count` / `sample_count` stb. oszlop |
| `$showVisibleColumn` | `visible` oszlop |
| `$showCreatedColumn` | `created` a timestamp oszlopban |
| `$showModifiedColumn` | `modified` a timestamp oszlopban |

- Created és Modified **egyenként** ki/be kapcsolható.
- Ha mindkettő `true`: egy közös `th`/`td` (`datetime created modified`), két sort link, két sor a cellában.
- Ha csak az egyik: egy oszlop, csak az a mező.
- Ha egyik sem: nincs timestamp oszlop.
- Csak az **index táblára** vonatkozik (view/modal ettől független).
- Alapértelmezés minden demó modulban: mind `true`.

### Érintett fájlok
- `templates/Admin/Samples/index.php`
- `templates/Admin/Parents/index.php`
- `templates/Admin/Cities/index.php`

### Doc frissítve
- [x] `admin-konvenciok.md` — „Opcionális oszlopok”
- [x] `crud-utmutato.md` — index checklist
- [x] `uj-projekt.md` — index konfig
- [x] `valtozasok.md`

---

## 2026-07-31 — Index sor dupla kattintás konfigurálható

### Mi változott
Az `index.php` **elején**:

```php
$rowDoubleClickAction = 'modal'; // 'modal' | 'edit' | 'none'
```

| Érték | Hatás |
|-------|--------|
| `modal` | Gyors nézet `#modalRecordView` + `recordGet` (alapértelmezés) |
| `edit` | Navigálás az edit formra (`editUrl/{id}`) |
| `none` | Nincs művelet |

- Átmegy a `MyAdmin.config.rowDoubleClickAction`-be; kezelés: `webroot/js/pages/index.js`.
- A card fejléc súgó (`$rowDoubleClickHint`) az értékhez igazodik (üres, ha `none`).

### Érintett fájlok
- `templates/Admin/{Samples,Parents,Cities}/index.php`
- `webroot/js/pages/index.js`
- `resources/locales/hu_HU/default.po` (`Double-click a row to edit the record.`)

### Doc frissítve
- [x] `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `valtozasok.md`

---

## 2026-07-31 — Select2 „+” multiple selectnél is

### Mi változott
- Cities (HABTM / multiple Select2) mellett is „+” gomb + modal; mentés után az új város **azonnal kiválasztott** a listában (meglévő kijelölések megmaradnak).
- Generikus minta: `data-select2-target` + `data-create-url` + `.modal-select2-add` / `.select2-add-form`; jövőbeli többmezős felvitel támogatott, a Select2 option szövege a válasz `text` (ill. `data-select2-text` mező).
- Endpoint: `select2CreateCity`; parent `select2Create` közös helperrel.

### Érintett fájlok
- `templates/Admin/Samples/form.php`, `webroot/js/pages/form.js`, `SamplesController.php`
- Doc: `admin-konvenciok.md`, `crud-utmutato.md`, `uj-projekt.md`, `struktura.md`, `valtozasok.md`

---

## 2026-07-31 — Hordozható dokumentáció (új projektből is felépíthető)

### Mi változott
- Új **[uj-projekt.md](uj-projekt.md)**: greenfield Admin keretrendszer lépésről lépésre (asset, routing, middleware, layout, elementek, i18n, első CRUD, agent szabályok).
- README / keretrendszer / struktura / crud-utmutato átírva: **nem** függnek a MyAdmin kódtól vagy fix `MyPluginTemplate` úttól; a `doc/` önálló specifikáció.
- View + related tabs, index/form/asset, i18n, middleware szabályok egy helyen követhetők új projektben is.

### Érintett fájlok
- `doc/uj-projekt.md` (új)
- `doc/README.md`, `keretrendszer.md`, `struktura.md`, `crud-utmutato.md`, `admin-konvenciok.md`, `valtozasok.md`

---

## 2026-07-31 — Admin alap + keretrendszer funkciók (eredeti MyAdmin építés)

### Cél
CakePHP 5 Admin prefix Pike Admin kinézettel. Demó DB táblák csak keretrendszer-teszthez.

### Létrehozott / fő területek
- Layout + `templates/element/admin/*`
- Middleware: Locale + NormalizeLocalizedDate/Number
- i18n: `__('English')` + `hu_HU/default.po`; adminban nincs nyelvválasztó
- Index minta: URL sort; opcionális oszlopok (`$showCountColumn`, `$showVisibleColumn`, `$showCreatedColumn`, `$showModifiedColumn`); konfigurálható dupla klikk (`$rowDoubleClickAction`); SweetAlert delete
- View minta: bake `dl` + `view_related_tabs` (üres tab is)
- Form: Select2 „+” single **és** multiple (inline create); számmezők locale (`numberFormat`)
- Modal: kapcsolt HABTM/hasMany lista **ABC ASC**; gombok Edit → View details; Swal hibák
- JS: `MyAdmin` + `pages/index.js` / `pages/form.js`

### Demó modulok (eldobhatók)
- Samples / Parents / Cities CRUD — tanulság: belongsTo, hasMany, HABTM, `ParentRecord`

### Ismert következő lépések (opcionális)
- Lista Search bekötése
- `index()` default sort / session query
- Auth / login
- Member többnyelvű .po bővítés

---

<!-- Új bejegyzés sablon:

## YYYY-MM-DD — Rövid cím

### Mi változott
- …

### Érintett fájlok
- …

### Doc frissítve
- [ ] uj-projekt.md / admin-konvenciok.md / crud-utmutato.md / …

-->
