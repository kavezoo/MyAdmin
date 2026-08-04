<?php
declare(strict_types=1);

namespace App\Controller\New;

use App\Auth\MembershipProfile;
use App\Controller\PanelAppController;
use Cake\Event\EventInterface;
use CakeDC\Users\Utility\UsersUrl;

/**
 * New-role panel (`/new/...`). Registration.defaultRole lands here only.
 */
class AppController extends PanelAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        $data = method_exists($identity, 'getOriginalData')
            ? $identity->getOriginalData()
            : $identity;
        if (MembershipProfile::needsProfileCompletion($data)) {
            $event->setResult($this->redirect(UsersUrl::actionUrl('completeProfile')));
        }
    }
}
