# CakeDC Users — Auth UI (login / regisztráció / profil / jelszó)

Hordozható specifikáció: **új CakePHP 5 projektben** a CakeDC Users plugin telepítése után **ebből** építsd fel az auth képernyőket.  
Admin CRUD specek: [admin-oldal.md](admin-oldal.md). Greenfield sorrend: [uj-projekt.md](uj-projekt.md) §2.9.  
Cursor rule: `.cursor/rules/users-auth.mdc`.

---

## 1. Célkép

| Oldal | URL (példa) | Layout | Tartalom |
|-------|-------------|--------|----------|
| Login | `/login` | `login` | **ország (Select2)** → **email** + jelszó + Remember me |
| Regisztráció | `/register` | `login` | **ország (első mező, Select2)** → név → email → jelszó → confirm |
| Elfelejtett jelszó | `/request-reset-password` | `login` | reference (email/username) |
| Profil | `/profile` | **`admin`** | Admin `view.php` stílusú olvasó adatlap (`dl.record-view-fields`) |
| Jelszócsere | `/change-password` | `login` | current (ha kell) + new + confirm |

Kinézet: **ValiAdmin** login (CDN: `saghysat.hu/plugins/valiadmin/…`) + helyi `users_auth.css`.  
**Minta struktúra** (`CakeDC-Login-layout-with-KeyCloak`): `.login-box.local-login` + tartalom `.local-login-form` (helyi face; nincs Keycloak flip).  
**Nincs** kis logo/ikon a `login-box`ban — csak a felső alkalmazásnév (`App.Name`).  
A keret magassága a tartalomhoz igazodik: a ValiAdmin abszolút flip-panelek helyett a `.local-login-form` **flow** layout (`position: relative`) — különben `min-height: 0` + absolute → üres/összecsukló box.

Flash: **Simple Notify** toast — ugyanaz, mint az Adminban (`admin/script_flash` + `flash/*`).

---

## 2. Plugin + konfig

### 2.1 Composer / bootstrap

```bash
composer require cakedc/users
```

`Application::bootstrap()` — **mielőtt** a plugin betöltődik:

```php
Configure::write('Users.config', ['users']);
$this->addPlugin('CakeDC/Users');
```

(`config/bootstrap.php`-ban is lehet `Users.config` — a lényeg: az App `config/users.php` felülírja a plugin defaultot.)

### 2.2 `config/users.php` (App override)

Kötelező irányok:

| Kulcs | Érték / megjegyzés |
|-------|---------------------|
| `Users.table` | `'Users'` → `App\Model\Table\UsersTable` |
| `Users.controller` | `'Users'` → `App\Controller\UsersController` |
| `Users.Registration.active` | `true` (ha kell regisztráció) |
| `Users.Registration.defaultRole` | pl. `'new'` ([roles](setups.md) / `AppRoles`) |
| `Auth.AuthenticationComponent.loginRedirect` | fallback; tényleges: `Users.Authentication.afterLogin` → `RoleHome` |
| `Auth.AuthenticationComponent.logoutRedirect` | `'/login'` |
| `Auth.Authenticators.Form…fields.username` | `'email'` — login azonosító = email (nem username) |
| `Auth.AuthorizationMiddleware.unauthorizedHandler.url` | App `Users` / `login` (`plugin` null) |

**Permissions (role panel):** `new` → csak `prefix New`; `member`/`editor` → Member; `clubpresident` → Clubpresident; `president`/`vicepresident` → President; `superuser`/`admin` → Admin (+ wildcard).

**Nincs** `/{lang}/…` — panelek: `/admin`, `/new`, `/member`, `/clubpresident`, `/president`.


### 2.3 `config/permissions.php` — **kritikus**

Az App `Users.controller = Users` miatt a request **`plugin` = `null`**.

