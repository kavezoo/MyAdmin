<?php
declare(strict_types=1);

namespace App\Controller\Member;

use App\Model\Table\CompetitionsTable;
use App\Model\Table\CompetitionsUsersTable;
use App\Model\Table\UsersTable;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionApplication;
use App\Utility\CompetitionBrowse;

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

        $homeCountryId = 0;
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
                $homeCountryId = (int)($row['country_id'] ?? 0);
            }
        }

        $clubFeePaid = is_array($row)
            ? CompetitionApplication::memberMayApply($row)
            : false;

        $browseCountryId = CompetitionBrowse::resolveCountryId(
            $this->request,
            $homeCountryId,
            CompetitionBrowse::SESSION_MEMBER
        );
        $browseCountryOptions = CompetitionBrowse::countryOptions();

        AdminTranslate::applyLocale($competitionsTable);
        $competitions = $browseCountryId > 0
            ? $competitionsTable->find()
                ->contain(['Clubs'])
                ->where(['Competitions.country_id' => $browseCountryId])
                ->where(CompetitionBrowse::activeConditions())
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

        $this->set(compact(
            'competitions',
            'myApplications',
            'clubFeePaid',
            'browseCountryId',
            'browseCountryOptions',
            'homeCountryId'
        ));
        $this->set('countryId', $browseCountryId);
    }
}
