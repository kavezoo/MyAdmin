<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

/**
 * Club president panel dashboard — content TBD.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $this->set('title', __('Dashboard'));
        $this->set('breadcrumb', __('Dashboard'));
    }
}