```php
// HELYES — plugin kulcs NINCS, vagy null is OK
[
    'controller' => 'Users',
    'action' => [/* login, register, … */],
    'bypassAuth' => true,
],

// ROSSZ — CakeDC Rbac: false !== null → nincs match → login redirect loop
[
    'plugin' => false,  // NE!
    'controller' => 'Users',
    …
]
```

Tünet: `/login?redirect=/login?redirect=…` → **URI Too Long**.  
Védelem: `SanitizeAuthRedirectMiddleware` + helyes permissions.

Logged-in: `profile`, `changePassword`, `logout` (`role => '*'`).  
Panelek: szerepkör → saját prefix (`new` → `New` csak; `admin`/`superuser` → Admin). Login cél: `RoleHome` / `EVENT_AFTER_LOGIN`.  
Setups: `SetupAccess` (Admin panelen).

---

## 3. App réteg (kötelező fájlok)

| Fájl | Szerep |
|------|--------|
| `src/Controller/UsersController.php` | Extends CakeDC Users; `login` layout; register country/locale; cookie remember |
| `src/Model/Table/UsersTable.php` | `country_id` validáció + belongsTo Countries; OneTimeLoginLink **wrapper** (CakePHP 5.3 deprecation) |
| `src/Utility/BrowserLocale.php` | Accept-Language → látható `countries.locale` |
| `src/Utility/AdminCountry.php` | Working country: session + cookie (**≥ 1 év**, pl. `+400 days`); `optionsWithLocale()` |
| `src/Middleware/LocaleMiddleware.php` | Admin / Member / egyéb (auth) locale |
| `src/Middleware/SanitizeAuthRedirectMiddleware.php` | Nested `/login?redirect=/login…` tisztítás |
| `templates/layout/login.php` | ValiAdmin + simple-notify + Flash toast |
| `templates/Users/*.php` | **App path** (nem `templates/plugin/CakeDC/…`) |
| `webroot/css/pages/users_auth.css` | Box auto-height; local-login-form flow; Select2 z-index |
| `webroot/js/pages/users_auth_country.js` | Ország Select2 (login + register) + `?country_id=` reload |
| Migráció | `users.country_id` → `countries.id` (nullable FK OK; register: required) |

### 3.1 Template path szabály

`Users.controller = Users` (App) → Cake a **`templates/Users/`** mappát keresi.  
**Ne** tedd a override-okat `templates/plugin/CakeDC/Users/Users/` alá (MissingTemplate).

Kötelező App templatek:

- `login.php`, `register.php`, `request_reset_password.php`
- `profile.php`, `change_password.php`
- (opcionális: további CakeDC actionök, ha használod őket)

### 3.2 `UsersController` viselkedés

- `AUTH_LAYOUT_ACTIONS` → `viewBuilder()->setLayout('login')`.
- **Register:**
  - Ország lista: `AdminCountry::optionsWithLocale()` → címke `Név (ISO) — locale`.
  - Default ország: explicit POST/`?country_id=` → cookie/session (`AdminCountry::id`) → HU fallback.
  - Explicit ország → UI locale = `countries.locale` + `BrowserLocale::remember` + Translate locale.
  - POST/GET ország → `AdminCountry::set()` cookie **≥ 1 év**.
  - `normalizeRegistrationData`: username = email ha üres; `country_id` int → mentés `users.country_id`.
- **Profile:** country label; ha `is_superuser` → Superuser badge + mező; „Change password” → `UsersUrl::actionUrl('changePassword')`.

### 3.3 OneTimeLoginLink (CakePHP 5.3+)

A Table-proxy behavior hívás deprecation → warning HTML → „Unable to emit headers”.  
`UsersTable`-en explicit:

```php
public function sendLoginLink(string $name): void
{
    $this->getBehavior('OneTimeLoginLink')->sendLoginLink($name);
}
// + loginWithToken(...)
```

---

## 4. Layout + Flash toast

### 4.1 `templates/layout/login.php`

