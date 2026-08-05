<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Auth\CurrentUser;
use App\Controller\Concerns\IndexListCrudTrait;
use App\Controller\PanelAppController;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Event\EventInterface;

/**
 * Shared helpers for president / vice president controllers.
 */
abstract class AppController extends PanelAppController
{
    use IndexListCrudTrait;

    protected const LAST_VISITED_SESSION_KEY = 'President.lastVisited';

    protected const INDEX_STATE_SESSION_KEY = 'President.indexState';

    protected function indexStateSessionKey(): string
    {
        return self::INDEX_STATE_SESSION_KEY;
    }

    protected function lastVisitedSessionKey(): string
    {
        return self::LAST_VISITED_SESSION_KEY;
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $controller = (string)$this->request->getParam('controller');
        if ($controller === '' || $controller === 'Dashboard') {
            return;
        }
        $this->set('indexListUrl', $this->indexListUrl($controller));
    }

    /**
     * Empty paginated set for index_pagination (CakePHP 5 PaginatorHelper).
     */
    protected function emptyPaginated(int $limit = 50): PaginatedResultSet
    {
        return new PaginatedResultSet(new ArrayIterator([]), [
            'count' => 0,
            'totalCount' => 0,
            'perPage' => $limit,
            'currentPage' => 1,
            'pageCount' => 1,
            'start' => 0,
            'end' => 0,
            'hasPrevPage' => false,
            'hasNextPage' => false,
            'requestedPage' => 1,
        ]);
    }

    protected function officerCountryId(): int
    {
        $countryId = CurrentUser::countryId($this->getRequest());
        if ($countryId > 0) {
            return $countryId;
        }

        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return 0;
        }

        $userId = '';
        if (method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        } elseif (method_exists($identity, 'get')) {
            $userId = (string)($identity->get('id') ?? '');
        }
        if ($userId === '') {
            return 0;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $row = $users->find()
            ->select(['country_id'])
            ->where(['Users.id' => $userId])
            ->first();

        return $row !== null ? (int)($row->get('country_id') ?? 0) : 0;
    }
}
