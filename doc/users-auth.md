# CakeDC Users — Auth UI + szerepkör panelek

Hordozható specifikáció: **új CakePHP 5 projektben** a CakeDC Users plugin telepítése után **ebből** építsd fel (vagy igazítsd) az auth + role paneleket.  
Admin CRUD: [admin-oldal.md](admin-oldal.md). Greenfield: [uj-projekt.md](uj-projekt.md) §2.2 + §2.9.  
Cursor rule: `.cursor/rules/users-auth.mdc`.

---

## 0. Baseline állapot (2026-08) — új projektek kiindulópontja

Ez a dokumentum a **MyAdmin-ban összerakott** auth / regisztráció / szerepkör-panel állapotot rögzíti.  
**Innen indulnak az új projektek**, ha a `doc/` (+ `.cursor/rules/`) átkerül.

### Mi stabil (másold / kövesd, amíg a projekt mást nem kér)

| Terület | Rögzített minta |
|---------|-----------------|
| Login layout | ValiAdmin CDN + `login-box local-login` + `.local-login-form` flow; **nincs** box-logo |
| Flash | Simple Notify toast (`usesFlashToast`) — Adminmal azonos |
| App Users | `Users.controller` / `Users.table` = App; templatek `templates/Users/` |
| Permissions | **ne** `'plugin' => false` App Usersre; `SanitizeAuthRedirectMiddleware` |
| Ország + locale | Login: **nyelv** Select2 + BrowserLocale; Register: **ország** + `users.country_id` — [login-language.md](login-language.md) |
| Login azonosító | **email** (nem username) — Form authenticator `fields.username` → `email` |
| Belépés kapu | CakeDC `active` **és** app `enabled` (`findActive`); session közben is |
| Eseménynapló | `event_logs` — saját lista minden usernek; officer ország-kereső — [event-logs.md](event-logs.md) |
| Profil | `admin` layout + view stílusú `dl`; header: Belépve / Profile / My event log / Change password / Logout |
| Tagság onboarding | `new` → kötelező `/complete-profile` → clubpresident Approve → `member` — [membership.md](membership.md) |
| URL nyelv | **nincs** `/{lang}/…` — locale session / user ország |
| Panel chrome | ugyanaz az `admin` layout (header / sidebar / breadcrumb / content) minden szerepkör-prefixen |

### Mi képlékeny (projektenként változhat — részletes „mit akarunk” később)

Új projekteknél **várhatóan** eltérhet (de maradhat is ez a minta):

- **Szerepkörök listája** (`AppRoles`) és melyik role melyik URL-prefixet kapja
- **Login / regisztrációs form** mezői, kinézete, kötelező ország
- **Auth megoldás** (CakeDC Form, SSO / Keycloak, magic link, stb.)
- Panel tartalom (`/new`, `/member`, … Dashboard placeholder → domain UI)

**Agent:** amíg a projekt nem ad új auth/role speceket, ezt a baseline-t használd. Ha a megrendelő mást kér, frissítsd ezt a fájlt + `valtozasok.md` + a rule-t.

---

## 1. Célkép (jelenlegi MyAdmin)

| Oldal | URL | Layout | Tartalom |
|-------|-----|--------|----------|
| Login | `/login` | `login` | **nyelv** (Select2) → **email** + jelszó + Remember me |
| Regisztráció | `/register` | `login` | **ország** (első) → név → email → jelszó → confirm → role=`new` |
| Elfelejtett jelszó | `/request-reset-password` | `login` | reference |
| Profil | `/profile` | **`admin`** | view-stílusú adatlap; vissza a role panel Dashboardjára |
| Profil kiegészítés | `/complete-profile` | **`admin`** | kötelező `new` role-nak (first/last/phone/country/club) — [membership.md](membership.md) |
| Jelszócsere | `/change-password` | `login` | current + new + confirm |

Kinézet: **ValiAdmin** + `users_auth.css`. Minta: `login-box local-login` + `.local-login-form` (ne `.login-form` — ValiAdmin elrejti). Flash: Simple Notify.

---

## 2. Szerepkör → panel prefix (RoleHome)

Nincs URL nyelv-prefix. Panelek (Admin chrome):

| `Users.role` | Prefix (Cake) | URL | Jogosultság |
|--------------|---------------|-----|-------------|
| `new` | `New` | `/new` | **csak** ez (regisztráció default) |
| `member`, `editor` | `Member` | `/member` | saját panel |
| `clubpresident` | `Clubpresident` | `/clubpresident` | saját panel |
| `president`, `vicepresident` | `President` | `/president` | saját panel |
| `admin`, `superuser` | `Admin` | `/admin` | Admin (+ wildcard) |

Kód:

