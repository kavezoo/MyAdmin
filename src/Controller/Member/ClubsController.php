<?php
declare(strict_types=1);

namespace App\Controller\Member;

use App\Controller\Concerns\IndexListCrudTrait;
use App\Controller\Concerns\PanelClubBrowserTrait;
use App\Model\Table\ClubsTable;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Event\EventInterface;

/**
 * Read-only club directory (own country default + country filter).
 *
 * @property \App\Model\Table\ClubsTable $Clubs
 */
class ClubsController extends AppController
{
    use IndexListCrudTrait;
    use PanelClubBrowserTrait;

    protected const LAST_VISITED_SESSION_KEY = 'Member.lastVisited';

    protected const INDEX_STATE_SESSION_KEY = 'Member.indexState';

    protected ClubsTable $Clubs;

    protected function indexStateSessionKey(): string
    {
        return self::INDEX_STATE_SESSION_KEY;
    }

    protected function lastVisitedSessionKey(): string
    {
        return self::LAST_VISITED_SESSION_KEY;
    }

    public function initialize(): void
    {
        parent::initialize();
        $this->Clubs = $this->fetchTable('Clubs');
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('indexListUrl', $this->indexListUrl('Clubs'));
    }

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
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        return $this->clubBrowserIndex();
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        return $this->clubBrowserView($id);
    }
}
