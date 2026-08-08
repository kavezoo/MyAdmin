# Versenyek (competitions)

President kiírja → tag jelentkezik → klubelnök alcsapatot rendel → hivatalos jelentkezett.

**Cursor rule:** `.cursor/rules/competitions.mdc` (glob: Competition*).  
**Kapcsolódó:** [membership.md](membership.md) (panelek / PanelNav), [users-auth.md](users-auth.md).

---

## Táblák

| Tábla | Szerep |
|-------|--------|
| `competitions` | Versenykiírás (ország + rendező klub + dátumok + `minimum_team_size` + opcionális `racing_pipe_N_title` címkék). Összesítők (`lunch_for_the_attendant`, `user_count`, …) nem formmezők. |
| `competitions_clubs` | Alcsapat indulás (klub + verseny); **nincs `name`** — név = `subclubs` (`subclub_id`); `user_count` = **besorolt** tagok |
| `competitions_users` | Tag jelentkezés (`competition_id` kötelező; `competition_club_id` = alcsapat; amíg NULL = pending / még nem hivatalos) |
| `subclubs` | Alcsapat **név** rekord (`{klub short_name} {n}`); számláló **versenyenként** (új verseny → 1-től); kötelező FK a `competitions_clubs.subclub_id`-n |

---

## Panelek

| Prefix | URL | Funkció |
|--------|-----|---------|
| President | `/president/competitions` | CRUD kiírás (ország-scope); **view**: related tabs = min. létszámot elérő alcsapatok + azok assigned jelentkezői |
| Clubpresident | `/clubpresident/competition-teams` | **Teljes CRUD** alcsapat (`competitions_clubs` + kapcsolt `subclubs`); **törlés** csak ha `user_count = 0` (nincs besorolt tag); listán verseny = keskeny oszlop + **linked modal** (`competitionRecordGet`, read-only) |
| Clubpresident | `/clubpresident/competition-applicants` | Jelentkezők → alcsapat besorolás **vagy törlés** / edit adatok |
| Member | `/member` + `/member/competitions` | Dashboardon + listán: ország versenyei; jelentkezés / **visszavonás (törlés)** / archívum |

**PanelNav:** minden fenti cél a `PanelNav`-ban is (dashboard kártya + sidebar) — rule `panel-nav-conventions.mdc`.

### President adatlap (`/president/competitions/view`)

| Tab | Szűrés |
|-----|--------|
| Sub-teams | `competitions_clubs.user_count >= competitions.minimum_team_size` |
| Applicants | `status=assigned` **és** alcsapat a fenti min. létszámot elérők között |

Üres tab is megjelenik (`No related records.`). Helper: `CompetitionsClubsTable::filterMeetingMinimum()`.

**Layout** (mint Member): `competition-view-layout` — desktop: meta balra, **leírás jobbra**; mobil: meta → leírás. CSS: `webroot/css/pages/competition_view.css`.

### Member adatlap (`/member/competitions/view`)

| Képernyő | Layout |
|----------|--------|
| `lg+` (≥992px) | CSS grid: bal = meta + alatta inputok; jobb = **Leírás** (`grid-row: 1 / -1`, lehet magasabb) |
| mobil | flex order: meta → leírás → **inputok alul** |

CSS: `webroot/css/pages/competition_view.css`.

---

## Jelentkezés adatok (ebéd / pipa / megjegyzés)

| Ki | Határidő előtt | Határidő után |
|----|----------------|---------------|
| Tag | Szerkeszthet / visszavonhat | Csak nézhet (visszavonás tiltva) |
| Klubelnök | Szerkeszthet | Szerkeszthet (`/clubpresident/competition-applicants/edit`) |

Shared mezők: `element/competitions/application_fields.php`. Utility: `CompetitionApplication`.

---

## Rendező klub (kiírás)

Admin (`/admin/competitions`) és President (`/president/competitions`) add/edit:

