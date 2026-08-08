# CounterCache / `*_count` mezők

**Örök döntés:** minden séma `*_count` / összegző számláló, ahol lehetséges, a **CakePHP `CounterCache` behavior**-rel frissül a **gyerek** (vagy HABTM **through**) Table-en.  
Minden prefix ORM `save()` / `delete()` — **ne** élő `COUNT(*)`, **ne** `updateAll` a gyereken.

**Cursor rule (alwaysApply):** `.cursor/rules/counter-caches.mdc`  
**UI / Delete:** [admin-konvenciok.md](admin-konvenciok.md) → CounterCache  
**Greenfield:** [uj-projekt-sema-playbook.md](uj-projekt-sema-playbook.md) · [minta-tanulsagok.md](minta-tanulsagok.md) §2

---

## Szabályok

1. Gyerek rekord **ORM** `Table::save()` / `delete()` — soha ne `updateAll` / `deleteAll` / nyers SQL a gyereken, ha van szülő számláló (kivéve rebuild).
2. Új `*_count` oszlop checklist:
   - séma `DEFAULT 0` + COMMENT `CounterCache: …`
   - CounterCache a **gyerek** Table `initialize()`-ben
   - soft FK esetén closure → `false` ha `< 1`
   - `App\Utility\CounterCaches::rebuildAll()` lépés
   - `doc/counter-caches.md` térkép + `counter-caches.mdc` tábla
   - `bin/cake rebuild_counter_caches`
3. CakePHP 5: **nincs** legacy `'sum'` kulcs — SUM / feltételes számláló → **closure** (`SelectQuery` vagy `int`; `false` = skip).
4. Soft FK `0` / üres (`Users.club_id`, `Users.country_id`, `Clubs.city_id`, `Cities.county_id`): ne frissítsen `id=0` sort.
5. Törlésvédelem: CounterCache oszlop (`PreventsDeleteWithChildrenTrait` / `canDelete`) — **ne** élő COUNT, kivéve dokumentált kivétel.

---

## Térkép (gyerek Table → szülő mező)

| Oszlop | Gyerek Table | Megjegyzés |
|--------|--------------|------------|
| `countries.user_count` | `Users` | soft `country_id` → skip ha `< 1` |
| `countries.club_count` | `Clubs` | |
| `countries.setup_count` | `Setups` | ország törlésvédelem |
| `clubs.user_count` | `Users` | soft `club_id=0` → skip |
| `clubs.competition_count` | `Competitions` | rendező klub |
| `cities.club_count` | `Clubs` | soft `city_id=0` → skip |
| `counties.city_count` | `Cities` | soft `county_id=0` → skip |
| `competitions.user_count` | `CompetitionsUsers` | **assigned** + van alcsapat |
| `competitions.attendant_count` | `CompetitionsUsers` | **active** (pending+assigned) |
| `competitions.lunch_for_the_attendant` | `CompetitionsUsers` | SUM lunch (active), closure |
| `competitions.national_pipe_club_member_count` | `CompetitionsUsers` | SUM pipe qty (active), closure |
| `competitions_clubs.user_count` | `CompetitionsUsers` | **assigned** az alcsapatra |

### Törlésvédelem (CounterCache mezők)

| Entitás | Feltétel |
|---------|----------|
| Country | `user_count` + `club_count` + `setup_count` mind 0 |
| Club | `user_count` + `competition_count` mind 0 |
| City / County / Team | `PreventsDeleteWithChildrenTrait` + a tábla `*_count` mezője |
| Competition | **kivétel:** bármely `competitions_users` sor (élő COUNT) — nem csak assigned `user_count` |

---

## Soft FK minta

```php
$this->addBehavior('CounterCache', [
    'Clubs' => [
        'user_count' => function ($event, $entity, $table, $original) {
            $clubId = (int)($original
                ? ($entity->getOriginal('club_id') ?? 0)
                : ($entity->get('club_id') ?? 0));
            if ($clubId < 1) {
                return false;
            }

            return $table->find()->where(['Users.club_id' => $clubId])->count();
        },
    ],
]);
```

Egyszerű FK (mindig érvényes szülő):

```php
$this->addBehavior('CounterCache', [
    'Countries' => ['club_count'],
]);
```

Feltételes / SUM (verseny jelentkezők): lásd `CompetitionsUsersTable` + `CounterCaches::competitionLunchSumQuery()` / `competitionPipeSumQuery()`.

---

## Rebuild

```bash
bin/cake rebuild_counter_caches
```

Implementáció: `App\Utility\CounterCaches::rebuildAll()` + `RebuildCounterCachesCommand`.  
Verseny összegzők (closure mezők): `CounterCaches::rebuildCompetitionCounters()` — a Cake `updateCounterCache()` ezeket nem mindig fedi.

Import / migráció után futtasd, ha a számlálók elcsúsztak.

---

## Greenfield checklist (új `*_count`)

- [ ] Séma oszlop `DEFAULT 0` + COMMENT
- [ ] Gyerek Table `addBehavior('CounterCache', …)`
- [ ] Soft FK → closure `false`
- [ ] Szülő törlésvédelem / index oszlop ha kell
- [ ] `CounterCaches::rebuildAll()` + command lépés
- [ ] `doc/counter-caches.md` + `counter-caches.mdc` + `doc/valtozasok.md`