1. ValiAdmin CSS/JS CDN + Bootstrap Icons.
2. Helyi: `simple-notify` + `pages/users_auth`.
3. **Nincs** `Html->image` logo a boxban.
4. Tartalom: `$this->fetch('content')`.
5. Layout végén (Admin mintára):

```php
<?php if (!empty($this->getRequest()->getSession()->read('Flash'))): ?>
<script>
<?= $this->element('admin/script_flash') ?>
<?= $this->Flash->render('auth') ?>
<?= $this->Flash->render() ?>
</script>
<?php endif; ?>
```

### 4.2 Flash elemek

`AppView::usesFlashToast()` = `true`, ha:

- `prefix === Admin`, **vagy**
- `layout === login`

Ilyenkor `templates/element/flash/{success,error,info,warning,default}.php` → `flashMessage(...)` JS (nem HTML `.message` div).

### 4.3 ValiAdmin CSS / struktúra (minta)

Minta: `CakeDC-Login-layout-with-KeyCloak/templates/layout/login.php`.

| Elem | Szerep |
|------|--------|
| `.login-box.local-login` | Helyi face aktív (mintában Keycloak ↔ local flip) |
| `.local-login-form` | Mezők / gombok (App templatek) |
| `.login-form` | Mintában Keycloak face — nálunk **nem** használjuk a tartalomhoz |

| Probléma | Ok | Fix |
|----------|-----|-----|
| Üres / szétcsúszott keret | Absolute `.login-form` + `min-height: 0` (nincs magasság) | `local-login` + `local-login-form` + `users_auth.css` flow |
| Mezők láthatatlanok | `.local-login .login-form { opacity: 0 }` | Tartalom a `.local-login-form`-ban legyen |
| Túl magas üres box | ValiAdmin fix min-height + üres absolute face | flow layout → magasság = tartalom |

---

## 5. Regisztráció — ország

1. **Első mező** a formon: `country_id` **Select2** (`theme: bootstrap-5`, kereshető; assetek a register templateben).
2. Címke: `Name (ISO2) — locale` (pl. Franciaország (FR) — fr_FR vs (FX) — en_FX).
3. Change → JS reload `?country_id=N` → oldal nyelve = ország locale.
4. Megjegyzés: session + cookie `AdminWorkingCountryId` (**≥ 1 év** / `+400 days`).
5. Mentés: `users.country_id` (validáció `validationRegister` + `existsIn Countries`).  
   **Figyelem:** a `nonNegativeInteger('country_id')` már a `validationDefault`-ban van; ne add újra a `validationRegister`-ben (Cake Exception: rule already exists → regisztráció elhasal).

---

## 6. Header profil menü (Admin)

`templates/element/admin/header_profile.php`:

1. **Profile** → `UsersUrl::actionUrl('profile')` (profiladatok)
2. **Change password** → `changePassword` (ugyanaz, mint a profil gomb)
3. **Log out** → `logout`

CSS (`style.css`):

```css
.profile-dropdown {
  min-width: 240px;
  width: max-content;
  max-width: min(320px, calc(100vw - 1.5rem));
}
.profile-dropdown .dropdown-item,
.profile-dropdown .notify-item,
.profile-dropdown span {
  white-space: nowrap; /* „Jelszó módosítása” ne törjön */
}
```

---

## 7. Middleware sorrend (auth-szel)

```
ErrorHandler → HostHeader → SanitizeAuthRedirect → Asset → Routing
→ Locale → BodyParser → NormalizeLocalizedDate → NormalizeLocalizedNumber → Csrf
```

`LocaleMiddleware` auth (nincs Admin/Member prefix): `BrowserLocale::resolve` + opcionális remembered locale; országnevek Translate locale.

---

## 8. i18n

Minden auth UI szöveg: `__('English msgid')` + `.po` a **látható országok `locale` értékei** szerint.

