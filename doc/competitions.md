# Versenyek (competitions)

President kiírja → tag jelentkezik → klubelnök alcsapatot rendel → hivatalos jelentkezett.

**Cursor rule:** `.cursor/rules/competitions.mdc` (glob: Competition*).  
**Kapcsolódó:** [membership.md](membership.md) (panelek / PanelNav), [users-auth.md](users-auth.md).

---

## Táblák

| Tábla | Szerep |
|-------|--------|
| `competitions` | Versenykiírás (ország + rendező klub + dátumok + `minimum_team_size` + opcionális `racing_pipe_N_title` + **pipa/dohány**: `pipe_type`, `pipe_parameters`, `tobacco_type`, `tobacco_weight` g). **Ebéd:** `lunch_description` (Translate) + `lunch_price`. **Pipa fotók:** `racing_pipe_N_image` (upload). **Szöveges mezők** = Cake Translate EAV (`i18n`). Összesítők nem formmezők. |
| `competition_text_templates` | Ország-scope, többnyelvű **leírás** (HTML) sablonok. Translate: csak `description`; `label`, `enabled`, `visible`, `pos` metaadat. Név/cím/… a versenyen. Placeholders: `{{lunch_description}}`, `{{lunch_price}}`, `{{racing_pipe_N_image}}`, … |
| `competitions_clubs` | Alcsapat indulás (klub + verseny); **nincs `name`** — név = `subclubs` (`subclub_id`); `user_count` = **besorolt** tagok |
| `competitions_users` | Tag jelentkezés; **`companion_count`**, `lunch_for_the_attendant`; **`fee_paid_at`** + **`fee_paid_by`** (check-in user); díj snapshot: `entry_fee_amount`, `racing_pipe_N_fee`, **`lunch_fee`**, **`fee_total`**; **`result_time`** + **`result_recorded_by_email`** (judge / API / `/judge/close`) |
| `competition_staff` | Versenynapi személyzet: `checkin` \| `judge` (`user_id` + `competition_id`) — **nem** `Users.role` |
| `subclubs` | Alcsapat **név** rekord (`{klub short_name} {n}`); számláló **versenyenként** (új verseny → 1-től); kötelező FK a `competitions_clubs.subclub_id`-n |

### Díjszámítás (nevezés + pipa + ebéd)

`App\Utility\CompetitionFees` — két ársáv a versenyen:

| Ársáv | Mezők | Mikor |
|-------|-------|-------|
| **Nemzeti tag** (olcsóbb) | `entry_fee_member`, `racing_pipe_N_price_member` | `users.national_membership_fee_date` az **idei évre** rendezett (`MembershipFee::isPaidForYear`) |
| **Nem nemzeti tag** (drágább) | `entry_fee_non_member`, `racing_pipe_N_price_non_member` | országos díj nincs / más év |
| Ebéd | `lunch_price` × `lunch_for_the_attendant` | nincs tag/nem-tag különbség |

**Megjelenítés:** `CompetitionFees::format()` — **0 tizedes** (nincs fillér). Check-in / nem fizetett sor: élő számítás + snapshot mentés. Fizetés után: snapshot (hiányzó ebéd gyógyítható). Form: tag ár **ne** legyen magasabb a nem-tag árnál (validáció).

---

## Panelek

