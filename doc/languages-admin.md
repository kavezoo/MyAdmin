# Languages Admin — lista / szűrő / oszlopok

UI locale sorok: `languages` + Translate (`name`).  
ACL: `LanguageAccess` — **superuser** teljes CRUD; **admin** csak `visible` + `pos` (Countries mintára).

Kapcsolódó: [login-language.md](login-language.md), [countries-admin.md](countries-admin.md), [i18n.md](i18n.md), [admin-konvenciok.md](admin-konvenciok.md).

Mezők: `code` (locale, unique), `name` (angol kanonikus + Translate), `endonim_name` (endoním — nem fordított), `visible` (login Select2), `pos` (DB DEFAULT `1000`).

---

## 1. Jogok

| Művelet | Superuser | Admin | Egyéb |
|---------|-----------|-------|-------|
| Index / view / menü | igen | igen | nem |
| Add / delete | igen | nem | nem |
| Edit összes mező | igen | nem | nem |
| Edit `visible` + `pos` | igen | igen | nem |

- Sidebar: Settings → **Languages** (Countries fölött) — `LanguageAccess::canAccessModule()`.
- Jog nélkül: `denyWithFlashWarning` + Dashboard.
- Törlés tiltva: `en_GB` / `hu_HU`; vagy ha van `countries.locale = code`. Delete után `i18n` (model=Languages) takarítás.

---

## 2. Index

Header (jobb): **Only visible languages** switch → `|` → kereső → lapozó.  
Session: `Admin.languagesVisibleOnly` (default be).  
Oszlopok: id, code, name, endonim_name, visible, pos, created/modified, actions.  
Modal: `recordGet` (nincs i18n EAV lista).

---

## 3. Form

- Superuser: `code`, `name`, `endonim_name`, majd `<hr>` → `visible` → `pos`.
- Admin: plaintext mezők + szerkeszthető visible/pos.
- Formon **nincs** nyelvi TAB a `name`-hez (Countries mintára; fordítások seed/`AdminLanguage::syncFromCountries`).
- `pos` üresen hagyva → DB DEFAULT.

---

## 4. Kód

| Fájl | Szerep |
|------|--------|
| `LanguageAccess` | ACL |
| `Admin\LanguagesController` | CRUD + visible-only + recordGet |
| `LanguagesTable` | Translate, canDelete, i18n cleanup |
| `templates/Admin/Languages/{index,form,view}.php` | UI |
| `config/admin_search.php` → `Languages` | globális kereső |
