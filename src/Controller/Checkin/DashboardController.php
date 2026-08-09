<?php
declare(strict_types=1);

namespace App\Controller\Checkin;

use Cake\Http\Response;

/**
 * Check-in home → applicants list (competition day staff).
 */
class DashboardController extends AppController
{
    public function index(): ?Response
    {
        return $this->redirect([
            'prefix' => 'Checkin',
            'controller' => 'Applicants',
            'action' => 'index',
        ]);
    }
}
