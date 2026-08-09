# Verseny időeredmény API (Flutter / asztalbíró)

Asztalbíró mobil app (Flutter) QR-kóddal olvassa a versenyző lapját, majd **POST**-tal elküldi az időeredményt. A folyamatosan frissülő kiíró képernyő **későbbi** feladat.

Kapcsolódó: [competitions.md](competitions.md), [users-auth.md](users-auth.md) (staff napablak).

---

## Jogosultság

| Ki / endpoint | Mit |
|---------------|-----|
| **President / vicepresident** | `/president` verseny view → Check-in / Judge kijelölés (`competition_staff`) |
| Clubpresident | opcionális: saját klub versenyeinél (`/clubpresident/competition-staff`) |
| Check-in / Judge **panel** | csak a **verseny naptári napján** + `competition_staff` |
| `POST /api/competitions/results/…` | bejelentkezett user + `judge` megbízás **az adott** versenyre + staff nap |
| `POST /judge/close/{pairToken}` | **nincs session** — a 128 char token a titok; body: `email` + idő |

A megbízás **nem** változtatja a `Users.role`-t.

---

## UUID token (QR)

Kanonikus UUID: `8-4-4-4-12` (36 karakter, 4 db `-`).

**Obfuscation:** minden `-` helyére **8 véletlen** `[A-Za-z0-9]` karakter → **64 karakteres** token.

```
plain:  a1b2c3d4-e5f6-7890-abcd-ef1234567890
token:  a1b2c3d4XXXXXXXX e5f6 XXXXXXXX 7890 XXXXXXXX abcd XXXXXXXX ef1234567890
        (szóközök csak olvashatósághoz — a token folytonos 64 char)
```

Utility: `App\Utility\UuidObfuscator` (`encode` / `decode` / `encodePair` / `decodePair`).

- Két külön token: `competitionToken` + `userToken` (versenyző `users.id`) — Api URL.
- **Egy összetett token:** `encodePair(competitionId, userId)` → **128 karakter** (64+64) — Judge close URL.

---

## Endpoint A — session + két token

```
POST /api/competitions/results/{competitionToken}/{userToken}
Content-Type: application/json
Cookie: (session a bejelentkezett asztalbíróhoz — JWT/token auth később)
```

CSRF: az `Api` prefix **kihagyva** (`Application` middleware).

### Body (egyik időmező elég)

| Mező | Példa | Jelentés |
|------|-------|----------|
| `time_seconds` | `754.567` | másodperc (decimal, 3 tized) |
| `time_ms` | `754567` | milliszekundum → /1000 |
| `time` / `result_time` | `"12:34.567"` vagy `"1:02:03.100"` | formázott idő |
| `email` (opcionális) | `judge@example.com` | rögzítő (írja `result_recorded_by_email`) |

### Sikeres válasz `200`

```json
{
  "success": true,
  "message": "Result time saved.",
  "competition_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "user_id": "…",
  "application_id": 123,
  "result_time": 754.567,
  "result_time_formatted": "12:34.567",
  "competition_ended": false
}
```

`competition_ended: true` → minden **assigned** versenyzőnek van `result_time`, és `competitions.end_datetime` most lett beállítva.

### Hibák

| HTTP | Mikor |
|------|--------|
| 401 | nincs session / identity |
| 400 | token nem fejthető vissza |
| 403 | nem judge / nem versenynap |
| 404 | nincs aktív `competitions_users` sor |
| 422 | hiányzó / hibás idő |
| 500 | mentés hiba |

---

## Endpoint B — bárhonnan, egy összetett token (`/judge/close`)

Asztali / külső eszköz: nincs bejelentkezés. A URL titkosítja a verseny + versenyző UUID-t.

```
POST /judge/close/{pairToken}
Content-Type: application/json
```

`pairToken` = `UuidObfuscator::encodePair($competitionId, $userId)` — **pontosan 128** `[A-Za-z0-9]` karakter.

CSRF: `Judge/Close` és `/judge/close/` **kihagyva**. Auth: `bypassAuth` (permissions).

### Body (kötelező)

| Mező | Kötelező | Jelentés |
|------|----------|----------|
| `email` / `recorded_by_email` | igen | ki rögzítette (érvényes email) |
| `time_seconds` / `time_ms` / `time` | igen (egyik) | elért idő (ugyanaz, mint Endpoint A) |

### Példa

```http
POST /judge/close/a1b2c3d4…(64)…e5f6…(64 char user token)
Content-Type: application/json

{"email":"table.judge@club.hu","time_seconds":754.567}
```

### Sikeres válasz `200`

```json
{
  "success": true,
  "message": "Result time saved. Competitor participation closed.",
  "competition_id": "…",
  "user_id": "…",
  "application_id": 123,
  "result_time": 754.567,
  "result_time_formatted": "12:34.567",
  "recorded_by_email": "table.judge@club.hu",
  "competition_ended": false
}
```

Ha minden assigned versenyzőnek megvan az idő: `competition_ended: true` + üzenet, hogy a verseny véget ért (`end_datetime`).

### Hibák

| HTTP | Mikor |
|------|--------|
| 400 | 128 char token hibás / nem dekódolható |
| 404 | nincs aktív jelentkezés |
| 422 | hiányzó/hibás email vagy idő |
| 500 | mentés hiba |

---

## Tárolás

| Mező | Szerep |
|------|--------|
| `competitions_users.result_time` | `DECIMAL(12,3)` **másodperc** — lezárás jele |
| `competitions_users.result_recorded_by_email` | ki rögzítette (close / opcionális Api) |
| `competitions.end_datetime` | ha minden **assigned** soron van `result_time` és még üres volt → `now()` |

Web UI: `/judge/applicants` (ugyanaz a mentés + automatikus verseny-vége).

PHP: `App\Utility\CompetitionResults::saveTimeForApplicant()` / `maybeEndCompetitionIfAllResultsIn()`.

---

## Flutter / eszköz checklist

1. **Sessiones út:** login → QR két 64 char token → `POST /api/…/results/…`.
2. **Nyilvános close:** QR egy 128 char pair token → `POST /judge/close/{token}` + `email` + idő.
3. Token generálás szerveren: `UuidObfuscator::encode` / `encodePair` (QR nyomtatáshoz).
4. `success === true` → UI; `competition_ended` → verseny lezárva.
5. Élő kiíró képernyő — **még nincs**.