- `App\Auth\AppRoles` — szerepkör konstansok / címkék
- `App\Auth\RoleHome` — `prefix()` / `path()` / `url()` / `brand()` / `sidebarElement()`
- `App\Controller\PanelAppController` — locale + `admin` layout + `panel*` view változók
- `App\Controller\{New,Member,Clubpresident,President}\` — Dashboard placeholder
- `templates/element/{new,member,clubpresident,president}/sidebar.php`
- Login után: `Users.Authentication.afterLogin` → **`$event->setResult(RoleHome::url($role))`** (ne `return` — CakePHP 5.2 deprecation)

`/` → bejelentkezve: role home; vendég: `/login` (`LocalesController::home`).

### Globális header kereső

Csak `superuser` / `admin` / `president` / `vicepresident` (`AppRoles::globalSearchRoles()` + `header_search.php` + permissions `Admin\Search`).  
`clubpresident` és lejjebb: **nincs** keresőmező.

Setups modul: továbbra is `SetupAccess` / `setupsModuleRoles()` (Admin panelen).

---

## 3. Plugin + konfig

### 3.1 Bootstrap

```php
Configure::write('Users.config', ['users']);
$this->addPlugin('CakeDC/Users');
```

### 3.2 `config/users.php`

| Kulcs | MyAdmin érték |
|-------|----------------|
| `Users.table` / `controller` | `Users` (App) |
| `Users.Registration.defaultRole` | `'new'` |
| `Auth.Authenticators.Form…fields.username` | `'email'` |
| `loginRedirect` | fallback `/login` (tényleges: afterLogin → RoleHome) |
| `logoutRedirect` | `/login` |
| `unauthorizedHandler.url` | App Users `login` (`plugin` null) |

### 3.3 `config/permissions.php` — kritikus

App Users: **ne** `'plugin' => false` (`null !== false` → redirect loop / URI Too Long).  
Szabályok: bypassAuth auth actionök; `role => '*'` profile/logout; Search (elnök+); role→saját prefix; Admin csak admin/superuser.

### 3.4 `config/routes.php`

```php
$panelPrefixes = ['Admin', 'New', 'Member', 'Clubpresident', 'President'];
foreach ($panelPrefixes as $prefix) {
    $routes->prefix($prefix, function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });
}
// NINCS $routes->scope('/{lang}', …)
```

---

## 4. App réteg (fájlinventory)

| Fájl | Szerep |
|------|--------|
| `src/Controller/UsersController.php` | login layout; login nyelv / register ország; profile |
| `src/Utility/AdminLanguage.php` | nyelvlista + i18n seed |
| `src/Model/Table/UsersTable.php` | `country_id`; register validáció; **`findActive` = active+enabled**; OneTimeLogin wrappers |
| `src/Controller/Component/LoginComponent.php` | disabled (`enabled=0`) login Flash |
| `src/Auth/UsersMiddlewareQueueLoader.php` + `RequireUserEnabledMiddleware` | session kick ha `enabled`/`active` off |
| `webroot/js/pages/users_auth_locale.js` | login nyelv Select2 |
| `webroot/js/pages/users_auth_country.js` | register ország Select2 |
| `src/Auth/AppRoles.php` | role lista + search/setups role listák |
| `src/Auth/RoleHome.php` | role → panel URL / sidebar |
| `src/Auth/CurrentUser.php` | identity role; `isSuperuser()` = `role===superuser` **vagy** `is_superuser` flag (1/true) |
| `src/Controller/PanelAppController.php` | közös panel chrome |
| `src/Controller/{New,Member,Clubpresident,President}/` | panelek |
| `src/Utility/BrowserLocale.php` | resolve / persist / `forLoggedIn` / `localeFromUser` |
| `src/Utility/AdminCountry.php` | working country cookie ≥ 1 év |
| `src/Middleware/LocaleMiddleware.php` | session/cookie locale (**nincs** URL lang) |
| `src/Middleware/SanitizeAuthRedirectMiddleware.php` | nested login redirect |
| `src/Application.php` | afterLogin → `setResult(RoleHome::…)` |
| `templates/layout/login.php` + `admin.php` | auth / panel |
| `templates/Users/*` | App path |
| `templates/element/admin/header_profile.php` | Belépve + menü (Help/Messages mintájú tipográfia) |
| `templates/element/admin/header_search.php` | role-gated |
| `webroot/css/pages/users_auth.css` | login box flow |
| `webroot/js/pages/users_auth_country.js` | ország Select2 |
| Migráció | `users.country_id` → `countries.id` |

---

## 5. Login / register részletek

- Login: **nyelv** Select2 a POST-on kívül (`?locale=`); feliratok: **aktuális nyelven + (endoním)** (pl. Angol (English)); csak látható országok locale-jai. Részlet: [login-language.md](login-language.md).
- Login: email mező (`type=email`).
- Register: **ország** Select2 — címke = `endonim_name`, csak `Countries.visible = true`; `normalizeRegistrationData` (username←email); `country_id` kötelező; **ne** duplikáld a `nonNegativeInteger` szabályt a `validationRegister`-ben.
- Locale login után: **login session/cookie nyelv** (`?locale=` / POST `locale`), fallback: user `country_id` → `Countries.locale` → `BrowserLocale::persist` (`AppUiLocale` ≥ 1 év) + panel `forLoggedIn`.

### 5.1 `active` vs `enabled`

| Mező | Ki állítja tipikusan | Jelentés |
|------|----------------------|----------|
| `active` | CakeDC (email aktiváció / regisztráció) | Fiók aktiválva-e |
| `enabled` | Admin / president | Belépés engedélyezve (kizárás) |

Belépéshez **mindkettő** kell (`UsersTable::findActive`).  
Session közben `RequireUserEnabledMiddleware` kidobja a usert, ha `enabled` (vagy `active`) kikapcsolódik.

---

## 6. Header profil menü

`header_profile.php`:

1. Kék cím: **Logged in: {0}** / hu **Belépve: {0}** (`h5 > small`, fehér — mint Help/Messages)
2. Profile / Change password / Log out — `.dropdown-item.notify-item`, **0.9rem** (mint a többi header legördülő)
3. `.profile-dropdown`: min ~280px, max ~420px

---

## 7. Middleware

```
ErrorHandler → HostHeader → SanitizeAuthRedirect → Asset → Routing
→ Locale → BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
→ (CakeDC) Authentication → Authorization → RequireUserEnabled
```

`LocaleMiddleware`: mindig `BrowserLocale::resolve` (+ `AppUiLocale` cookie megújítás ≥ 1 év); panel AppController finomít `forLoggedIn`-nel.

---

## 8. i18n

Auth stringek: `__()` + `resources/locales/{locale}/default.po` (`languages.visible = true`).  
Újraépítés: `php tmp/build_auth_locale_pos.php`.  
Login/register box: `.login-box.local-login` szélesebb (`users_auth.css`, ~32rem) a zászlós Select2 miatt.
Részletek: [i18n.md](i18n.md).

---

## 9. Új projekt checklist

- [ ] CakeDC Users + `config/users.php` (App table/controller; email login ha kell)
- [ ] `permissions.php` — nincs `plugin => false`; role→prefix; Search role-gate
- [ ] Panel prefixek a `routes.php`-ban (**nincs** `/{lang}`)
- [ ] `RoleHome` + `PanelAppController` + panel Dashboardok + sidebarek
- [ ] afterLogin: **`setResult()`**, ne `return`
- [ ] UsersController + UsersTable + country_id + **enabled** belépéskapu
- [ ] login layout + `templates/Users/*` + users_auth CSS/JS
- [ ] Header profile + (opcionális) role-gated search
- [ ] `.po` msgid-ek
- [ ] Smoke: register → login → `/new`; admin user → `/admin`; alsó role: nincs search

**Projektspecifikus (később írd le, ha eltér):** role lista; login/reg form; auth provider.

---

## 10. Gyakori hibák

| Tünet | Ok | Teendő |
|-------|-----|--------|
| URI Too Long `/login?redirect=…` | `plugin => false` / hiányzó bypassAuth | permissions + Sanitize |
| Redirect loop / „nem érhető el” | role nincs a cél prefixen | RoleHome + permissions |
| afterLogin deprecation + headers already sent | `return` az eventből | `$event->setResult(...)` |
| MissingTemplate Users/login | plugin path | `templates/Users/` |
| Üres login box | `.login-form` absolute | `local-login` + `.local-login-form` + flow CSS |
| Register crash: rule already exists | dupla `nonNegativeInteger('country_id')` | csak defaultban legyen |
| OneTimeLogin deprecation | Table proxy | `getBehavior()` wrapper |

---

## 11. Kapcsolódó

```
config/users.php, permissions.php, routes.php, roles.php
src/Auth/{AppRoles,RoleHome,CurrentUser,SetupAccess}.php
src/Controller/PanelAppController.php
src/Controller/{Users,Locales,New,Member,Clubpresident,President,Admin}/
src/Application.php
src/Utility/{BrowserLocale,AdminCountry}.php
src/Middleware/{Locale,SanitizeAuthRedirect}Middleware.php
templates/layout/{login,admin}.php
templates/Users/*, templates/{New,Member,Clubpresident,President}/
templates/element/admin/header_{profile,search}.php
templates/element/{new,member,clubpresident,president}/sidebar.php
webroot/css/pages/users_auth.css, webroot/css/style.css (.profile-dropdown)
webroot/js/pages/users_auth_country.js
```
