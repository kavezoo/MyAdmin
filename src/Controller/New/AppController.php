<?php
declare(strict_types=1);

namespace App\Controller\New;

use App\Auth\MembershipProfile;
use App\Controller\PanelAppController;
use Cake\Event\EventInterface;

/**
 * New-role panel (`/new/...`). Registration.defaultRole lands here only.
 *
 * Incomplete profile: Dashboard is allowed (shows warning + CTA).
 * Other `/new/*` actions redirect to `/complete-profile` until name + club are set.
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
        if (!MembershipProfile::needsProfileCompletion($data)) {
            return;
        }

        $controller = (string)$this->getRequest()->getParam('controller');
        $action = (string)$this->getRequest()->getParam('action');
        // Let the dashboard explain what is missing; force completion elsewhere.
        if (strcasecmp($controller, 'Dashboard') === 0 && strcasecmp($action, 'index') === 0) {
            return;
        }

        $event->setResult($this->redirect('/complete-profile'));
    }
}
