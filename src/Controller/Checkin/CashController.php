<?php
declare(strict_types=1);

namespace App\Controller\Checkin;

use App\Utility\AdminTranslate;
use App\Utility\CompetitionCashDesk;
use App\Utility\CompetitionStaff;
use Cake\Http\Response;

/**
 * Cash desk reconciliation — who paid, who collected, how much per till.
 */
class CashController extends AppController
{
    public function index(?string $competitionId = null): ?Response
    {
        $this->set('title', __('Cash desk'));
        $this->set('breadcrumb', __('Cash desk'));

        $ids = $this->assignedCompetitionIds();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $competitionOptions = [];
        $competition = null;
        $paidCashGroups = [];
        $paidCashTotal = 0.0;

        if ($ids !== []) {
            foreach ($competitionsTable->find()
                ->where(['Competitions.id IN' => $ids])
                ->orderBy(['Competitions.competition_datetime' => 'ASC'])
                ->all() as $row
            ) {
                $competitionOptions[(string)$row->id] = CompetitionCashDesk::competitionOptionLabel($row);
            }

            $competitionId = $competitionId !== null && $competitionId !== ''
                ? $competitionId
                : (string)$this->request->getQuery('competition_id');
            if ($competitionId === '' || !isset($competitionOptions[$competitionId])) {
                $competitionId = (string)array_key_first($competitionOptions);
            }

            if (
                $competitionId !== ''
                && CompetitionStaff::canOperateOnCompetition(
                    $competitionId,
                    CompetitionStaff::ROLE_CHECKIN,
                    null,
                    $this->getRequest()
                )
            ) {
                $competition = $competitionsTable->get($competitionId, contain: ['Clubs']);
                $desk = CompetitionCashDesk::paidGroupsForCompetition($competition);
                $paidCashGroups = $desk['groups'];
                $paidCashTotal = $desk['total'];
            }
        } else {
            $competitionId = '';
        }

        $this->set(compact(
            'competition',
            'competitionOptions',
            'competitionId',
            'paidCashGroups',
            'paidCashTotal',
        ));

        return null;
    }
}
