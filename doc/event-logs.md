# Eseménynapló (`event_logs`)

Append-only audit: belépés, kilépés, adatváltozás (CRUD). **Oldalnézet / böngészés nem naplózódik.**

Kapcsolódó: [users-auth.md](users-auth.md), [admin-konvenciok.md](admin-konvenciok.md).  
Schema: [`config/schema/event_logs.sql`](../config/schema/event_logs.sql).

## Jogok

| Ki | Mit lát |
|----|---------|
| Minden bejelentkezett user | **Saját** napló (`/users/event-log`) |
| `president`, `vicepresident`, `admin`, `superuser` | Ország szerinti böngészés (`/admin/event-logs`) + kereső |
| `superuser` | Ország szűrő (bármely master-visible ország) |
| Többi officer | Csak a saját `Users.country_id` (fallback: Admin working country) országa |

ACL: `App\Auth\EventLogAccess`.

## Mit naplóz

| Forrás | Action példák |
|--------|----------------|
| `EVENT_AFTER_LOGIN` / `EVENT_BEFORE_LOGOUT` | `login`, `logout` |
| `EventLogBehavior` (App Tables) | `add`, `edit`, `delete` + **mezőnként `from → to`** (secrets: `[set]` / `[changed]`) |

**Nem naplózott:** oldalnézet / index / keresés / HTTP böngészés; `Languages`, `I18n`, `EventLogs` táblák.

Jelszó / token mezők: soha nem plaintext — `EventLogger::isSecretField` + `[redacted]` / `[changed]`.  
Érzékeny audit mezők (role, email, enabled, …) a `description`-ben is kiemeltek.  
**Megjelenítés:** lista **Adatváltozások** oszlop (`mező: régi → új`); részletezőn kiemelt **Data changes** tábla.

## UI

- Admin / President sidebar → **Event logs**
- Profil menü → **My event log**
- Index: szöveges kereső + **user** (Select2 AJAX) + module/action filter (+ country superusernél)
- User AJAX: `GET /admin/event-logs/user-options?q=&country_id=&page=` (ország-szűrt)

## Kód

| Fájl | Szerep |
|------|--------|
| `EventLogger` | írás |
| `EventLogBehavior` | Table afterSave/Delete |
| `Admin\EventLogsController` | officer lista |
| `UsersController::eventLog` | saját lista |
