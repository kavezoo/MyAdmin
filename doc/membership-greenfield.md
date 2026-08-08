# Tagság + role panelek — greenfield playbook

**Cél:** új CakePHP 5 projektben, ahol CakeDC Users + szerepkör panelek + tagsági jelentkezés kell, az agent **ebből** építse fel a teljes folyamatot — ne találjon ki új UX-et.

| Kapcsolódó | Tartalom |
|------------|----------|
| Auth baseline | [users-auth.md](users-auth.md) §0–2, §2.9 [uj-projekt.md](uj-projekt.md) |
| Folyamat részletek | [membership.md](membership.md) |
| Séma mátrix | [uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md) §1.6 |
| Cursor rules | `users-auth.mdc`, `membership-greenfield.mdc` |

---

## 1. Mikor kell ez a playbook

- Regisztráció → `role=new` → profil kiegészítés → klubelnök / országos elnök jóváhagy → `role=member`
- Panelek: `/new`, `/member`, `/clubpresident`, `/president` (+ `/admin`)
- Tagdíj: klub + országos (MPE) dátum mezők, év = dátum éve
- Klubok CRUD országos elnök panelen; klubelnök hozzárendelés

**Nem kell:** ha a projektnek nincs klub/tagság — ezt a playbookot hagyd ki.

---

## 2. Greenfield lépéssorrend (kötelező sorrend)

1. **Auth + panelek** — [users-auth.md](users-auth.md) + [uj-projekt.md](uj-projekt.md) §2.9 (`RoleHome`, `PanelAppController`, permissions, middleware)
2. **DB séma** — §3 (migrációk + `config/schema/*.sql`)
3. **`AppRoles`** — role konstansok, `label()`, `membershipRosterRoles()`, ACL helperök (§5)
4. **`MembershipProfile`** — státusz, kötelező mezők, `displayName`, activity descriptions
5. **`MembershipService`** — `onProfileCompleted`, `approve`, `reject`, `onClubChanged`; approver scope (§6)
6. **`MembershipFee`** — `isPaidForYear`, `clearClubFeeOnClubSwitch`, `isClubFeeUnpaid`, activity descriptions
7. **`UsersController`** — `completeProfile`, `edit`, `clubsForCountry` (AJAX, nincs reload)
8. **Clubpresident `MembersController`** — kártyák + lista + approve/reject + klub tagdíj
9. **President `MembersController`** — ország roster + national fee + role select + pending switch + approve/reject
10. **President `ClubsController`** — CRUD + `club_president_id` + national club fee
11. **UI elementek** — §8; dashboard figyelmeztetések; profil tagdíj **warning** (nem danger)
12. **Email** — `MembershipMailer` + templates
13. **Permissions** — president/vp → President prefix `*`; clubpresident → Clubpresident `*`
14. **`.po`** msgid-ek + `doc/valtozasok.md`

---

## 3. Adatbázis — kötelező mezők

### `users` (CakeDC Users + app)

| Mező | Típus | Jelentés |
|------|-------|----------|
| `role` | string | `AppRoles` kulcsok |
| `country_id` | int | Ország (regisztráció / profil) |
| `club_id` | int | 0 = nincs; FK soft |
| `language_id` | int NULL | belépéskor a login UI nyelv (`UserUiLanguage`); FK soft → `languages.id`; email locale |
| `enabled` | bool | App belépéskapu (`findActive` + middleware) |
| `membership_status` | string | `incomplete` / `pending` / `approved` |
| `membership_joined_date` | date | Tagként csatlakozás (approve napja) |
| `application_notified` | bool | Email a klubelnök(ek)nek elküldve |
| `club_membership_fee_date` | date | Klub tagdíj (év = dátum éve) |
| `national_membership_fee_date` | date | Országos / MPE tagdíj |

### `clubs`

