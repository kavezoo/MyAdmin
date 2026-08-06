# Tagság jelentkezés — `new` → profil → clubpresident → `member`

Folyamat a frissen regisztrált (`role=new`) felhasználóktól a teljes értékű tagságig (`role=member`).

| Dokumentum | Mikor |
|------------|-------|
| **[membership-greenfield.md](membership-greenfield.md)** | **Új projekt / greenfield** — lépéssorrend, séma, checklist, rögzített szabályok |
| [users-auth.md](users-auth.md) | Auth + panelek baseline |
| Cursor | `membership-greenfield.mdc`, `users-auth.mdc` |

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
- Klub lista: `ClubsTable::optionsForCountry(country_id)` — csak az adott ország **enabled + visible** klubjai, **és** az adott évben befizetett országos (nemzeti) tagdíj (`national_membership_fee_date` éve = aktuális év); `pos` majd `name` sorrend. A user jelenlegi klubja akkor is listázódik, ha a tagdíj hiányzik.
- Profil / complete-profile **országváltás**: nincs oldal-újratöltés / leave kérdés — AJAX `UsersController::clubsForCountry` frissíti a klub Select2-t.
- Profil szerkesztés ország select: **minden** `Countries.visible = true` sor (`AdminCountry::visibleOptionsWithLocale`), + a user mentett országa ha hiányzik a listából.
- Alapértelmezett kijelölés: `users.country_id` és `users.club_id`.

---

## 2. Felhasználói út

1. **Regisztráció** → `role=new`, `membership_status=incomplete`, `club_id=0`.
2. **Bejelentkezés** → `EVENT_AFTER_LOGIN`: ha `new` és hiányos a profil → redirect `/complete-profile`.
3. **`/new/*`** → `New\AppController::beforeFilter` ugyanezt kényszeríti, amíg a profil hiányos.
4. **Profil mentés** (`UsersController::completeProfile`) → `MembershipService::onProfileCompleted`:
   - `membership_status=pending`
   - email minden `clubpresident` + ugyanaz a `club_id` + `active`/`enabled` usernek
   - `application_notified=1`
5. **New Dashboard**
   - **Hiányos** profil (nincs név és/vagy klub): figyelmeztetés + „Profil kiegészítése” CTA — amíg hiányzik, **nem** tud jelentkezni; a `/new` dashboard megtekinthető, más `/new/*` → `/complete-profile`.
   - **Kész** profil: csak **„Elfogadásra vár”** üzenet (+ Profile kártya); `onProfileCompleted` heal ha státusz beragadt.
6. **Clubpresident** → `/clubpresident/members`:
   - **Fent:** pending jelentkezők **kártyákon** (név, email, telefon, ország, klub, beadva; avatar; **shadow**)
   - **Approve** (SWAL zöld/question) → `MembershipService::approve`: `role=member`, `membership_status=approved`, **`membership_joined_date` = ma**, email
   - **Reject** (SWAL warning) → `MembershipService::reject`: `users.enabled=false`
   - **Lent:** aktív tagok táblázata (tagdíj)
6b. **President / VP** → `/president/members` (opcionális kapcsoló):
   - Switch **„Show pending applicants”** → ugyanazok a kártyák, de **ország** scope (`country_id`)
   - Approve / Reject: `MembershipService` — president/vp → `country_id` match; clubpresident → `club_id` match
7. **Reject** után a jelentkező nem tud újra belépni (`RequireUserEnabledMiddleware`).
8. Legacy `/clubpresident/applicants` → redirect a Members indexre (nincs külön Applicants menü).

### Tagdíj dátumok (`users` / `clubs`)

| Mező | Tábla | Jelentés |
|------|-------|----------|
| `club_membership_fee_date` | `users` | Helyi klub tagdíj befizetés dátuma (év = dátum éve → érvényes tagság arra az évre) |
| `national_membership_fee_date` | `users` | Országos pipa egyesület tagdíj — EN msgid: **National pipe association membership fee**; HU: **MPE tagdíj** |
| `national_membership_fee_date` | `clubs` | Klub éves tagdíja az országos pipa egyesület felé; Clubs listán ugyanaz a címke (HU: **MPE tagdíj**) |

