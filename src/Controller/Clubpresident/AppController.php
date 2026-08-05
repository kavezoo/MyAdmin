<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Controller\PanelAppController;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;

/**
 * Shared helpers for club president controllers.
 */
abstract class AppController extends PanelAppController
{
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

    protected function presidentClubId(): int
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return 0;
        }

        $clubId = 0;
        if (method_exists($identity, 'get')) {
            $clubId = (int)($identity->get('club_id') ?? 0);
        }
        if ($clubId > 0) {
            return $clubId;
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
}