| Mező | Típus | Jelentés |
|------|-------|----------|
| `country_id` | int FK | Ország |
| `name` | string | |
| `enabled` | bool | Profil select: csak enabled |
| `visible` | bool | |
| `pos` | int | **DB DEFAULT 1000** — PHP-ból ne állítsd |
| `user_count` | int | CounterCache `Users.club_id` |
| `club_president_id` | uuid/null | Kijelölt klubelnök (`Users.id`) |
| `national_membership_fee_date` | date | Klub → országos egyesület éves díj |

Séma fájlok: `config/schema/clubs.sql`, `config/schema/users_membership.sql`; migrációk: `doc/membership.md` §3.

---

## 4. Szerepkörök és panelek

| `Users.role` | Panel | URL prefix |
|--------------|-------|------------|
| `new` | New | `/new` — **RestrictNewRoleMiddleware** |
| `member`, `editor` | Member | `/member` |
| `clubpresident` | Clubpresident | `/clubpresident` (kell `club_id`) |
| `president`, `vicepresident` | President | `/president` (+ Member; Clubpresident ha `club_id`) |
| `admin`, `superuser` | Admin | `/admin` |

**Roster listák** (`membershipRosterRoles`): member, editor, clubpresident, president, vicepresident — **nincs** `new`, admin, superuser a role listában.

**Önmaga a taglistán (kötelező):** `PanelMemberListTrait::membershipRosterOrSelfCondition()` = roster **vagy** bejelentkezett `Users.id` (ugyanabban a klub/ország scope-ban). Így admin/superuser panelváltáskor is megjelenik. UI: **You** badge + zöld sor. Rule: `panel-nav-conventions.mdc`.

**Dashboard ↔ sidebar:** `App\Utility\PanelNav` — minden panel cél kártya **és** menüpont. Rule: `panel-nav-conventions.mdc`.

**Saját klub a klublistán:** `My club` badge (`Users.club_id`) — President Clubs + Member/Clubpresident böngésző.

**Header** (`element/admin/header.php`): hamburger mellett **név** + **rang** (`AppRoles::label`); `is_superuser` flag → „· Superuser” ha role ≠ `superuser`.

---

## 5. Role szerkesztés (President Members edit)

Csak **President / VP** taglistán: `element/users/member_edit_form` + `showRole`.

| Szabály | Implementáció |
|---------|----------------|
| Lista max | member … president (`AppRoles::presidentAssignableRoles`) |
| Admin / superuser / new | **Nincs** a listában |
| VP | **Nem** állíthat / nem módosíthat `president` role-t |
| President / admin | `president` role állítható / módosítható |
| President | VP role szabadon (member / clubpresident / president …) |

Helperök: `AppRoles::assignableOptionsForActor()`, `canAssignRole()`, `canEditTargetRole()`.

### Klubváltás (President Members edit)

| Szabály | Implementáció |
|---------|----------------|
| Select lista | Ország összes **enabled + visible** klubja |
| Tagdíj szűrés | **Nincs** (`optionsForCountry(..., requireNationalFeePaid: false)`) |
| Profil select | Továbbra is fee-szűrt (`requireNationalFeePaid: true`) |
| Klubváltáskor | Role változatlan; `club_membership_fee_date` = null; régi `club_president_id` törlés ha ő volt |

---

## 6. Klubelnök (`clubs.club_president_id`)

Mentés: `ClubsTable::assignClubPresident($clubId, $countryId, $userId)`.

| Kiválasztott user role | Eredmény |
|------------------------|----------|
| `member` / `editor` | `role=clubpresident` + `club_id` + `club_president_id` |
| `president` / `vicepresident` (és fölötte) | **Role marad**; `club_id` + `club_president_id` |
| Előző kijelölt elnök, tiszta `clubpresident` | → `member` (club_id maradhat) |
| Előző kijelölt elnök, president/vp | **Role marad** |

`findClubPresident()`: először `clubs.club_president_id`, legacy fallback: `role=clubpresident` + `club_id`.

Select2: ugyanaz az ország, `role != new`.

---

## 7. Jelentkezők (approve / reject)

