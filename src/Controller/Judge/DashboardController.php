<?php
declare(strict_types=1);

namespace App\Controller\Judge;

use App\Utility\AdminTranslate;
use App\Utility\CompetitionBrowse;
use App\Utility\CompetitionStaff;

/**
 * Judge dashboard — competitions where this user is table judge.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $this->set('title', __('Judge'));
        $this->set('breadcrumb', __('Judge'));

        $ids = $this->assignedCompetitionIds();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $competitions = $ids !== []
            ? $competitionsTable->find()
                ->contain(['Clubs', 'Cities'])
                ->where(['Competitions.id IN' => $ids])
                ->where(CompetitionBrowse::activeConditions())
                ->orderBy(['Competitions.competition_datetime' => 'ASC'])
                ->all()
            : $competitionsTable->find()->where(['1 = 0'])->all();

        $this->set(compact('competitions'));
    }
}