- Clubpresident: `/clubpresident/members` — **csak** a bejelentkezett user `club_id` klubjának tagjai / jelentkezői (más klub **soha**); lista: `membershipRosterRoles` (member, editor, clubpresident, president, vp — **önmaga is**); klub tagdíj **egy gomb + SWAL** → mai dátum; oszlopban zöld pipa vagy piros „Outstanding” gomb; **Enable / Disable** (SWAL warning/success; napló ha activity logging be van kapcsolva); tiltott tagok (`enabled=0`) is a listán.
- **Lista név:** vastag név + alatta lefordított role (`AppRoles::label` — pl. Tag, Új tag, Klub elnök); element: `users/list_name_cell`.
- **Edit:** ceruza / modal Edit / view „Edit” → `…/members/edit/{id}` (közös `element/users/member_edit_form`: név, telefon, tagdíj dátum Tempus+Popper; Clubpresident = klub díj; President = országos díj + enabled). Tagdíj dátum változás → `event_logs` ha `activity_logging_enabled`.
- **President / vicepresident:** `/president/members` — ország `country_id` szerinti roster (ugyanazok a role-ok, **önmaga is**; enabled=0 is látszik); országos tagdíj rögzítés (SWAL); **Enable / Disable** gomb (AJAX + SWAL, `users.enabled`); switch „Only national fee paid”; switch **„Show pending applicants”** → `role=new` + `pending` jelentkező kártyák (Approve / Reject, ország scope); klub tagdíj oszlop csak olvasható; **edit:** role select (`AppRoles::assignableOptionsForActor`) — member…president (nincs admin); **VP nem** módosíthat / nem állíthat `president` role-t; **president/admin** igen; president megváltoztathatja a VP role-ját (akár member / clubpresident / president).
- **President Clubs:** `/president/clubs` — ország klubjainak teljes CRUD (name, enabled, visible, pos, **`user_count`** CounterCache); **`club_president_id`** (kijelölt klubelnök Users.id); **`national_membership_fee_date`** (klub → országos pipa egyesület éves tagdíj, év = dátum éve); index oszlopcímke: `MembershipFee::clubEntityFeeLabel()` → EN *National pipe association membership fee*, HU **MPE tagdíj**; Outstanding gomb / zöld pipa + SWAL → mai dátum; **email** a klubelnöknek (`MembershipMailer::clubNationalFeeRecorded`, szöveg: `__('the national pipe association')` → HU **az MPE**); **napló** ha `activity_logging_enabled` (`MembershipFee::clubEntityActivityDescriptions`); view: tagdíj panel pipával (`profile_club`); **klubelnök** AJAX Select2: ugyanaz az ország + **nem** `role=new` → `clubs.club_president_id` + `club_id`; **member/editor → `clubpresident`**; **president/vp lehet klubelnök** (role **változatlan**); előző tiszta `clubpresident` → **member**; magasabb rangú előző → role megmarad; indexen `user_count` + elnök név → user modal (Edit/View → Members); **view:** Member list félkövér linkek + Related records TAB (Users/Members) + linked modal; törlés tiltva ha `user_count > 0`. Admin Clubs CRUD később.
- **Profil:** tagdíj panel **2 oszlop egy sorban** (bal: klub tagdíj, jobb: országos/MPE); **warning** stílus (sárga) ha nincs befizetve — **nem** danger/piros; zöld pipa + dátum ha igen.
- **Vezérlőpult:** ha van klub és a klub tagdíj nincs befizetve a tárgyévre → `element/panel/club_fee_unpaid_alert` (**alert-warning**) a New / Member / Clubpresident / President dashboardon (`PanelAppController` → `clubFeeUnpaid`).
- **Napló:** `EventLogBehavior` + `ActivityLogSetup::isLoggingEnabled` (ország Setup `activity_logging_enabled`). User tagdíj: `MembershipFee::activityDescriptions`. **Klub → országos tagdíj** (`clubs.national_membership_fee_date`, pl. Outstanding gomb / `updateNationalFee`): `MembershipFee::clubEntityActivityDescriptions` (module=`Clubs`). Jóváhagyás / enable-disable: `MembershipProfile::activityDescriptions`. Ha a globális naplózás ki van kapcsolva az országra → **nincs** `event_logs` sor.

**Profil klubváltás:**
- **member / editor:** `/edit` → más klub → `role=new`, `membership_status=pending`, **`membership_joined_date` = null**, **csak** `club_membership_fee_date` = null (`MembershipFee::clearClubFeeOnClubSwitch`); **`national_membership_fee_date` érintetlen**; clubpresident értesítés; redirect `/new`. Figyelmeztetés: **alert-warning**. **RestrictNewRoleMiddleware:** csak `/new` + profil/auth URL-ek.
- **clubpresident / president / vicepresident:** `/edit` → más klub → **role változatlan**, nincs re-application; csak `club_id` + klub tagdíj nullázás; ha a régi klub `club_president_id`-je ő volt → törölve (`ClubsTable::clearDesignatedPresidentIfUser`); redirect `/profile`.

---

## 3. Adatbázis

| Fájl | Tartalom |
|------|----------|
| `config/schema/clubs.sql` | `clubs` tábla (`enabled` DEFAULT 1, `visible`, `pos`, `club_president_id`, `national_membership_fee_date`) |
| `config/Migrations/20260805170000_AddNationalMembershipFeeDateToClubs.php` | klub → országos éves tagdíj dátum |
| `config/Migrations/20260806090000_AddClubPresidentIdToClubs.php` | kijelölt klubelnök `club_president_id` + backfill |
| `config/schema/users_membership.sql` | `membership_status`, `membership_joined_date`, `application_notified` |
| `config/Migrations/20260805150000_AddMembershipJoinedDateToUsers.php` | `membership_joined_date` + backfill approved tagokra |
| `tmp/seed_membership.php` | séma + demo klubok + clubpresident `club_id` |

`users.club_id` már létezett (0 = nincs klub). FK a `clubs.id`-re soft (0 megengedett).

---

## 4. Kód térkép

