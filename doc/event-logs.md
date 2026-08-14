# Eseménynapló / tevékenységnapló (`event_logs`)

Append-only audit: belépés, kilépés, adatváltozás (CRUD). **Oldalnézet / böngészés nem naplózódik.**

Kapcsolódó: [users-auth.md](users-auth.md), [setups.md](setups.md), [admin-konvenciok.md](admin-konvenciok.md).  
Schema: [`config/schema/event_logs.sql`](../config/schema/event_logs.sql).

## Setups (országonként)

| Slug | Típus | Alap | Jelentés |
|------|-------|------|----------|
| `activity_logging_enabled` | boolean | `1` | Új sorok írása `event_logs`-ba (login/logout + CRUD) |
| `users_activity_log_visible` | boolean | `1` | User megnyithatja a saját tevékenységlistát (`/users/event-log`) |

Olvasás: `ActivityLogSetup::isLoggingEnabled()` / `usersCanViewOwn()`.  
Seed: `php tmp/seed_activity_log_setups.php`.

## Jogok

| Ki | Mit lát |
|----|---------|
| Bejelentkezett user | **Saját tevékenység** — ha `users_activity_log_visible` (`/users/event-log`) |
| `president`, `vicepresident`, `admin`, `superuser` | Ország szerinti böngészés (`/admin/event-logs`) + kereső |
| `superuser` | Ország szűrő (bármely master-visible ország) |
| Többi officer | Csak a saját `Users.country_id` országa |

ACL: `App\Auth\EventLogAccess`.
`canSearch`: `superuser` / `admin` / `president` / `vicepresident` **vagy** CakeDC `is_superuser` flag (Admin sidebar / dashboard kártya).

## Mit naplóz

| Forrás | Action példák |
|--------|----------------|
| `EVENT_AFTER_LOGIN` / `EVENT_BEFORE_LOGOUT` | `login`, `logout` |
| `EventLogBehavior` (App Tables) | `add`, `edit`, `delete` + mező diff |
| Users tagdíj dátum | `club_membership_fee_date` / `national_membership_fee_date` → `MembershipFee::activityDescriptions` (ha `activity_logging_enabled`) |
| Clubs tagdíj dátum | `national_membership_fee_date` → `MembershipFee::clubEntityActivityDescriptions` (ha `activity_logging_enabled`) |

**Nem naplózott:** ha `activity_logging_enabled = 0`; oldalnézet / index / keresés; `Languages`, `I18n`, `EventLogs` táblák.

## Mező diff (from → to)

- `EventLogBehavior` mentéskor minden dirty mezőt `request_data.changes[field] = {from, to}` formában ír (jelszó/token: `[empty]` / `[set]` / `[changed]`).
- Translate mezők: `_translations` → `name:hu_HU` stb. kulcsok, locale szerinti régi/új szöveg.
- Megjelenítés: `EventLogValueResolver` (FK → név, bool → Igen/Nem, avatar → „kép beállítva” / „nincs kép`).
- User lista összefoglaló: `EventLogPresenter::activitySummary` — pl. „Név: Régi → Új; Telefon: … → …” (nem csak mezőnév lista).
- Admin index/view: `element/admin/event_log_changes` — emberi mezőcím + értékek.

## UI

- **User:** profil menü → **My activity** — tömör időrend (`activity_log.css`); összefoglaló sortöréssel
- **Officer:** sidebar → **Event logs** — kompakt tábla (`event_logs.css`): kis sormagasság, olvasható betű; **Event** oszlop = description + változások, `white-space: pre-wrap` / sortörés
- **Officer beállítások:** index és view fejléc alatt — **working country** (`AdminCountry::id`) Setups kapcsolók:
  - **Activity logging** — `toggleActivityLogging` (POST)
  - **Users see own activity** — `toggleUsersActivityView` (POST)
  - Element: `templates/element/admin/activity_log_setup_toggles.php`; jog: `SetupAccess::canEditValue`
- Részletező usernek: `templates/Users/activity_log_view.php` (nincs IP/URL/JSON)

## Kód

| Fájl | Szerep |
|------|--------|
| `ActivityLogSetup` | Setup slug-ok + gate |
| `EventLogPresenter` | User-facing címkék / összefoglaló |
| `EventLogValueResolver` | FK / bool / státusz → emberi érték a diffben |
| `EventLogger` | írás (+ logging gate); **`Connection::afterCommit`** + `atomic => false` (ne NestedTransactionRollbackException a domain `save()`-en) |
| `EventLogBehavior` | Table afterSave/Delete |
| `Admin\EventLogsController` | officer lista |
| `UsersController::eventLog` | saját lista |
