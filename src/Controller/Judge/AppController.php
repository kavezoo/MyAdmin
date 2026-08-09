<?php
declare(strict_types=1);

namespace App\Controller\Judge;

use App\Auth\CurrentUser;
use App\Auth\PanelAccess;
use App\Controller\PanelAppController;
use App\Utility\CompetitionStaff;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Table judge panel — assigned competitions only.
 */
class AppController extends PanelAppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!PanelAccess::canAccessPrefix('Judge', $this->getRequest())) {
            throw new ForbiddenException(__('You are not assigned as a judge for any competition.'));
        }
    }

    /**
     * @return list<string>
     */
    protected function assignedCompetitionIds(): array
    {
        return CompetitionStaff::deskCompetitionIds(
            CompetitionStaff::ROLE_JUDGE,
            CurrentUser::id($this->getRequest()),
            $this->getRequest()
        );
    }
}