| Réteg | Fájl |
|-------|------|
| ACL / státusz | `src/Auth/MembershipProfile.php` (`FIELD_JOINED`, `isJoined`, activityDescriptions) |
| Tagdíj | `src/Utility/MembershipFee.php` (`isClubFeeUnpaid`, `clearClubFeeOnClubSwitch`, …); panel warning: `element/panel/club_fee_unpaid_alert` (`PanelAppController`) |
| Service | `src/Service/MembershipService.php` |
| Mailer | `src/Mailer/MembershipMailer.php` + `templates/email/{html,text}/membership_*.php` |
| Profil form | `UsersController::completeProfile` / `edit`, `templates/Users/complete_profile.php` / `edit.php`; view: `profile` → `view.php` |
| Clubpresident tagok + jelentkező kártyák | `Clubpresident\MembersController` (+ legacy `ApplicantsController` redirect); `edit` + `form` |
| Tag edit form | `templates/element/users/member_edit_form.php` (+ `…/Members/form.php`) |
| UI kártyák | `templates/element/clubpresident/applicant_cards.php`, `css/pages/clubpresident_applicants.css` |
| President Clubs CRUD | `President\ClubsController`, `templates/President/Clubs/`, `js/pages/president_clubs_form.js`; `clubs.user_count` CounterCache (`Users` → `Clubs`) |
| Clubs séma | `config/schema/clubs.sql`, migráció `20260805160000_AddUserCountToClubs` |
| President enable/disable | `President\MembersController::toggleEnabled` + `js/pages/president_members.js` |
| President pending + approve | `President\MembersController` (`show_applicants` switch, `approve`/`reject`); `MembershipService::approverMayActOnApplicant` |
| Role ACL | `AppRoles::canAssignRole`, `canEditTargetRole`, `assignableOptionsForActor`, `shouldDemoteFromClubPresident` |
| Klubelnök assign | `ClubsTable::assignClubPresident`, `clubs.club_president_id` migráció |
| Header session | `element/admin/header.php` — név + rang |
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

- [ ] Migráció: `membership_joined_date`, `club_president_id`
- [ ] Clubpresident: egy **Members** menü; kártyák fent + tábla lent
- [ ] President: **Show pending applicants** kapcsoló + approve/reject ország scope
- [ ] Approve → joined date ma + role member + email
- [ ] Reject → SWAL warning + `enabled=0`
- [ ] Klubelnök: member→clubpresident; president/vp role megmarad; előző clubpresident→member
- [ ] President Members edit: role select; VP nem president-et
- [ ] Klubváltás member/editor: csak klub tagdíj null + role=new; officer (clubpresident/president/vp): role megmarad
- [ ] Dashboard: `club_fee_unpaid_alert` unpaid klub tagdíjnál
- [ ] Header: név + rang (minden prefix, közös layout)
- [ ] President: Enable/Disable AJAX + SWAL; disabled sorok látszanak
- [ ] Activity logging Setup off → nincs event_logs sor enable/approve-nál
- [ ] Új regisztráció → complete profile → clubpresident email → Members kártya

**Greenfield teljes lista:** [membership-greenfield.md](membership-greenfield.md) §11.

---

## 7. Taglisták (President / Clubpresident)

A **Members** index **Admin index playbook** — nem egyszerű HTML tábla.

| Elem | Kötelező |
|------|----------|
| Fejléc változók | `$rowDoubleClickAction`, `$showIdColumn`, `$showCreatedColumn`, … |
| Sort | Minden oszlop: `Paginator::sort` + **típusosztály** a `th`-n; `sortableFields` kulcs = `sort()` első arg (rövid név / `Clubs.name`) — különben nincs ASC/DESC ikon |
| Clubpresident kártyák | Pending `new`+`pending`+`enabled` jelentkezők a tábla **fölött** — **külső panel card** + nested `20rem` kártyák (`shadow`); **ugyanaz** a `club_id` scope |
| President pending | Kapcsoló **Show pending applicants** → `element/clubpresident/applicant_cards`; **ország** scope; approve/reject ugyanaz a SWAL JS |
| Clubpresident scope | `Users.club_id` = bejelentkezett user klubja (`presidentClubId()` DB-ből; `scopeToPresidentClub()`); más klub tagja **nem** látszik / nem approve-olható |
| President scope | `Users.country_id` = officer country; pending + roster |
| President enable | Actions: ban / check gomb → `toggleEnabled` AJAX |
| Clubpresident enable | Ugyanaz: ban / check → `toggleEnabled`; tiltott tagok is a listán (`enabled=0`); SWAL warning (disable) / success (enable) |
| Edit | Ceruza / `editUrl` → `edit` action + `member_edit_form` (President: role select + enabled) |

---

## 8. Greenfield / új projekt

Új CakePHP projektben a tagság + role panelek **teljes felépítése**: **[membership-greenfield.md](membership-greenfield.md)** (lépéssorrend, DB, ACL, UI checklist).  
Auth előfeltétel: [users-auth.md](users-auth.md) + [uj-projekt.md](uj-projekt.md) §2.9.  
Cursor rule: `.cursor/rules/membership-greenfield.mdc`.
