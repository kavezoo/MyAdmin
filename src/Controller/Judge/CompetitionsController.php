<?php
declare(strict_types=1);

namespace App\Controller\Judge;

use Cake\Http\Response;

/**
 * Judge competition entry → applicants list.
 */
class CompetitionsController extends AppController
{
    public function view(?string $id = null): ?Response
    {
        if ($id === null || $id === '') {
            return $this->redirect(['prefix' => 'Judge', 'controller' => 'Dashboard', 'action' => 'index']);
        }

        return $this->redirect([
            'prefix' => 'Judge',
            'controller' => 'Applicants',
            'action' => 'index',
            $id,
        ]);
    }
}
