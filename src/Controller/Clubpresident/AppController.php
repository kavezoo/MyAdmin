<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Controller\Concerns\IndexListCrudTrait;
use App\Controller\PanelAppController;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Shared helpers for club president controllers.
 *
 * Scope: every Users query under this prefix is limited to
 * `Users.club_id` = the logged-in user's club (never other clubs).
 */
abstract class AppController extends PanelAppController
{
    use IndexListCrudTrait;

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

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $controller = (string)$this->request->getParam('controller');
        if ($controller === '' || $controller === 'Dashboard' || $controller === 'Members') {
            return;
        }
        $this->set('indexListUrl', $this->indexListUrl($controller));
    }

    /**
     * Deny Clubpresident content when the user has no club assigned.
     * Dashboard may still render the warning; other actions redirect home.
     *
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $controller = (string)$this->request->getParam('controller');
        $action = (string)$this->request->getParam('action');
        if ($controller === 'Dashboard' && $action === 'index') {
            return null;
        }

        if ($this->presidentClubId() > 0) {
            return null;
        }

        $this->Flash->warning(__('Your account is not assigned to a club yet. Contact an administrator.'));

        return $this->redirect([
            'prefix' => 'Clubpresident',
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);
    }

    /**
     * Logged-in user's club id — always from DB (authoritative), not stale identity.
     */
    protected function presidentClubId(): int
    {
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
            ->select(['club_id'])
            ->where(['Users.id' => $userId])
            ->first();

        return $row !== null ? (int)($row->get('club_id') ?? 0) : 0;
    }

    /**
     * Restrict a Users query to the president's own club.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function scopeToPresidentClub(SelectQuery $query): SelectQuery
    {
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            return $query->where(['1 = 0']);
        }

        return $query->where(['Users.club_id' => $clubId]);
    }
}
