<?php
declare(strict_types=1);

namespace App\Controller\President;

/**
 * President panel dashboard — content TBD.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $this->set('title', __('Dashboard'));
        $this->set('breadcrumb', __('Dashboard'));
    }
}
