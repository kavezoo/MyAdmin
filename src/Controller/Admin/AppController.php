<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use Cake\I18n\I18n;

/**
 * Admin Application Controller
 *
 * Shared base for controllers under the Admin prefix.
 * Az admin felület nyelve mindig magyar; nincs nyelvválasztó.
 */
class AppController extends BaseController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        I18n::setLocale('hu_HU');
        $this->viewBuilder()->setLayout('admin');
    }
}