### Clubpresident (`/clubpresident/members`)

- **Mindig** fent: pending kártyák (`role=new`, `membership_status=pending`, `enabled=1`)
- Scope: **csak** `Users.club_id` = bejelentkezett user klubja
- Approve → `MembershipService::approve` (club scope)
- Element: `clubpresident/applicant_cards` + `clubpresident_applicants.css` (kártya **shadow**)

### Klub böngésző (`/clubpresident/clubs`, `/member/clubs`)

- Read-only lista + view; csak `visible` + `enabled` klubok.
- Ország szűrő: default = user `country_id`; select = országok, ahol van legalább egy ilyen klub (`PanelClubBrowserTrait`).
- Saját klub (`Users.club_id`) kiemelve („My club”).
- Template: `element/panel/clubs_index` / `clubs_view`.

### President / VP (`/president/members`)

- Kapcsoló: **Show pending applicants** (session `President.Members.show_applicants`)
- Bekapcsolva: ugyanaz a kártya element; scope: **ország** (`Users.country_id`)
- Approve / Reject: `President\MembersController` + `MembershipService` (country officer path)

### `MembershipService` approver jogosultság

| Approver role | Scope |
|---------------|-------|
| `president`, `vicepresident` | `approver.country_id === applicant.country_id` |
| Egyéb (pl. clubpresident) | `approver.club_id === applicant.club_id` |

Approve: `role=member`, `membership_status=approved`, `membership_joined_date=today`, email.  
Reject: `enabled=false` (SWAL warning).

---

## 8. Tagdíj és figyelmeztetések

| Hely | Szabály |
|------|---------|
| Profil unpaid | **warning** (sárga) — `membership_fee.css`; **nem** danger |
| Dashboard | `element/panel/club_fee_unpaid_alert` ha klub tagdíj unpaid (`PanelAppController`) |
| Klubváltás (member / editor) | **Csak** `club_membership_fee_date` null; **országos nem**; `role=new` + pending + redirect `/new` |
| Klubváltás (clubpresident / president / vp) | Role **változatlan**; csak klub + klub tagdíj null; nincs re-application (`AppRoles::keepsRoleOnClubSwitch`) |
| Klubváltás UX | `alert-warning` a formon; officer vs member szöveg különbözik |

Megjelenítés pénzhez: `MembershipFee::formatCurrency()` / `LocaleNumberParser::formatCurrency()` — rule `penznem-formatcurrency.mdc`.

---

## 9. UI elementek (másold / hozd létre)

| Element / asset | Hol |
|-----------------|-----|
| `element/panel/club_fee_unpaid_alert.php` | Dashboardok |
| `element/panel/dashboard_nav_cards.php` | Panel dashboard — **PanelNav** kártyák |
| `element/panel/sidebar_nav_items.php` | Sidebar — **ugyanaz** a PanelNav lista |
| `src/Utility/PanelNav.php` | Egy forrás: dashboard ↔ menü |
| `element/panel/clubs_index.php` | Klubböngésző + **My club** |
| `element/clubpresident/applicant_cards.php` | Clubpresident + President (pending) |
| `element/users/member_edit_form.php` | Tag szerkesztés |
| `element/users/membership_fee_status.php` | Profil tagdíj |
| `element/users/list_name_cell.php` | Taglista név + role |
| `css/pages/clubpresident_applicants.css` | Applicant kártyák |
| `css/pages/membership_fee.css` | Profil tagdíj panel |
| `js/pages/clubpresident_applicants.js` | Approve/reject SWAL (`PresidentApplicants` config is) |
| `js/pages/clubpresident_members.js` | Klub tagdíj + enable/disable |
| `js/pages/president_members.js` | Országos tagdíj + enable/disable |

President Members index: `PanelMemberListTrait`, national paid switch, pending applicants switch, linked club modal — rule `panel-member-index.mdc`.

---

## 10. Kód térkép (minimum fájlok)

