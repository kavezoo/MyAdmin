# Verseny kivetítő + óra — architektúra (döntések)

**Állapot (2026-08-06):** csak döntésnapló / terv. **Nincs implementáció.** Folytatás: következő munkanap.

Kapcsolódó terv: Cursor plan „Kivetítő versenyóra”. Versenyző-admin CRUD **folyamatban** (külön); a kivetítő nem vár rá.

---

## Cél

| Ablak | Szerep |
|-------|--------|
| Főképernyő (operátor) | Vezérlés: felkészülés, verseny start, kiesés; **egy gomb** → kivetítő ablak |
| Kivetítő (projektor) | Teljes képernyő scoreboard: nagy óra + létszámok + utolsó 5 kiesett |

Böngészőből **nem garantálható**, hogy az új ablak automatikusan a második monitoron nyíljon. Gyakorlat: gomb → `window.open` → egyszer áthúzás a kivetítőre → Fullscreen.

---

## Layout (kivetítő)

```
+-------------------------------+
|     NAGY ÓRA (felső 50%)      |
+--------+------------+---------+
| Bent   | Utolsó 5   | Kiesett |
|  25%   |    50%     |   25%   |
+--------+------------+---------+
```

Saját sötét scoreboard layout (nem Admin chrome).

---

## Óra állapotgép

1. **idle** — nincs indítva  
2. **prep** — `prep_started_at` + `prep_seconds` (alap: 300 s / 0–5 perc pipa) → visszaszámláló  
3. **running** — `started_at` → elapsed = now − started_at  
4. **finished** — `ended_at` (utolsó versenyző bejegyzése) → elapsed freeze  

Forrás: **szerver / DB timestamp** (vagy offline fallback helyi állapot). A kliens csak megjelenít.

---

## Sync: ~1 s polling (nem WebSocket)

| Döntés | Indok |
|--------|--------|
| **HTTP GET status ~1 s** | Egyszerű, elég az órához és létszámhoz |
| **Nem** WebSocket / SSE az első verzióban | Extra infra; 1 s késés láthatatlan az órán |

JSON vázlat: `server_time`, `phase`, `prep_started_at`, `started_at`, `ended_at`, `prep_seconds`, `active_count`, `eliminated_count`, `last_eliminated[]`.

---

## Biztonság vs UI

- **UI:** egy gomb nyitja a kivetítőt.  
- **Auth a kivetítőn:** **nem** session-ös admin URL; **display token** (`races.display_token` vagy signed `?t=`).  
- Display: **csak olvasás** (státusz + megjelenítés).  
- Start / stop / kiesés: **csak** bejelentkezett operátor a főablakon.  
- Token rotálható / visszavonható.

---

## Offline / nincs internet

| Réteg | Megoldás |
|-------|----------|
| **Üzemeltetés (ajánlott)** | CakePHP + DB **helyi LAN**-on (laptop a helyszínen). Operátor + kivetítő: `http://192.168.x.x/…`. **Internet nem kell.** |
| **Azonos gép, 2 monitor** | Ha a poll fail: **BroadcastChannel** + `localStorage` / IndexedDB a két ablak között; ugyanaz a JSON-struktúra. Szerver visszatérésekor flush DB-be. |
| **Később (nem MVP)** | Teljes PWA / Service Worker offline-first sync |

---

## Adat (javasolt, még nincs a sémában)

**`races`:** `name`, `prep_seconds`, `prep_started_at`, `started_at`, `ended_at`, `display_token`, …

**`race_competitors`** (vékony, amíg a versenyző-admin kész): `race_id`, név / későbbi `user_id`, `status` (active/eliminated), `eliminated_at`.

---

## URL vázlat

| Szerep | Útvonal | Auth |
|--------|---------|------|
| Operátor | panel prefix (pl. later `/president/races/…`) | session + role |
| Kivetítő oldal | `/display/races/{id}?t={token}` | token |
| Státusz JSON | `/display/races/{id}/status?t={token}` | token; 1 s poll |

---

## Expliciten nem az első körben

- WebSocket / SSE  
- Electron / natív monitorválasztás  
- Teljes PWA offline-first  
- Teljes versenyző CRUD (máshol folyamatban)  
- Vezérlés a kivetítő ablakról  

---

## Következő lépés (implementáció)

1. Migráció `races` (+ vékony `race_competitors`)  
2. Display layout + JS óra + 1 s poll  
3. Operátor UI: kivetítő gomb, prep, start, stub kiesés  
4. Tokenes display route-ok  
5. Offline: BroadcastChannel fallback + LAN üzemeltetés a doksiban  

Frissítéskor: ez a fájl + `valtozasok.md`.