| Hol | Mi |
|-----|-----|
| `resources/locales/default.pot` | msgid lista (auth stringek is) |
| `resources/locales/hu_HU/default.po` | teljes magyar katalog |
| `resources/locales/{countries.locale}/default.po` | auth UI fordítás (országváltáskor azonnal) |

Újraépítés: `php tmp/build_auth_locale_pos.php` (DB látható locale-ok → nyelvcsalád map).

Auth locale (oldal nyelve): session / cookie (≥1 év) / böngésző / választott ország.  
**Login után:** `Users.country_id` → `Countries.locale` (ha van); különben a login képernyő nyelve. Session + cookie frissül.  
**Teljes Admin UI** ugyanezt használja (`BrowserLocale::forLoggedIn`) — **nem** marad `App.adminLocale`-on, ha van login nyelv.

Részletek: [i18n.md](i18n.md).

---

## 9. Új projekt checklist

- [ ] `composer require cakedc/users`
- [ ] `Users.config` + `config/users.php` (`table`/`controller` = App)
- [ ] `config/permissions.php` — **ne** `plugin => false` az App Usersre; `bypassAuth` login/register/…
- [ ] `SanitizeAuthRedirectMiddleware` a queue-ban (Routing előtt)
- [ ] `App\Controller\UsersController` + `UsersTable` (+ OneTimeLogin wrappers)
- [ ] `users.country_id` migráció + Countries kapcsolat
- [ ] `templates/layout/login.php` (ValiAdmin, **nincs** box-logo, simple-notify Flash)
- [ ] `templates/Users/{login,register,request_reset_password,profile,change_password}.php`
- [ ] `users_auth.css` + `users_register.js`
- [ ] Register: ország **első** mező; cookie ≥ 1 év; mentés DB-be
- [ ] Header: Profile + Change password + Logout; széles `profile-dropdown`
- [ ] Flash toast: `usesFlashToast()` login layouton
- [ ] `.po` msgid-ek
- [ ] Smoke: `/login` 200, mezők látszanak; rossz jelszó → toast; register ország váltás → locale; login → Admin Dashboard

---

## 10. Gyakori hibák

| Tünet | Ok | Teendő |
|-------|-----|--------|
| URI Too Long /login?redirect=… | permissions `plugin => false` vagy hiányzó bypassAuth | permissions + Sanitize middleware |
| Login után „nem érhető el” / redirect loop | rossz permission / rossz login cél | `RoleHome` + szerepkör→saját prefix a permissionsben |
| MissingTemplate Users/login | template a plugin path alatt | tedd `templates/Users/` |
| Üres / szétcsúszott login box | `.login-form` absolute + box `min-height: 0`; vagy hiányzó `local-login` | Minta: `login-box local-login` + `.local-login-form` + CSS flow (`users_auth.css`) |
| Flash HTML a box tetején | nincs simple-notify / HTML fallback | login layout script + `usesFlashToast` |
| Behavior deprecation / headers | Table proxy OneTimeLogin | explicit `getBehavior(…)` wrapper |
| Change password szöveg törik | `.profile-dropdown { width: 170px }` | `min-width` + `nowrap` |

---

## 11. Kapcsolódó fájlok (referencia inventory)

```
config/users.php
config/permissions.php
src/Application.php
src/Controller/UsersController.php
src/Model/Table/UsersTable.php
src/Utility/AdminCountry.php
src/Utility/BrowserLocale.php
src/Middleware/LocaleMiddleware.php
src/Middleware/SanitizeAuthRedirectMiddleware.php
src/View/AppView.php                    # usesFlashToast()
templates/layout/login.php
templates/Users/*.php
templates/element/admin/header_profile.php
templates/element/admin/script_flash.php
templates/element/flash/*.php
webroot/css/pages/users_auth.css
webroot/css/style.css                   # .profile-dropdown
webroot/js/pages/users_register.js
webroot/plugins/simple-notify/
```