1. Select2 **csak** azok a klubok, amelyek **nemzeti tagsága az idei évre rendezett** (`clubs.national_membership_fee_date` → `Clubs::optionsForCompetitionOrganizer` / `findSelectable` + `requireNationalFeePaid`).
2. Mentés ugyanazt ellenőrzi (`isAllowedCompetitionOrganizer`) — nem lehet „titokban” nem fizető klubot választani.
3. Szerkesztéskor a **jelenlegi** rendező klub a listán marad, ha a tagdíja időközben lejárt (hogy a meglévő kiírás menthető legyen).
4. Admin országváltáskor a klublista AJAX-szal frissül (`Users::clubsForCountry` — ugyanez a fee-szűrés).

**CounterCache:** `clubs.competition_count` = hány versenyt rendez a klub (`Competitions.club_id`). Klub törlés tiltott, ha `user_count + competition_count > 0`.

---

## Jelentkezés → alcsapat (szabály)

1. Tag meglátja a versenyt (saját ország, `visible`, még nem zárult).
2. Tag **csak akkor** jelentkezhet, ha a **klub tagdíja az idei évre rendezett** (`users.club_membership_fee_date` → `CompetitionApplication::memberMayApply` / `MembershipFee::isClubFeeUnpaid`). Ellenkező esetben Apply tiltott (lista/view + POST `apply` Flash).
3. Tag jelentkezik (SWAL) → `competitions_users` pending → redirect a versenylistára.
4. Tag visszavonhat (lista / **adatlap**, SWAL) → **sor törlés** + redirect a versenylistára; CounterCache frissül. Adatlapon: rejtett `#form-withdraw-application` + gomb `form="form-withdraw-application"` (nem a szerkesztő `#form-horizontal`) — **nincs** „Save changes?” kérdés.
5. **Nincs jelentkezési rekord** (még nem jelentkezett / már visszavonta) → **nincs** Withdraw gomb; Apply / details (ha tagdíj OK).
6. Klubelnök alcsapatot rendel → `competition_club_id` + `assigned` → hivatalos.
7. Klubelnök törölheti a jelentkezési sort (számlálók ugyanúgy frissülnek).

**Hivatalos** = `CompetitionApplication::isRegistered()` (`assigned` + van `competition_club_id`).  
**Aktív jelentkezés** = `CompetitionApplication::hasApplication()` (`pending`/`assigned`; withdrawn/invalid / hiányzó sor = nincs).

**CounterCache (CakePHP 5):** legacy `'sum'` **tilos** — SUM closure; `user_count` = assigned; `attendant_count` = active apps; pipe SUM → `national_pipe_club_member_count`. Rebuild: `bin/cake rebuild_counter_caches`. Spec: [counter-caches.md](counter-caches.md).

**Lista contain:** `CompetitionsClubs` → `Subclubs` mindig **LEFT** join — pending (`competition_club_id` NULL) soroknál az INNER kiszűrné a jelentkezőt.

**Kanban** (húzós alcsapat-besorolás): tervezett / megbeszélt — **nincs implementálva**, amíg a user nem kéri.

---

## Jelentkezési ablak

- Nyitva: `first_date_of_application` ≤ ma ≤ `application_deadline`
- Lezárt: Apply secondary/disabled; view OK
- Dashboard / lista: `end_datetime` NULL vagy ≥ most
- Archívum: volt `competitions_users` + `end_datetime` < most; `result_*` mezők

---

## Agent checklist (új verseny-funkció)

- [ ] Séma / FK illeszkedik (`doc/competitions.md` táblák)
- [ ] PanelNav + sidebar/dashboard
- [ ] President view: min. létszám szűrés a tabokon
- [ ] Pending contain: Subclubs LEFT
- [ ] CounterCache + Flash mentés hibák (`flashEntityErrors`)
- [ ] i18n msgid + `.po`; `doc/valtozasok.md`

## Séma migration

`config/Migrations/20260808180000_AlterCompetitionsSchema.php` (+ későbbi drop migrációk); `config/schema/competitions.sql`.
