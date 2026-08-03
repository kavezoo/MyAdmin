# MyAdmin — használat a programban

Gyakorlati cheat sheet: amikor a **saját kódba** nyúlsz (controller, view, utility, CLI), ezeket hívd.  
Teljes modul-specek: [setups.md](setups.md), keret: [README.md](README.md).

---

## Setup érték olvasása — `Setup::get()`

A Setups táblában (Admin → Settings → Setups) tárolt beállításokat **bárhonnan** így olvasod.

| | |
|--|--|
| Fájl | `src/Utility/Setup.php` |
| Namespace | `App\Utility\Setup` |
| Alá | `SetupsTable::getValue()` → típusos cast (`SetupValue::cast`) |

### Alapminta

```php
use App\Utility\Setup;

$title = Setup::get('site_title', 'My Admin');
```

1. argumentum: **slug** (kisbetű, `a-z0-9_`, pl. `site_title`)  
2. argumentum: **default**, ha nincs ilyen rekord, `visible = 0`, vagy érvénytelen a slug

`fetchTable('Setups')` **nem** kell — a `Setup::get()` elintézi.

### Típus → PHP visszatérés

| Type (Admin) | `Setup::get()` eredmény | Példa default |
|--------------|-------------------------|---------------|
| String / Text | `string` | `'My Admin'` |
| Integer | `int` | `10` |
| Float | `float` | `1.5` |
| Boolean | `bool` | `false` |
| Date / Time / Datetime | `string` (SQL forma) | `'2026-01-01'` |
| JSON | `array` (asszociatív / vegyes) | `[]` |
| Array | `list` (indexelt tömb) | `[]` |

### Slug szabály (röviden)

- Csak: `a-z`, `0-9`, `_` — pl. `site_title`
- **Nem** kötőjel: `site-title` → default jön vissza
- A slug-ot az Admin formon állítod; a kódban **ugyanazt** a stringet add át

---

### Példák

Előfeltétel az Adminban (példa rekordok):

| Name | Slug | Type | Value |
|------|------|------|-------|
| Site title | `site_title` | String | `My Admin` |
| Max upload (MB) | `max_upload_mb` | Integer | `10` |
| Feature X | `feature_x` | Boolean | bekapcsolva |
| Support email | `support_email` | String | `info@example.com` |
| Allowed roles | `allowed_roles` | Array | soronként: `admin` / `editor` |
| Extra options | `extra_options` | JSON | `{"theme":"dark","limit":5}` |

#### 1) Controller — cím a view-nak

```php
// src/Controller/Admin/DashboardController.php
namespace App\Controller\Admin;

use App\Utility\Setup;

class DashboardController extends AppController
{
    public function index()
    {
        $this->set('title', Setup::get('site_title', 'My Admin'));
    }
}
```

#### 2) Template / layout — megjelenítés

```php
<?php
// templates/layout/admin.php (vagy bármely .php sablon)
use App\Utility\Setup;
?>
<title><?= h(Setup::get('site_title', 'My Admin')) ?></title>
```

#### 3) Integer — feltétel / limit

```php
use App\Utility\Setup;

$maxMb = Setup::get('max_upload_mb', 10); // int, pl. 10

if ($fileSizeMb > $maxMb) {
    $this->Flash->error(__('The file is too large.'));

    return;
}
```

#### 4) Boolean — feature flag

```php
use App\Utility\Setup;

if (Setup::get('feature_x', false)) {
    // új funkció kódja
}
```

#### 5) String — email / szöveg

```php
use App\Utility\Setup;
use Cake\Mailer\Mailer;

$mailer = new Mailer('default');
$mailer
    ->setTo(Setup::get('support_email', 'info@example.com'))
    ->setSubject(Setup::get('site_title', 'My Admin'))
    ->deliver('…');
```

#### 6) Array — lista bejárása

Adminban Array típus, soronként egy érték → JSON tömb a DB-ben.

```php
use App\Utility\Setup;

/** @var list<string> $roles */
$roles = Setup::get('allowed_roles', ['admin']);

if (!in_array($userRole, $roles, true)) {
    throw new ForbiddenException();
}
```

#### 7) JSON — kulcsos opciók

```php
use App\Utility\Setup;

/** @var array<string, mixed> $opts */
$opts = Setup::get('extra_options', ['theme' => 'light', 'limit' => 20]);

$theme = $opts['theme'] ?? 'light';
$limit = (int)($opts['limit'] ?? 20);
```

#### 8) Utility / service osztály

```php
namespace App\Service;

use App\Utility\Setup;

class ReportService
{
    public function pageTitle(): string
    {
        return (string)Setup::get('site_title', 'My Admin');
    }
}
```

#### 9) Table / Association belül (alternatíva)

```php
// ha már Table kontextusban vagy:
$value = $this->fetchTable('Setups')->getValue('site_title', 'My Admin');

// egyébként maradj a Setup::get()-nél
```

---

### Ha nem jön érték

1. Van-e rekord a slug-gal az Admin Setups listában  
2. Be van-e kapcsolva a **Visible**  
3. Egyezik-e pontosan a slug (kisbetű, `_`)  
4. A default típusa illik-e ahhoz, amit a kódban vársz  

---

## Kapcsolódó specek

| Téma | Fájl |
|------|------|
| Setups séma, típusok, form | [setups.md](setups.md) |
| Agent rule | `.cursor/rules/setups-eav.mdc` |
| Új CRUD modul | [crud-utmutato.md](crud-utmutato.md) |
