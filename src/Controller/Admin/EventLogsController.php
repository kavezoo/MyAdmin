<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\EventLogAccess;
use App\Auth\SetupAccess;
use App\Utility\AdminCountry;
use App\Utility\ActivityLogSetup;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * Event logs — searchable audit for president+ (country-scoped; superuser all).
 *
 * @property \App\Model\Table\EventLogsTable $EventLogs
 */
class EventLogsController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 500;

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!EventLogAccess::canSearch($this->getRequest())) {
            return $this->denyWithFlashWarning(__('You are not allowed to browse event logs.'));
        }
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Event logs'));
        $this->viewBuilder()->setVar('breadcrumb', __('Event logs'));

        $redirect = $this->applyIndexListState('EventLogs');
        if ($redirect !== null) {
            return $redirect;
        }

        $canFilterCountries = EventLogAccess::canFilterAllCountries($this->getRequest());
        $filterCountryId = $this->resolveFilterCountryId($canFilterCountries);
        $filterUserId = trim((string)$this->request->getQuery('user_id'));
        $filterUserLabel = $this->resolveUserLabel($filterUserId);

        $paginateOptions = [
            'limit' => $this->indexLimit,
            'maxLimit' => $this->indexMaxLimit,
            'order' => ['EventLogs.created' => 'DESC', 'EventLogs.id' => 'DESC'],
            'sortableFields' => [
                'id',
                'created',
                'module',
                'action',
                'entity',
                'entity_id',
                'description',
                'ip',
                'actor_role',
                'Countries.name',
                'Users.email',
            ],
        ];

        $query = $this->EventLogs->find()
            ->contain(['Countries', 'Users'])
            ->where(['EventLogs.country_id' => $filterCountryId]);

        if ($filterUserId !== '') {
            $query->where(['EventLogs.user_id' => $filterUserId]);
        }

        $actionFilter = trim((string)$this->request->getQuery('action_filter'));
        if ($actionFilter !== '') {
            $query->where(['EventLogs.action' => $actionFilter]);
        }
        $moduleFilter = trim((string)$this->request->getQuery('module_filter'));
        if ($moduleFilter !== '') {
            $query->where(['EventLogs.module' => $moduleFilter]);
        }

        $query = $this->applyIndexSearch($query, $this->EventLogs);

        $eventLogs = $this->paginate($query, $paginateOptions);

        $actionOptions = $this->EventLogs->find()
            ->select(['action'])
            ->distinct(['action'])
            ->where(['EventLogs.country_id' => $filterCountryId])
            ->orderBy(['action' => 'ASC'])
            ->all()
            ->combine('action', 'action')
            ->toArray();

        $moduleOptions = $this->EventLogs->find()
            ->select(['module'])
            ->distinct(['module'])
            ->where(['EventLogs.country_id' => $filterCountryId])
            ->orderBy(['module' => 'ASC'])
            ->all()
            ->combine('module', 'module')
            ->toArray();

        $countryOptions = $canFilterCountries ? AdminCountry::masterVisibleOptions() : [];
        $filterCountryLabel = AdminCountry::label($filterCountryId);
        $userSearchUrl = Router::url([
            'prefix' => 'Admin',
            'controller' => 'EventLogs',
            'action' => 'userOptions',
        ]);

        $this->set(compact(
            'eventLogs',
            'canFilterCountries',
            'filterCountryId',
            'filterCountryLabel',
            'countryOptions',
            'actionOptions',
            'moduleOptions',
            'actionFilter',
            'moduleFilter',
            'filterUserId',
            'filterUserLabel',
            'userSearchUrl',
        ));
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);
        $this->setActivityLogSetupViewVars();
    }

    /**
     * Toggle activity logging for the Admin working country (Setups).
     *
     * @return \Cake\Http\Response
     */
    public function toggleActivityLogging(): Response
    {
        return $this->toggleActivitySetup(ActivityLogSetup::SLUG_LOGGING_ENABLED, [
            'on' => __('Activity logging has been turned on for {0}.'),
            'off' => __('Activity logging has been turned off for {0}.'),
        ]);
    }

    /**
     * Toggle whether users may view their own activity log (Admin working country).
     *
     * @return \Cake\Http\Response
     */
    public function toggleUsersActivityView(): Response
    {
        return $this->toggleActivitySetup(ActivityLogSetup::SLUG_USERS_VIEW_ENABLED, [
            'on' => __('Users can now view their own activity in {0}.'),
            'off' => __('Users can no longer view their own activity in {0}.'),
        ]);
    }

    /**
     * @param array{on: string, off: string} $flashMessages
     */
    protected function toggleActivitySetup(string $slug, array $flashMessages): Response
    {
        $this->request->allowMethod(['post']);

        $countryId = AdminCountry::id($this->getRequest());
        if ($countryId < 1) {
            throw new ForbiddenException(__('No working country is set.'));
        }

        /** @var \App\Model\Table\SetupsTable $setups */
        $setups = $this->fetchTable('Setups');
        $setup = $setups->findBySlugAndCountry($slug, $countryId);
        if ($setup === null) {
            $setups->ensureActivityLogSetups($countryId);
            $setup = $setups->findBySlugAndCountry($slug, $countryId);
        }
        if ($setup === null || !SetupAccess::canEditValue($setup, $this->getRequest())) {
            throw new ForbiddenException(__('You are not allowed to change this setting.'));
        }

        $newState = $setups->toggleBoolean($countryId, $slug);
        if ($newState === null) {
            $this->Flash->error(__('The setting could not be saved. Please try again.'));

            return $this->redirectToEventLogIndex();
        }

        $countryLabel = AdminCountry::label($countryId);
        $this->Flash->success(__(
            $newState ? $flashMessages['on'] : $flashMessages['off'],
            $countryLabel
        ));

        return $this->redirectToEventLogIndex();
    }

    protected function redirectToEventLogIndex(): Response
    {
        $redirect = $this->request->getData('_redirect');
        if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, '/')) {
            return $this->redirect($redirect);
        }

        $query = $this->request->getQueryParams();

        return $this->redirect(['action' => 'index', '?' => $query]);
    }

    protected function setActivityLogSetupViewVars(): void
    {
        ActivityLogSetup::ensureRowsForAllCountries();

        $workingCountryId = AdminCountry::id($this->getRequest());
        $workingCountryLabel = $workingCountryId > 0 ? AdminCountry::label($workingCountryId) : '';

        /** @var \App\Model\Table\SetupsTable $setups */
        $setups = $this->fetchTable('Setups');
        $loggingSetup = $workingCountryId > 0
            ? $setups->findBySlugAndCountry(ActivityLogSetup::SLUG_LOGGING_ENABLED, $workingCountryId)
            : null;
        $usersViewSetup = $workingCountryId > 0
            ? $setups->findBySlugAndCountry(ActivityLogSetup::SLUG_USERS_VIEW_ENABLED, $workingCountryId)
            : null;

        $this->set('workingCountryId', $workingCountryId);
        $this->set('workingCountryLabel', $workingCountryLabel);
        $this->set('activityLoggingEnabled', ActivityLogSetup::isLoggingEnabled($workingCountryId, $this->getRequest()));
        $this->set('usersActivityLogVisible', ActivityLogSetup::usersCanViewOwn($workingCountryId, $this->getRequest()));
        $this->set('canToggleActivityLogging', $loggingSetup !== null && SetupAccess::canEditValue($loggingSetup, $this->getRequest()));
        $this->set('canToggleUsersActivityView', $usersViewSetup !== null && SetupAccess::canEditValue($usersViewSetup, $this->getRequest()));
    }

    /**
     * Select2 AJAX: users for event-log filter (country-scoped).
     *
     * @return \Cake\Http\Response
     */
    public function userOptions(): Response
    {
        $this->request->allowMethod(['get']);

        $canFilterCountries = EventLogAccess::canFilterAllCountries($this->getRequest());
        $filterCountryId = $this->resolveFilterCountryId($canFilterCountries);
        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 20;

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $users->find()
            ->select(['id', 'email', 'username', 'first_name', 'last_name', 'role'])
            ->where(['Users.country_id' => $filterCountryId])
            ->orderBy(['Users.email' => 'ASC']);

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where([
                'OR' => [
                    'Users.email LIKE' => $like,
                    'Users.username LIKE' => $like,
                    'Users.first_name LIKE' => $like,
                    'Users.last_name LIKE' => $like,
                ],
            ]);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->offset(($page - 1) * $limit)->all();

        $results = [];
        foreach ($rows as $user) {
            $results[] = [
                'id' => (string)$user->get('id'),
                'text' => $this->formatUserOptionLabel($user),
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $limit) < $total,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $eventLog = $this->EventLogs->find()
            ->contain(['Countries', 'Users'])
            ->where(['EventLogs.id' => (int)$id])
            ->first();
        if ($eventLog === null) {
            throw new NotFoundException(__('Record not found.'));
        }
        if (!EventLogAccess::canView($eventLog, $this->getRequest())) {
            throw new ForbiddenException(__('You are not allowed to view this event.'));
        }

        $this->set('title', __('Event log'));
        $this->viewBuilder()->setVar('breadcrumb', __('Event logs'));
        $this->set(compact('eventLog'));
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);
        $this->setActivityLogSetupViewVars();
    }

    protected function resolveFilterCountryId(bool $canFilterCountries): int
    {
        if ($canFilterCountries) {
            $fromQuery = (int)$this->request->getQuery('country_id');
            if ($fromQuery > 0) {
                $options = AdminCountry::masterVisibleOptions();
                if (isset($options[$fromQuery])) {
                    return $fromQuery;
                }
            }
            // Prefer working Admin country, then user country
            $working = AdminCountry::id();
            if ($working > 0) {
                return $working;
            }
        }

        $mine = \App\Auth\CurrentUser::countryId($this->getRequest());
        if ($mine > 0) {
            return $mine;
        }

        $fallback = AdminCountry::id();
        if ($fallback < 1) {
            throw new ForbiddenException(__('No active country is set for event log filtering.'));
        }

        return $fallback;
    }

    protected function resolveUserLabel(string $userId): string
    {
        if ($userId === '') {
            return '';
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->select(['id', 'email', 'username', 'first_name', 'last_name', 'role'])
            ->where(['Users.id' => $userId])
            ->first();

        return $user !== null ? $this->formatUserOptionLabel($user) : $userId;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $user
     */
    protected function formatUserOptionLabel(object $user): string
    {
        $name = trim((string)($user->get('first_name') ?? '') . ' ' . (string)($user->get('last_name') ?? ''));
        $email = trim((string)($user->get('email') ?? ''));
        $role = trim((string)($user->get('role') ?? ''));

        if ($name !== '' && $email !== '') {
            $label = $name . ' <' . $email . '>';
        } elseif ($email !== '') {
            $label = $email;
        } else {
            $label = (string)($user->get('username') ?? $user->get('id'));
        }

        if ($role !== '') {
            $label .= ' (' . $role . ')';
        }

        return $label;
    }
}
