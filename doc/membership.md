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

| Mező | Jelentés |
|------|----------|
| `membership_joined_date` | **Tagként csatlakozás dátuma** — ha nem NULL → a jelentkezés el van fogadva (logikai + dátum egyben) |
| `application_notified` | Egyszeri email a clubpresident(ek)nek (1 = elküldve) |

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
6. **Clubpresident** → `/clubpresident/members`:
   - **Fent:** pending jelentkezők **kártyákon** (név, email, telefon, ország, beadva; avatar ha van)
   - **Approve** (SWAL zöld/question) → `MembershipService::approve`: `role=member`, `membership_status=approved`, **`membership_joined_date` = ma**, email
   - **Reject** (SWAL warning) → `MembershipService::reject`: `users.enabled=false`
   - **Lent:** aktív tagok táblázata (tagdíj)
7. **Reject** után a jelentkező nem tud újra belépni (`RequireUserEnabledMiddleware`).
8. Legacy `/clubpresident/applicants` → redirect a Members indexre (nincs külön Applicants menü).

### Tagdíj dátumok (`users`)

| Mező | Jelentés |
|------|----------|
| `club_membership_fee_date` | Helyi klub tagdíj befizetés dátuma (év = dátum éve → érvényes tagság arra az évre) |
| `national_membership_fee_date` | Országos szövetség tagdíj (Magyarországon: MPE) |

- Clubpresident: `/clubpresident/members` — **csak** a bejelentkezett user `club_id` klubjának tagjai / jelentkezői (más klub **soha**); klub tagdíj **egy gomb + SWAL** → mai dátum; oszlopban zöld pipa vagy piros „Outstanding” gomb; **Enable / Disable** (SWAL warning/success; napló ha activity logging be van kapcsolva); tiltott tagok (`enabled=0`) is a listán.
- **Lista név:** vastag név + alatta lefordított role (`AppRoles::label` — pl. Tag, Új tag, Klub elnök); element: `users/list_name_cell`.
- **Edit:** ceruza / modal Edit / view „Edit” → `…/members/edit/{id}` (közös `element/users/member_edit_form`: név, telefon, tagdíj dátum Tempus+Popper; Clubpresident = klub díj; President = országos díj + enabled). Tagdíj dátum változás → `event_logs` ha `activity_logging_enabled`.
- **President / vicepresident:** `/president/members` — ország `country_id` szerinti tagok (enabled=0 is látszik); országos tagdíj rögzítés (SWAL); **Enable / Disable** gomb (AJAX + SWAL, `users.enabled`); switch „Only national fee paid”; klub tagdíj oszlop csak olvasható.
- **President Clubs:** `/president/clubs` — ország klubjainak teljes CRUD (name, enabled, visible, pos, **`user_count`** CounterCache); **klubelnök** AJAX Select2: ugyanaz az ország + **nem** `role=new` (member és fölötte bárki, önmaga is) → mentéskor `role=clubpresident` + `club_id`; indexen `user_count` + elnök név → user modal (Edit/View → Members); **view:** Member list félkövér linkek + Related records TAB (Users/Members) + linked modal; törlés tiltva ha `user_count > 0`.
- **Profil:** feltűnő piros blokk ha nincs befizetve; zöld pipa + dátum ha igen (klub + országos/MPE).
- **Napló:** `EventLogBehavior` + `ActivityLogSetup::isLoggingEnabled` (ország Setup `activity_logging_enabled`). Tagdíj: `MembershipFee::activityDescriptions`. Jóváhagyás / enable-disable: `MembershipProfile::activityDescriptions` (joined date, role, enabled). Ha a globális naplózás ki van kapcsolva az országra → **nincs** `event_logs` sor.

**Profil klubváltás (member+):** `/edit` oldalon más klub mentése → `role=new`, `membership_status=pending`, **`membership_joined_date` = null**, `Authentication::setIdentity`, clubpresident értesítés; redirect `/new`. **RestrictNewRoleMiddleware:** csak `/new` + profil/auth URL-ek (`/profile`, `/edit`, …), más prefix → `/new`.

---

## 3. Adatbázis

| Fájl | Tartalom |
|------|----------|
| `config/schema/clubs.sql` | `clubs` tábla (`enabled` DEFAULT 1, `visible`, `pos`) |
| `config/schema/users_membership.sql` | `membership_status`, `membership_joined_date`, `application_notified` |
| `config/Migrations/20260805150000_AddMembershipJoinedDateToUsers.php` | `membership_joined_date` + backfill approved tagokra |
| `tmp/seed_membership.php` | séma + demo klubok + clubpresident `club_id` |

