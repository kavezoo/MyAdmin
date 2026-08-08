<?php
declare(strict_types=1);

namespace App\Controller\Member;

use App\Model\Table\CompetitionsTable;
use App\Model\Table\CompetitionsUsersTable;
use App\Model\Table\UsersTable;
use App\Utility\CompetitionApplication;
use Cake\I18n\DateTime;

/**
 * Member panel dashboard — profile + open competitions.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $this->set('title', __('Dashboard'));
        $this->set('breadcrumb', __('Dashboard'));

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        /** @var \App\Model\Table\CompetitionsTable $competitionsTable */
        $competitionsTable = $this->fetchTable('Competitions');
        /** @var \App\Model\Table\CompetitionsUsersTable $competitionsUsers */
        $competitionsUsers = $this->fetchTable('CompetitionsUsers');

        $countryId = 0;
        $userId = '';
        $row = null;
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        }
        if ($userId !== '') {
            $row = $users->find()
                ->select(['id', 'country_id', 'club_id', \App\Utility\MembershipFee::FIELD_CLUB])
                ->where(['Users.id' => $userId])
                ->disableHydration()
                ->first();
            if (is_array($row)) {
                $countryId = (int)($row['country_id'] ?? 0);
            }
        }

        $clubFeePaid = is_array($row)
            ? CompetitionApplication::memberMayApply($row)
            : false;

        $now = DateTime::now()->format('Y-m-d H:i:s');
        $competitions = $countryId > 0
            ? $competitionsTable->find()
                ->contain(['Clubs'])
                ->where([
                    'Competitions.country_id' => $countryId,
                    'Competitions.visible' => true,
                    'OR' => [
                        'Competitions.end_datetime IS' => null,
                        'Competitions.end_datetime >=' => $now,
                    ],
                ])
                ->orderBy([
                    'Competitions.first_date_of_application' => 'ASC',
                    'Competitions.application_deadline' => 'ASC',
                ])
                ->limit(12)
                ->all()
            : $competitionsTable->find()->where(['1 = 0'])->all();

        $myApplications = [];
        if ($userId !== '') {
            $apps = $competitionsUsers->find()
                ->where([
                    'CompetitionsUsers.user_id' => $userId,
                    'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->all();
            foreach ($apps as $app) {
                if (!CompetitionApplication::hasApplication($app)) {
                    continue;
                }
                $myApplications[(string)$app->competition_id] = $app;
            }
        }

        $this->set(compact('competitions', 'myApplications', 'countryId', 'clubFeePaid'));
    }
}
