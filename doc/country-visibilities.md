# Országonkénti plusz nyelvek (`country_visibilities`)

Kapcsolódó: [countries-admin.md](countries-admin.md), [form-i18n-tabs.md](form-i18n-tabs.md).  
Schema: [`config/schema/country_visibilities.sql`](../config/schema/country_visibilities.sql).  
Seed: `php tmp/seed_country_visibilities.php` (TRUNCATE + minden ország ↔ önmaga)

## Cél

| Kontextus | Honnan jön |
|-----------|------------|
| Form nyelvi TAB-ok | Aktív `Users.country_id` → junction: **saját ország mindig** + opcionális plusz nyelvek |
| Login / regisztráció ország select | `DISTINCT visible_country_id` (self-élek miatt minden ország megjelenhet) |
| `countries.visible` | Master zászló (lista/seed) — **nem** a TAB lista |

**Értelmezés:** ha a user országa Szlovákia, a TAB-okon mindig ott van a szlovák. Ha az ország további tartalomnyelveket akar (pl. EN, HU), azokat **plusz nyelvként** adja hozzá a Countries formon.

## Saját ország szabály

- Minden országhoz **mindig** van `country_id = visible_country_id` sor (`ensureSelfVisibility`, pos=1).
- A Select2 listában a **saját ország nem jelenik meg** (csak tárolva).
- Mentéskor a saját sor **nem törölhető** (`ensureSelfFirst` + utólagos `ensureSelfVisibility`).

## Admin UI

Countries full edit: **Additional languages** multiple Select2 → `visible_countries._ids` (csak partnerek).  
Saját nyelv nincs a listában; a help szöveg ezt magyarázza.

**Form TAB-ok (Samples, Parents, …):** pontosan ezek a nyelvek — `FormLanguages::tabs()` ← `country_visibilities` (saját + extras). Nincs globális „minden ország” füllista.

## Kód

| API | Szerep |
|-----|--------|
| `ensureSelfVisibility` / `ensureSelfFirst` | saját nyelv lock |
| `visibleCountryIdsFor($activeId)` | saját + extras |
| `seedDefaultVisibilitiesForCountry` | új ország: csak self |
| `FormLanguages::tabs()` | saját nyelvű TAB első |
| `AdminCountry::masterVisibleOptions()` | form opcióforrás (view kiszűri a self-et) |
