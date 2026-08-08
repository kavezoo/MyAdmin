<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Controller\Concerns\PanelClubBrowserTrait;
use App\Model\Table\ClubsTable;

/**
 * Read-only club directory (own country default + country filter).
 *
 * @property \App\Model\Table\ClubsTable $Clubs
 */
class ClubsController extends AppController
{
    use PanelClubBrowserTrait;

    protected const LAST_VISITED_SESSION_KEY = 'Clubpresident.lastVisited';

    protected const INDEX_STATE_SESSION_KEY = 'Clubpresident.indexState';

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
