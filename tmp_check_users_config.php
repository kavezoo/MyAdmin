<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Core\Plugin;
use CakeDC\Users\Utility\UsersUrl;

require __DIR__ . '/vendor/autoload.php';

define('ROOT', __DIR__);
require ROOT . '/config/bootstrap.php';

echo 'Users.controller=[' . var_export(Configure::read('Users.controller'), true) . ']' . PHP_EOL;
echo 'Users.table=[' . var_export(Configure::read('Users.table'), true) . ']' . PHP_EOL;
echo 'Users.config=[' . var_export(Configure::read('Users.config'), true) . ']' . PHP_EOL;
echo 'isCustom=' . (UsersUrl::isCustom() ? 'yes' : 'no') . PHP_EOL;
echo 'actionParams login:' . PHP_EOL;
print_r(UsersUrl::actionParams('login'));
echo 'CakeDC loaded=' . (Plugin::isLoaded('CakeDC/Users') ? 'yes' : 'no') . PHP_EOL;
if (Plugin::isLoaded('CakeDC/Users')) {
    echo 'templatePath=' . Plugin::templatePath('CakeDC/Users') . PHP_EOL;
    $login = Plugin::templatePath('CakeDC/Users') . 'Users' . DIRECTORY_SEPARATOR . 'login.php';
    echo 'vendor login exists=' . (is_file($login) ? 'yes' : 'no') . ' path=' . $login . PHP_EOL;
}
