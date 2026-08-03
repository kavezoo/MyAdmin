<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Utility\AdminSearch;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;

/**
 * Global Admin search (header search field).
 */
class SearchController extends AppController
{
    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Search'));
        $this->viewBuilder()->setVar('breadcrumb', __('Search'));

        $q = trim((string)$this->request->getQuery(AdminSearch::queryParam()));
        $all = $q !== '' ? AdminSearch::searchAll($q) : [];
        $paginated = $this->paginateSearchResults($all);

        $this->set('q', $q);
        $this->set('results', iterator_to_array($paginated->items()));
        $this->set('resultsPaginated', $paginated);
    }

    /**
     * Slice combined search hits and build a Cake PaginatedResultSet for PaginatorHelper.
     *
     * @param list<array<string, mixed>> $all
     * @return \Cake\Datasource\Paging\PaginatedResultSet
     */
    protected function paginateSearchResults(array $all): PaginatedResultSet
    {
        $perPage = AdminSearch::globalPageLimit();
        $total = count($all);
        $pageCount = $total > 0 ? (int)max(1, (int)ceil($total / $perPage)) : 1;
        $page = max(1, (int)$this->request->getQuery('page', 1));
        if ($page > $pageCount) {
            $page = $pageCount;
        }

        $offset = ($page - 1) * $perPage;
        $slice = $total > 0 ? array_slice($all, $offset, $perPage) : [];
        $count = count($slice);
        $start = $count > 0 ? $offset + 1 : 0;
        $end = $count > 0 ? $offset + $count : 0;

        return new PaginatedResultSet(new ArrayIterator($slice), [
            'count' => $count,
            'totalCount' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'start' => $start,
            'end' => $end,
            'hasPrevPage' => $page > 1,
            'hasNextPage' => $page < $pageCount && $total > 0,
            'requestedPage' => $page,
        ]);
    }
}
