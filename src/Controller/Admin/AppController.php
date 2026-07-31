<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;

/**
 * Admin Application Controller
 *
 * Shared base for controllers under the Admin prefix.
 */
class AppController extends BaseController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
    }
}
