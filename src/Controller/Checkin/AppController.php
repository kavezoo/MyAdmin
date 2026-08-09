<?php
declare(strict_types=1);

namespace App\Controller\Checkin;

use App\Auth\CurrentUser;
use App\Auth\PanelAccess;
use App\Controller\PanelAppController;
use App\Utility\CompetitionStaff;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Check-in desk panel — entry fee collection + racing-pipe handout.
 */
class AppController extends PanelAppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!PanelAccess::canAccessPrefix('Checkin', $this->getRequest())) {
            throw new ForbiddenException(__('You are not assigned to check-in for any competition.'));
        }
    }

    /**
     * @return list<string>
     */
    protected function assignedCompetitionIds(): array
    {
        return CompetitionStaff::deskCompetitionIds(
            CompetitionStaff::ROLE_CHECKIN,
            CurrentUser::id($this->getRequest()),
            $this->getRequest()
        );
    }
}
