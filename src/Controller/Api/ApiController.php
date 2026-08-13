<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Event\EventInterface;

/**
 * Shared base for Flutter /api/v1 controllers.
 * Auth open-access during debug is handled in App\Controller\Api\AppController.
 */
class ApiController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
    }
}