```
src/Auth/AppRoles.php
src/Auth/MembershipProfile.php
src/Auth/PanelAccess.php
src/Auth/RoleHome.php
src/Service/MembershipService.php
src/Utility/MembershipFee.php
src/Controller/UsersController.php
src/Controller/PanelAppController.php
src/Controller/New/AppController.php
src/Controller/Clubpresident/MembersController.php
src/Controller/President/MembersController.php
src/Controller/President/ClubsController.php
src/Controller/President/EmailTemplatesController.php
src/Controller/Admin/CitiesController.php
src/Controller/Admin/CountiesController.php
src/Utility/EmailTemplateService.php
src/Utility/EmailTemplateSlugs.php
src/Mailer/MembershipMailer.php
src/Controller/Concerns/PanelMemberListTrait.php
src/Middleware/RestrictNewRoleMiddleware.php
src/Middleware/RequireUserEnabledMiddleware.php
templates/element/admin/header.php
templates/Users/{complete_profile,edit,view}.php
templates/President/EmailTemplates/
templates/Admin/{Cities,Counties}/
```

### Email sablonok + geo (Admin)

| Elem | Hol |
|------|-----|
| `email_templates` | ország + nyelv subject/body; Admin + President CRUD; egyediség `(country_id, language_id, slug)` |
| Locale | belépéskor `users.language_id` ← login UI; email: language_id → ország locale → default |
| Cities / Counties / Countries | Admin Settings CRUD (ref/geo) |
| Users / Clubs / Competitions / Email templates | Admin **top-level** domain CRUD (nem Settings) |
---

## 11. Ellenőrző checklist (smoke)

- [ ] Regisztráció → login → `/new`; hiányos profil → complete-profile CTA
- [ ] Profil kész → clubpresident email → pending kártya
- [ ] Clubpresident Approve → member + joined date + email
- [ ] President: „Show pending applicants” → kártyák ország szinten; Approve működik
- [ ] Klubelnök assign: member→clubpresident; president/vp role megmarad
- [ ] President Members edit: role select; VP nem állíthat president-et
- [ ] Klubváltás member/editor: csak klub tagdíj null + role=new; officer: role megmarad
- [ ] Klubváltás warning dashboard + profil (officer / member szöveg)
- [ ] Tagdíj unpaid: warning stílus (nem piros danger)
- [ ] Header: név + rang minden prefix alatt (közös `admin` layout)
- [ ] PanelNav: minden dashboard kártya = sidebar menü (és fordítva)
- [ ] Members lista: bejelentkezett user **látszik** (You badge) — admin panelváltáskor is
- [ ] Clubs lista: saját klub **My club** badge
- [ ] Reject → user nem lép be (`enabled=0`)
- [ ] Activity logging off → nincs felesleges `event_logs` approve-nál

---

## 12. Gyakori hibák

| Hiba | Helyes |
|------|--------|
| President approve: club_id check | Country officer → `country_id` match (`approverMayActOnApplicant`) |
| Klubelnök = mindig role clubpresident | President/vp elnök: role **marad**, csak `club_president_id` |
| Klubváltás nullázza MPE tagdíjat | **Csak** `club_membership_fee_date` |
| Officer klubváltás → role=new | clubpresident / president / vp: **role marad** (`keepsRoleOnClubSwitch`) |
| Unpaid tagdíj piros danger | **warning** CSS + `alert-warning` |
| Applicant approve URL clubpresident only | President prefix is `approve` / `reject` action |
| Taglista csak `role IN roster` | **`membershipRosterOrSelfCondition()`** — önmaga is (admin) |
| Új menüpont csak a sidebarban | **`PanelNav`** → dashboard + sidebar |
| Pending jelentkezők üres lista (contain Subclubs INNER) | Subclubs **LEFT** join |
| `pos` klub seedben 1,2,3 | DB DEFAULT **1000**; user formon állítja |

---

*Részletes folyamat és napló: [membership.md](membership.md). Auth: [users-auth.md](users-auth.md).*