| Prefix | URL | Funkció |
|--------|-----|---------|
| President | `/president/competitions` | CRUD kiírás (ország-scope); **index alapból csak az idei év** (`competition_datetime`); switch: **Show all competitions**; **view**: related tabs = min. létszámot elérő alcsapatok + azok assigned jelentkezői |
| President | `/president/competition-staff` | **Külön lista** + manage: check-in / judge kijelölés (AJAX névkeresés); **nem** a verseny view alatt |
| President | `/president/competition-text-templates` | Ország-scope sablon CRUD; a `country_id` mindig a tisztségviselő országa |
| Clubpresident | `/clubpresident/competition-teams` | **Teljes CRUD** alcsapat; **országválasztó** + aktív versenyek; versenyoszlop **sortörhető** (`min-width`, nem fix/nowrap); oszlopcímkék Bootstrap **tooltip** (létszám `#` → Members, Min. → Min. team size); státusz ikon tooltip; törlés ha `user_count = 0`; verseny linked modal |
| Clubpresident | `/clubpresident/competition-staff` | Saját klub versenyeihez staff kijelölés (AJAX névkeresés) |
| Clubpresident | `/clubpresident/competition-applicants` | Jelentkezők → alcsapat besorolás **vagy törlés** / edit; **országválasztó** + csak **aktív** versenyek az adott országban |
| Clubpresident | `/clubpresident` (Dashboard) | Alert a vezérlőpult **fölött**: pending verseny-jelentkezők (`status=pending`, aktív verseny) + gomb → Competition applicants |
| Member | `/member` + `/member/competitions` | Dashboard + lista: **aktív** versenyek; alapból saját ország; **országválasztó** → más ország versenyeire is lehet jelentkezni; visszavonás / archívum; jelentkezéskor **kísérők** + **plusz ebéd**; view: személyzet a név/alcím alatt (check-in → asztalbírók; President/Admin: név → linked modal) |
| Check-in | `/checkin` → `/checkin/applicants` | **Csak versenynap** + `competition_staff` checkin; listázás = `deskCompetitionIds`; `markPaid` tiltva a nap után; Role switch-ben is csak ma |
| Check-in | `/checkin/cash` | Kassza: fizetések **check-in gyűjtő szerint** csoportosítva; „Should have in till” részösszeg + Grand total |
| Judge | `/judge` | Csak **versenynapon** + `competition_staff` judge; időeredmény (`result_time`); Flutter: session Api **és** nyilvános `POST /judge/close/{128}` — [competition-results-api.md](competition-results-api.md) |

**Staff kijelölés:** President/VP → `/president/competition-staff`; Clubpresident → `/clubpresident/competition-staff`. Hozzáférés = **csak** `competition_staff` sor + a verseny **naptári napja** (ország timezone, teljes nap). **Admin/officer sem** kap automatikus check-in/judge panelt kijelölés + versenynap nélkül (`PanelAccess`).

**PanelNav:** minden fenti cél a `PanelNav`-ban is (dashboard kártya + sidebar) — rule `panel-nav-conventions.mdc`.

### Versenykiírás-sablonok

Admin és President teljes CRUD-ot kapott a `competition_text_templates` táblához. Az Admin ország-scope szabályt és superuser országválasztót használ, a President minden lekérdezése és mentése a tisztségviselő országára korlátozott. A form csak a **description** HTML-t szerkeszti (Summernote + nyelvi TAB); a név/cím mezők a verseny kiíráson vannak.

Az `applyData/{id}` GET JSON csak a **description** mezőt adja vissza locale-onként; a név/cím/… mezőket a verseny formon kell megadni. A `{{placeholder}}` tokenek a leírás HTML-ben maradnak (szerkesztőben félkövér beszúrással), és csak a verseny megjelenítésekor oldódnak fel.

### President lista (`/president/competitions`)

| Alap | Switch `Show all competitions` (`?show_all=1`) |
|------|------------------------------------------------|
| Csak az **idei naptári év** versenyei (`competition_datetime` év = `MembershipFee::currentYear()`) | Minden év |

Session: `President.Competitions.show_all` (mint Countries `visible_only` / Members fee filter). A param **nincs** az index URL state kulcsai között — `resolveShowAllCompetitions()` **előbb**, mint `applyIndexListState`.

