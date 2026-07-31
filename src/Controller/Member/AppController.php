<?php
declare(strict_types=1);

namespace App\Controller\Member;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;

/**
 * Member Application Controller
 *
 * Shared base for controllers under the Member prefix (/ {lang} /member /...).
 * Locale is set by LocaleMiddleware from the URL language prefix.
 */
class AppController extends BaseController
{
    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->set('lang', (string)$this->request->getParam('lang', 'hu'));
    }
}
