# Tagság jelentkezés — `new` → profil → clubpresident → `member`

Folyamat a frissen regisztrált (`role=new`) felhasználóktól a teljes értékű tagságig (`role=member`).

Kapcsolódó: [users-auth.md](users-auth.md), Cursor rule `.cursor/rules/users-auth.mdc`.

---

## 1. Állapotgép

| `users.membership_status` | Jelentés |
|---------------------------|----------|
| `incomplete` | Regisztráció után; hiányzó profilmezők |
| `pending` | Profil kész; clubpresident értesítve |
| `approved` | Clubpresident jóváhagyta → `role=member` |

`users.application_notified` — egyszeri email a clubpresident(ek)nek (1 = elküldve).

Kötelező profilmezők (`MembershipProfile::requiredFields()`):

- `first_name` (egy „név” mező), `country_id` (>0), `club_id` (>0, `clubs.enabled=1`, `visible=1`) — **telefon opcionális**
- Klub lista: `ClubsTable::optionsForCountry(country_id)` — csak az adott ország **enabled + visible** klubjai, `pos` majd `name` sorrend.
- Profil szerkesztés ország select: **minden** `Countries.visible = true` sor (`AdminCountry::visibleOptionsWithLocale`), + a user mentett országa ha hiányzik a listából.
- Alapértelmezett kijelölés: `users.country_id` és `users.club_id`; országváltás query csak más ország esetén üríti a klubot.

---

## 2. Felhasználói út

1. **Regisztráció** → `role=new`, `membership_status=incomplete`, `club_id=0`.
2. **Bejelentkezés** → `EVENT_AFTER_LOGIN`: ha `new` és hiányos a profil → redirect `/complete-profile`.
3. **`/new/*`** → `New\AppController::beforeFilter` ugyanezt kényszeríti, amíg a profil hiányos.
4. **Profil mentés** (`UsersController::completeProfile`) → `MembershipService::onProfileCompleted`:
   - `membership_status=pending`
   - email minden `clubpresident` + ugyanaz a `club_id` + `active`/`enabled` usernek
   - `application_notified=1`
5. **New Dashboard** — „jelentkezés elküldve, várakozás” üzenet.
6. **Clubpresident** → `/clubpresident/applicants` lista; **Approve** → `MembershipService::approve`:
   - `role=member`, `membership_status=approved`
   - email a jelentkezőnek login linkkel (`/login`)

**Profil klubváltás (member+):** más klub mentése → `role=new`, `membership_status=pending`, `Authentication::setIdentity` (session role is `new`), clubpresident értesítés; redirect `/new`. **RestrictNewRoleMiddleware:** csak `/new` + profil/auth URL-ek, más prefix → `/new`. Profil továbbra is szerkeszthető (`canEditOwnProfile` minden érvényes role-nál). UI: piros figyelmeztetés + SweetAlert.

---

## 3. Adatbázis

| Fájl | Tartalom |
|------|----------|
| `config/schema/clubs.sql` | `clubs` tábla (`enabled` DEFAULT 1, `visible`, `pos`) |
| `config/schema/users_membership.sql` | `membership_status`, `application_notified` |
| `tmp/seed_membership.php` | séma + demo klubok + clubpresident `club_id` |

`users.club_id` már létezett (0 = nincs klub). FK a `clubs.id`-re soft (0 megengedett).

---

## 4. Kód térkép

| Réteg | Fájl |
|-------|------|
| ACL / státusz | `src/Auth/MembershipProfile.php` |
| Service | `src/Service/MembershipService.php` |
| Mailer | `src/Mailer/MembershipMailer.php` + `templates/email/{html,text}/membership_*.php` |
| Profil form | `UsersController::completeProfile`, `templates/Users/complete_profile.php` |
| Jelentkezők | `Clubpresident\ApplicantsController`, sidebar menü |
| Login redirect | `Application` `EVENT_AFTER_LOGIN` |
| New gate | `Controller/New/AppController.php` |
| Role `new` lock | `RestrictNewRoleMiddleware` (auth után) |

Permissions: `completeProfile` a `role => '*'` Users action listában.

---

## 5. Email

- **applicationReceived** → clubpresident(ek), link: `/clubpresident/applicants`
- **membershipApproved** → új tag, link: `/login`

Transport: `config/app.php` / `app_local.php` `Email` + `EmailTransport`.

---

## 6. Ellenőrzőlista

- [ ] `php tmp/seed_membership.php` (clubs + oszlopok)
- [ ] Clubpresident usernek van `club_id` (seed vagy manuális)
- [ ] Új regisztráció → login → complete profile kötelező
- [ ] Mentés után clubpresident kap emailt (vagy log, ha nincs transport)
- [ ] Approve → role `member` + email login linkkel
- [ ] Új tag újra belép → Member panel
