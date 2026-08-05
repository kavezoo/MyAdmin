<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use Cake\Http\Response;

/**
 * Legacy `/clubpresident/applicants` — redirects to Members (cards + list).
 */
class ApplicantsController extends AppController
{
    /**
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        return $this->redirect(['controller' => 'Members', 'action' => 'index']);
    }

    /**
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function approve(?string $id = null): Response
    {
        return $this->redirect(['controller' => 'Members', 'action' => 'index']);
    }

    /**
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function reject(?string $id = null): Response
    {
        return $this->redirect(['controller' => 'Members', 'action' => 'index']);
    }
}