`users.club_id` már létezett (0 = nincs klub). FK a `clubs.id`-re soft (0 megengedett).

---

## 4. Kód térkép

| Réteg | Fájl |
|-------|------|
| ACL / státusz | `src/Auth/MembershipProfile.php` (`FIELD_JOINED`, `isJoined`, activityDescriptions) |
| Service | `src/Service/MembershipService.php` |
| Mailer | `src/Mailer/MembershipMailer.php` + `templates/email/{html,text}/membership_*.php` |
| Profil form | `UsersController::completeProfile` / `edit`, `templates/Users/complete_profile.php` / `edit.php`; view: `profile` → `view.php` |
| Clubpresident tagok + jelentkező kártyák | `Clubpresident\MembersController` (+ legacy `ApplicantsController` redirect); `edit` + `form` |
| Tag edit form | `templates/element/users/member_edit_form.php` (+ `…/Members/form.php`) |
| UI kártyák | `templates/element/clubpresident/applicant_cards.php`, `css/pages/clubpresident_applicants.css` |
| President Clubs CRUD | `President\ClubsController`, `templates/President/Clubs/`, `js/pages/president_clubs_form.js`; `clubs.user_count` CounterCache (`Users` → `Clubs`) |
| Clubs séma | `config/schema/clubs.sql`, migráció `20260805160000_AddUserCountToClubs` |
| President enable/disable | `President\MembersController::toggleEnabled` + `js/pages/president_members.js` (SWAL: disable=warning, enable=success) |
| Clubpresident enable/disable | `Clubpresident\MembersController::toggleEnabled` + `js/pages/clubpresident_members.js` (ugyanaz a SWAL stílus; napló: `EventLogBehavior` + `MembershipProfile::activityDescriptions`) |
| Taglista index minta | `templates/President/Members/index.php`, `PanelMemberListTrait`, rule `.cursor/rules/panel-member-index.mdc` |
| Login redirect | `Application` `EVENT_AFTER_LOGIN` |
| New gate | `Controller/New/AppController.php` |
| Role `new` lock | `RestrictNewRoleMiddleware` (auth után) |

Permissions: `completeProfile` a `role => '*'` Users action listában.

---

## 5. Email

- **applicationReceived** → clubpresident(ek), link: `/clubpresident/members`
- **membershipApproved** → új tag, link: `/login`

Transport: `config/app.php` / `app_local.php` `Email` + `EmailTransport`.

---

## 6. Ellenőrzőlista

- [ ] Migráció: `membership_joined_date`
- [ ] Clubpresident: egy **Members** menü; kártyák fent + tábla lent
- [ ] Approve → joined date ma + role member + email
- [ ] Reject → SWAL warning + `enabled=0`
- [ ] President: Enable/Disable AJAX + SWAL; disabled sorok látszanak
- [ ] Activity logging Setup off → nincs event_logs sor enable/approve-nál
- [ ] Új regisztráció → complete profile → clubpresident email → Members kártya

---

## 7. Taglisták (President / Clubpresident)

A **Members** index **Admin index playbook** — nem egyszerű HTML tábla.

| Elem | Kötelező |
|------|----------|
| Fejléc változók | `$rowDoubleClickAction`, `$showIdColumn`, `$showCreatedColumn`, … |
| Sort | Minden oszlop: `Paginator::sort` + **típusosztály** a `th`-n; `sortableFields` kulcs = `sort()` első arg (rövid név / `Clubs.name`) — különben nincs ASC/DESC ikon |
| Clubpresident kártyák | Pending `new`+`pending`+`enabled` jelentkezők a tábla **fölött** — **külső panel card** (header + body) + nested `20rem` kártyák; **ugyanaz** a `club_id` scope |
| Clubpresident scope | `Users.club_id` = bejelentkezett user klubja (`presidentClubId()` DB-ből; `scopeToPresidentClub()`); más klub tagja **nem** látszik / nem approve-olható |
| President enable | Actions: ban / check gomb → `toggleEnabled` AJAX |
| Clubpresident enable | Ugyanaz: ban / check → `toggleEnabled`; tiltott tagok is a listán (`enabled=0`); SWAL warning (disable) / success (enable) |
| Edit | Ceruza / `editUrl` → `edit` action + `member_edit_form` (nem view) |
