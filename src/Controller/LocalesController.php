<?php
declare(strict_types=1);

namespace App\Controller;

use App\Auth\CurrentUser;
use App\Auth\RoleHome;
use Cake\Http\Response;

/**
 * Entry redirects.
 */
class LocalesController extends AppController
{
    /**
     * `/` → login (panels use session/user locale, no URL language prefix).
     */
    public function home(): Response
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null) {
            return $this->redirect(RoleHome::url(CurrentUser::role($this->getRequest())));
        }

        return $this->redirect([
            'plugin' => null,
            'controller' => 'Users',
            'action' => 'login',
        ]);
    }
}
