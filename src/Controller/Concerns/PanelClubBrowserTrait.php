<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\AdminCountry;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Read-only club directory for Clubpresident / Member panels.
 *
 * Default filter = user's country; country select lists only countries that have clubs.
 */
trait PanelClubBrowserTrait
{
    protected const CLUB_BROWSER_COUNTRY_SESSION = 'Panel.clubBrowserCountryId';

    /**
     * Logged-in user's country_id and club_id from DB.
     *
     * @return array{country_id: int, club_id: int, user_id: string}
     */
    protected function clubBrowserIdentity(): array
    {
        $identity = $this->getRequest()->getAttribute('identity');
        $userId = '';
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        }
        if ($userId === '') {
            return ['country_id' => 0, 'club_id' => 0, 'user_id' => ''];
        }

        $row = $this->fetchTable('Users')->find()
            ->select(['country_id', 'club_id'])
            ->where(['Users.id' => $userId])
            ->disableHydration()
            ->first();

        return [
            'country_id' => (int)($row['country_id'] ?? 0),
            'club_id' => (int)($row['club_id'] ?? 0),
            'user_id' => $userId,
        ];
    }

    /**
     * Countries that have at least one visible + enabled club.
     *
     * @return array<int, string>
     */
    protected function clubBrowserCountryOptions(): array
    {
        $ids = $this->fetchTable('Clubs')->find()
            ->select(['country_id'])
            ->where([
                'Clubs.visible' => true,
                'Clubs.enabled' => true,
            ])
            ->distinct(['country_id'])
            ->disableHydration()
            ->all()
            ->extract('country_id')
            ->toList();
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        return AdminCountry::filterOptionsByCountryIds(AdminCountry::options(), $ids);
    }

    /**
     * Resolve country filter: query → session → home country.
     */
    protected function resolveClubBrowserCountryId(int $homeCountryId, array $countryOptions): int
    {
        $session = $this->getRequest()->getSession();
        $query = $this->getRequest()->getQueryParams();

        if (array_key_exists('country_id', $query)) {
            $raw = $query['country_id'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $countryId = (int)$raw;
            if ($countryId > 0 && !isset($countryOptions[$countryId])) {
                $countryId = $homeCountryId;
            }
            $session->write(self::CLUB_BROWSER_COUNTRY_SESSION, $countryId);

            return $countryId > 0 ? $countryId : $homeCountryId;
        }

        $saved = $session->read(self::CLUB_BROWSER_COUNTRY_SESSION);
        if ($saved !== null && is_numeric($saved)) {
            $countryId = (int)$saved;
            if ($countryId > 0 && isset($countryOptions[$countryId])) {
                return $countryId;
            }
        }

        if ($homeCountryId > 0 && isset($countryOptions[$homeCountryId])) {
            $session->write(self::CLUB_BROWSER_COUNTRY_SESSION, $homeCountryId);

            return $homeCountryId;
        }

        $first = $countryOptions !== [] ? (int)array_key_first($countryOptions) : 0;
        if ($first > 0) {
            $session->write(self::CLUB_BROWSER_COUNTRY_SESSION, $first);
        }

        return $first;
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    protected function clubBrowserIndex()
    {
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);
        $this->set('title', __('Clubs'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));

        $identity = $this->clubBrowserIdentity();
        $homeCountryId = $identity['country_id'];
        $myClubId = $identity['club_id'];
        $countryOptions = $this->clubBrowserCountryOptions();

        if ($countryOptions === []) {
            $this->Flash->info(__('No clubs are available yet.'));
            $this->set('clubs', method_exists($this, 'emptyPaginated') ? $this->emptyPaginated(50) : []);
            $this->set(compact('countryOptions', 'homeCountryId', 'myClubId'));
            $this->set('countryId', 0);
            $this->set('countryLabel', '');

            return;
        }

        $countryId = $this->resolveClubBrowserCountryId($homeCountryId, $countryOptions);

        // Keep country_id in URL for paginator / bookmarks.
        if (!array_key_exists('country_id', $this->getRequest()->getQueryParams())) {
            $params = $this->getRequest()->getQueryParams();
            $params['country_id'] = (string)$countryId;
            if (!isset($params['page'])) {
                $params['page'] = '1';
            }

            return $this->redirect(['action' => 'index', '?' => $params]);
        }

        /** @var \App\Model\Table\ClubsTable $clubsTable */
        $clubsTable = $this->fetchTable('Clubs');

        if (method_exists($this, 'applyIndexListState')) {
            $redirect = $this->applyIndexListState('Clubs');
            if ($redirect !== null) {
                return $this->withClubBrowserCountryOnRedirect($redirect, $countryId);
            }
        }

        // applyIndexListState replaces query with sort/page/q only — keep country_id for paginator/search.
        $this->mergeClubBrowserCountryQuery($countryId);

        $paginateOptions = method_exists($this, 'indexPaginateOptionsFor')
            ? $this->indexPaginateOptionsFor($clubsTable, [
                'sortableFields' => [
                    'id',
                    'name',
                    'short_name',
                    'Cities.name',
                    'user_count',
                    'competition_count',
                    'pos',
                ],
                'order' => [
                    'Clubs.pos' => 'ASC',
                    'Clubs.name' => 'ASC',
                ],
            ], [
                'Cities' => $clubsTable->Cities->getTarget(),
            ])
            : [
                'limit' => 50,
                'order' => ['Clubs.pos' => 'ASC', 'Clubs.name' => 'ASC'],
            ];

        $query = $clubsTable->find()
            ->contain(['Cities', 'Countries'])
            ->where([
                'Clubs.country_id' => $countryId,
                'Clubs.visible' => true,
                'Clubs.enabled' => true,
            ]);

        if (method_exists($this, 'applyIndexSearch')) {
            $query = $this->applyIndexSearch($query, $clubsTable);
        }

        if (method_exists($this, 'resolveIndexPageForLastVisited')) {
            $redirect = $this->resolveIndexPageForLastVisited('Clubs', $query, $paginateOptions);
            if ($redirect !== null) {
                return $this->withClubBrowserCountryOnRedirect($redirect, $countryId);
            }
        }

        $clubs = $this->paginate($query, $paginateOptions);
        if (method_exists($this, 'setLastVisitedForIndex')) {
            $this->setLastVisitedForIndex('Clubs');
        }

        $this->set(compact('clubs', 'countryId', 'countryOptions', 'homeCountryId', 'myClubId'));
        $this->set('countryLabel', (string)($countryOptions[$countryId] ?? AdminCountry::label($countryId)));
        $this->set('tableSearchHidden', ['country_id' => (string)$countryId]);
    }

    /**
     * Ensure country_id stays on the current request (paginator / search forms).
     */
    protected function mergeClubBrowserCountryQuery(int $countryId): void
    {
        if ($countryId < 1) {
            return;
        }
        $queryParams = $this->getRequest()->getQueryParams();
        if (!isset($queryParams['country_id'])) {
            $queryParams['country_id'] = (string)$countryId;
            $this->setRequest($this->getRequest()->withQueryParams($queryParams));
        }
    }

    /**
     * Append country_id to a list redirect Location if missing.
     */
    protected function withClubBrowserCountryOnRedirect(Response $redirect, int $countryId): Response
    {
        if ($countryId < 1) {
            return $redirect;
        }
        $target = $redirect->getHeaderLine('Location');
        if ($target === '') {
            return $redirect;
        }
        $parts = parse_url($target);
        $q = [];
        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $q);
        }
        if (isset($q['country_id'])) {
            return $redirect;
        }
        $q['country_id'] = (string)$countryId;
        $path = ($parts['path'] ?? '') . '?' . http_build_query($q);

        return $this->redirect($path);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    protected function clubBrowserView(?string $id = null)
    {
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);
        $this->set('title', __('Club details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));

        $identity = $this->clubBrowserIdentity();
        $myClubId = $identity['club_id'];

        /** @var \App\Model\Table\ClubsTable $clubsTable */
        $clubsTable = $this->fetchTable('Clubs');
        try {
            /** @var \App\Model\Entity\Club $club */
            $club = $clubsTable->get($id, contain: ['Cities', 'Countries']);
        } catch (\Throwable $e) {
            throw new NotFoundException(__('Record not found.'));
        }

        if (!(bool)$club->visible || !(bool)$club->enabled) {
            throw new NotFoundException(__('Record not found.'));
        }

        // Only clubs in countries that appear in the browser (have clubs).
        $countryOptions = $this->clubBrowserCountryOptions();
        if (!isset($countryOptions[(int)$club->country_id])) {
            throw new NotFoundException(__('Record not found.'));
        }

        if (method_exists($this, 'rememberLastVisited')) {
            $this->rememberLastVisited('Clubs', $club->id);
        }

        $president = null;
        if (method_exists($clubsTable, 'findClubPresident')) {
            $president = $clubsTable->findClubPresident((int)$club->id);
        }

        $this->set(compact('club', 'president', 'myClubId'));
        $this->set('countryLabel', AdminCountry::label((int)$club->country_id));
        $this->set('isMyClub', $myClubId > 0 && (int)$club->id === $myClubId);
    }
}
