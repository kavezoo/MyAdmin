# Admin — teljes CRUD + view gyerek TAB-ok

**Örök szabály:** az Admin panelen **minden domain tábla** teljes értékű CRUD; minden **view** oldalon a hasMany/HABTM gyerekek **`view_related_tabs`** alatt, modalos CRUD linkekkel.

Cursor rule: **`admin-view-related-tabs.mdc`** (alwaysApply).  
UI minta: [admin-konvenciok.md](admin-konvenciok.md) „Kapcsolt gyerek táblák”; playbook: [uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md).

---

## 1. Miért külön a President / Clubpresident?

| Prefix | Szerep |
|--------|--------|
| **Admin** | Globális / országok feletti **adatkezelés** — teljes CRUD minden táblára |
| **President** | Ország-scope üzleti UI (tagdíj, klubelnök assign, versenykiírás) |
| **Clubpresident** | Klub-scope (tagok, alcsapatok, jelentkezők) |

A panel CRUD **nem** váltja ki az Admin CRUD-ot. Admin user a `/admin` menüből menedzsel.

---

## 2. Kötelező Admin modulok

| Modul | Controller | Tábla | View gyerek TAB-ok |
|-------|------------|-------|-------------------|
| Languages | `Admin\Languages` | `languages` | — |
| Countries | `Admin\Countries` | `countries` | Users, Setups, Counties, Cities, Clubs (+ opcionális VisibleCountries) |
| Counties | `Admin\Counties` | `counties` | **Cities** |
| Cities | `Admin\Cities` | `cities` | **Clubs** |
| Setups | `Admin\Setups` | `setups` | — |
| Event logs | `Admin\EventLogs` | `event_logs` | — (read-only) |
| Users | `Admin\Users` (vagy Members) | `users` | Club (belongsTo link); applications ha van |
| Clubs | `Admin\Clubs` | `clubs` | **Users** (Members); Competitions ha asszoc. |
| Competitions | `Admin\Competitions` | `competitions` | Sub-teams, Applicants (min. létszám szabály: [competitions.md](competitions.md)) |
| Email templates | `Admin\EmailTemplates` | `email_templates` | — |
| Competition teams | nested vagy `Admin\CompetitionTeams` | `competitions_clubs` | Applicants |
| Continents | opcionális | `continents` | Countries |

`PanelNav::admin()` + sidebar: **domain** modulok top-level (`navGroup=main`); ref/geo/setups/logs → **Settings** (`navGroup=settings`).  
`config/admin_search.php` — szöveges mezők minden Table-re.

---

## 3. View related tabs — kötelező checklist

```
[ ] Table hasMany / HABTM felmérve
[ ] view() contain + ASC név
[ ] element admin/view_related_tabs (üres is)
[ ] related-records-table + data-get/edit/view/delete = gyerek CRUD
[ ] record-modal-link a néven
[ ] modal_linked_record_view a layoutban
[ ] pages/index JS
```

**Példa minta:** `templates/President/Clubs/view.php`, `templates/Admin/Countries/view.php`.

**Tilos:** inline gyerektábla a fő card-ban related tabs helyett (kivéve ha a user explicit mást kér).

---

## 4. Gap lista (ellenőrző — tartsd naprakészen)

| View | Elvárt TAB | Állapot |
|------|------------|---------|
| Admin/Countries | Users, Setups, Counties, Cities, Clubs | **kész** (modal CRUD; Users → Admin Users) |
| Admin/Counties | Cities | **kész** |
| Admin/Cities | Clubs | **kész** |
| Admin/Clubs | Users (Members) | **kész** |
| Admin/Users | Competition applications | **kész** (tab + `applicationRecordGet`; teljes nested CRUD: Admin CompetitionApplicants) |
| Admin/Competitions | Sub-teams, Applicants | **kész** |
| Clubpresident/CompetitionTeams | Applicants / Team members | **kész** (`view_related_tabs` + modal + unassign) |
| President/Competitions | Sub-teams, Applicants | OK |
| President/Clubs | Members | OK |

---

## 5. Agent playbook — új tábla

1. Migráció + Table + Entity + associations  
2. **Admin** Controller teljes CRUD + templates (index/form/view)  
3. view: related tabs minden gyerekre  
4. `PanelNav::admin()` + `admin_search.php`  
5. Permissions Admin prefix  
6. `doc/valtozasok.md` + ez a fájl gap tábla  

Greenfield: [uj-projekt.md](uj-projekt.md) + [crud-utmutato.md](crud-utmutato.md).