Lista oszlopok (a meglévők mellett): **Applicants** (`user_count`), **Applying clubs** (distinct `competitions_clubs.club_id`), **Sub-teams** (`subclubs.name` lista, contain).

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
5. **Új kiírás (add):** `competition_datetime` alapértelmezés = mai nap **14:00:00** (`DateTime::now()->setTime(14, 0, 0)`).
6. **`start_datetime` / `end_datetime`:** **nincs** a kiírás formon (add/edit) — máshonnan kap értéket (pl. versenyóra / operátor); a mezők a DB-ben / view-n megmaradnak; FORM_FIELDS-ből kizárva.
7. **Nyelvi TAB-ok (Admin + President add/edit):** szöveges mezők Translate EAV — `name`, `title`, `subtitle`, `subtitle2`, `description`, `racing_pipe_1_title`…`3`, **`pipe_type`**, **`pipe_parameters`**, **`tobacco_type`**. Nem szöveges (klub, dátumok, national, min. size, **`tobacco_weight`**, visible, pos) a TAB-okon kívül. Markup: `element/competitions/form_i18n_tabs`. Controller: `setFormLanguageTabs($countryId)` + edit `getWithTranslations(..., $countryId)` + mentés `setFormTranslateLocale`. **`i18n.foreign_key` = varchar(36)** (UUID PK). Form root = EAV `defaultLocale` (`en_GB`).
8. **Leírás HTML:** **Summernote** ([JeffAdmin5](https://packagist.org/packages/zsfoto/jeffadmin5) `jeffAdminInitSummerNote`: height 400 / leírás TAB 520, `hu-HU`, `codeviewFilter: false`). Form: külön **Description** lapfül — **teljes szélesség** (`form_i18n_tabs` `fullWidth => true`, JeffAdmin5 `col-sm-12` text pane). View: `admin/html_content` + `CompetitionTextRender`.
9. **Megjelenítés (minden prefix):** lista / view / modal / Select2 — verseny szöveg = **login / UI locale** (`AdminCountry::applyTranslateLocale` → Competitions; contain előtt `AdminTranslate::applyLocale`). Clubpresident alcsapatok + jelentkezők is. **Üres i18n sor tilos** (`allowEmptyTranslations = false` + mentéskor `scrubEmptyTranslations`) — különben a főtábla (en_GB) szöveg eltűnik a listáról.
10. **Audit a listán:** `user_id` = létrehozó; `modified_by` = utolsó szerkesztő (create-kor = `user_id`). Index: Created + létrehozó név; Modified + módosító név.
11. **Szövegsablon:** a **Description** fülön Select2 → csak a **description** HTML-t tölti (`applyData`); név/cím/… egyedileg a Basic data-n. Másik sablonra váltáskor SweetAlert. Sablon CRUD: csak `description` Translate mező (+ label/meta); placeholder chip → `<strong>{{token}}</strong>`.
12. **Helyszín:** `city_id` (AJAX `cityOptions`, címke = név + ZIP), **`venue_name`** (épület/helyszín neve), `venue_address` (kézi), `google_maps_url`. Soft FK: `city_id = 0` = nincs. Maps URL → embed: `CompetitionTextRender::googleMapsToEmbedUrl` (koordináta `!3d!4d` / `@lat,lng`, place név, iframe src, short link redirect) — **tilos** a teljes share URL-t `q=`-ba tenni.
13. **`{{placeholders}}` / `{placeholders}` megjelenítés:** DB-ben tokenek maradnak; view-n `CompetitionTextRender` (élő klub/város/dátum…, **UI locale** formátum). Üres érték → token **megmarad**. `src`/`href` attribútumban `{{club_logo}}` / `{{national_association_logo}}` / `{{racing_pipe_N_image}}` → **URL**; önálló token → HTML `<img>` blokk. `{{map}}` → térkép keret; chip → félkövér beszúrás.

**CounterCache:** `clubs.competition_count` = hány versenyt rendez a klub (`Competitions.club_id`). Klub törlés tiltott, ha `user_count + competition_count > 0`.

---

## Szövegsablon + helyőrzők

| Elem | Hol |
|------|-----|
| CRUD | `/admin/competition-text-templates`, `/president/competition-text-templates` |
| Utility | `App\Utility\CompetitionTextRender` |
| Tokenek | Description fül mellett kattintható chip-ek: `{{national_association_logo}}`, `{{club_logo}}` (PNG átlátszóság), `{{venue_name}}`, `{{pipe_type}}`… → Summernote kurzor |

A sablon HTML-ben tedd a `{{map}}` oda, ahol a térképnek kell megjelennie; a `{{club_logo}}` a rendező klubhoz; a `{{national_association_logo}}` az országos pipa egyesület logójához (Countries feltöltés).
---

## Jelentkezés → alcsapat (szabály)

1. Tag meglátja a versenyt: **aktív** (`visible` + `end_datetime` NULL vagy ≥ most) a **választott browse országban** (alap: saját `users.country_id`). Más ország: Select2 (`element/panel/competition_browse_country` + `CompetitionBrowse`).
2. Tag **csak akkor** jelentkezhet, ha a **klub tagdíja az idei évre rendezett** (`users.club_membership_fee_date` → `CompetitionApplication::memberMayApply` / `MembershipFee::isClubFeeUnpaid`). Ellenkező esetben Apply tiltott (lista/view + POST `apply` Flash).
3. Tag jelentkezhet **más ország** versenyére is (nincs ország-lock az `apply` / `view` actionön) — a verseny legyen `visible` + aktív + nyitott jelentkezési ablak.
4. Tag jelentkezik (SWAL) → `competitions_users` pending → redirect a versenylistára.
5. Tag visszavonhat (lista / **adatlap**, SWAL) → **sor törlés** + redirect a versenylistára; CounterCache frissül. Adatlapon: rejtett `#form-withdraw-application` + gomb `form="form-withdraw-application"` (nem a szerkesztő `#form-horizontal`) — **nincs** „Save changes?” kérdés.
6. **Nincs jelentkezési rekord** (még nem jelentkezett / már visszavonta) → **nincs** Withdraw gomb; Apply / details (ha tagdíj OK).
7. Klubelnök alcsapatot rendel → `competition_club_id` + `assigned` → hivatalos. Klubelnök **országválasztóval** szűr (saját / külföldi verseny); jelentkezők és alcsapatok listája: csak **aktív** versenyek. **Dashboard alert** (vezérlőpult fölött): ha van `pending` jelentkező aktív versenyen → üzenet + **Assign to teams** gomb → `/clubpresident/competition-applicants`.
8. Klubelnök törölheti a jelentkezési sort (számlálók ugyanúgy frissülnek).

**Browse ország:** `App\Utility\CompetitionBrowse` — session kulcsok: `Member.Competitions.browseCountryId`, `Clubpresident.CompetitionApplicants.browseCountryId`, `Clubpresident.CompetitionTeams.browseCountryId`. Query: `?country_id=`. Aktív WHERE: `CompetitionBrowse::activeConditions()`.

**Hivatalos** = `CompetitionApplication::isRegistered()` (`assigned` + van `competition_club_id`).  
**Aktív jelentkezés** = `CompetitionApplication::hasApplication()` (`pending`/`assigned`; withdrawn/invalid / hiányzó sor = nincs).

**CounterCache (CakePHP 5):** legacy `'sum'` **tilos** — SUM closure; `user_count` = assigned; `attendant_count` = active apps; pipe SUM → `national_pipe_club_member_count`. Rebuild: `bin/cake rebuild_counter_caches`. Spec: [counter-caches.md](counter-caches.md).

**Lista contain:** `CompetitionsClubs` → `Subclubs` mindig **LEFT** join — pending (`competition_club_id` NULL) soroknál az INNER kiszűrné a jelentkezőt.

**Kanban** (húzós alcsapat-besorolás): tervezett / megbeszélt — **nincs implementálva**, amíg a user nem kéri.

---

## Jelentkezési ablak

- Nyitva: `first_date_of_application` ≤ ma ≤ `application_deadline`
- Lezárt: Apply secondary/disabled; view OK
- **Kiírás tartalom zárolva:** `application_deadline` utáni naptól (Admin + President edit) — mezők `disabled` / Summernote disable, nincs Save; POST mentés elutasítva (`CompetitionApplication::isContentLocked`). A határidő napján még szerkeszthető.
- Dashboard / lista / jelentkezők / alcsapatok (Member + Clubpresident browse): `visible` + (`end_datetime` NULL vagy ≥ most) — `CompetitionBrowse::activeConditions()`
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
