<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\AdminSearch;
use App\Utility\AdminTranslate;
use App\Utility\EntityFormErrors;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Shared Admin/panel index list state, search, last-visited, delete helpers.
 *
 * Override {@see indexStateSessionKey()}, {@see lastVisitedSessionKey()} per panel.
 * {@see indexListUrlFromState()} uses the current request prefix.
 */
trait IndexListCrudTrait
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    protected int $modalRelatedLimit = 20;

    protected function indexStateSessionKey(): string
    {
        return 'Admin.indexState';
    }

    protected function lastVisitedSessionKey(): string
    {
        return 'Admin.lastVisited';
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function indexPaginateOptions(array $extra = []): array
    {
        return array_merge([
            'limit' => $this->indexLimit,
            'maxLimit' => $this->indexMaxLimit,
        ], $extra);
    }

    /**
     * @param array<string, mixed> $extra
     * @param array<string, \Cake\ORM\Table> $associationTables
     * @return array<string, mixed>
     */
    protected function indexPaginateOptionsFor(Table $table, array $extra = [], array $associationTables = []): array
    {
        $opts = $this->indexPaginateOptions($extra);
        AdminTranslate::applyLocale($table);
        foreach ($associationTables as $assocTable) {
            if ($assocTable instanceof Table) {
                AdminTranslate::applyLocale($assocTable);
            }
        }

        if (!empty($opts['sortableFields']) && is_array($opts['sortableFields'])) {
            /** @var list<string> $fields */
            $fields = array_values(array_filter(
                $opts['sortableFields'],
                static fn ($f) => is_string($f) && $f !== ''
            ));
            $opts['sortableFields'] = AdminTranslate::sortableFields($table, $fields, $associationTables);
        }
        if (!empty($opts['order']) && is_array($opts['order'])) {
            /** @var array<string, string> $order */
            $order = $opts['order'];
            $opts['order'] = AdminTranslate::remapOrder($table, $order, $associationTables);
        }

        return $opts;
    }

    protected function applyIndexListState(string $model): ?Response
    {
        $qKey = AdminSearch::queryParam();
        $paramKeys = ['sort', 'direction', 'page', 'limit', $qKey];
        $query = $this->request->getQueryParams();
        $session = $this->request->getSession();
        $sessionKey = $this->indexStateSessionKey();
        $all = $session->read($sessionKey);
        if (!is_array($all)) {
            $all = [];
        }
        $saved = isset($all[$model]) && is_array($all[$model]) ? $all[$model] : [];

        if (!empty($query['clear_search'])) {
            if (isset($saved['page'])) {
                $saved['_pageBeforeSearch'] = $saved['page'];
            }
            unset($saved[$qKey]);
            $saved['_resolveLastVisitedPage'] = '1';
            unset($saved['page']);
            $all[$model] = $saved;
            $session->write($sessionKey, $all);

            $requestQuery = [];
            foreach (['sort', 'direction', 'limit'] as $key) {
                if (!empty($saved[$key])) {
                    $requestQuery[$key] = (string)$saved[$key];
                }
            }
            $this->request = $this->request->withQueryParams($requestQuery);
            $this->set('indexSearch', '');

            return null;
        }

        $hasListQuery = false;
        foreach ($paramKeys as $key) {
            if (array_key_exists($key, $query)) {
                $hasListQuery = true;
                break;
            }
        }

        if (!$hasListQuery) {
            $this->set('indexSearch', (string)($saved[$qKey] ?? ''));
            if ($saved === []) {
                return null;
            }
            $public = $this->publicIndexState($saved);
            if ($public === []) {
                return null;
            }

            return $this->redirect($this->indexListUrlFromState($model, $public));
        }

        $incomingQ = array_key_exists($qKey, $query);
        $prevQ = (string)($saved[$qKey] ?? '');
        $newQ = $incomingQ ? trim((string)$query[$qKey]) : $prevQ;

        $hasPagingMeta = array_key_exists('sort', $query)
            || array_key_exists('direction', $query)
            || array_key_exists('page', $query)
            || array_key_exists('limit', $query);
        $searchSubmit = $incomingQ && !$hasPagingMeta;

        $merged = [];
        foreach (['_pageBeforeSearch', '_resolveLastVisitedPage'] as $internal) {
            if (isset($saved[$internal])) {
                $merged[$internal] = $saved[$internal];
            }
        }

        foreach ($paramKeys as $key) {
            if (!array_key_exists($key, $query)) {
                continue;
            }
            $val = $query[$key];
            if ($key === $qKey) {
                $val = trim((string)$val);
                if ($val === '') {
                    unset($merged[$key]);
                    continue;
                }
            }
            if ($val === null || $val === '') {
                unset($merged[$key]);
                continue;
            }
            $merged[$key] = is_string($val) || is_int($val) || is_float($val)
                ? (string)$val
                : $val;
        }

        if (!array_key_exists('page', $query)) {
            $merged['page'] = '1';
        }

        if ($searchSubmit) {
            if ($newQ !== '' && $newQ !== $prevQ && isset($saved['page'])) {
                $merged['_pageBeforeSearch'] = (string)$saved['page'];
            }
            if ($newQ === '') {
                unset($merged['_pageBeforeSearch']);
            }
            $merged['page'] = '1';
        } else {
            $prevPage = (string)($saved['page'] ?? '');
            $newPage = (string)($merged['page'] ?? '1');
            // Resolving last-visited page must not wipe the highlight on the redirect hop.
            if ($prevPage !== $newPage && empty($merged['_resolveLastVisitedPage'])) {
                $this->clearLastVisited($model);
            }
            unset($merged['_pageBeforeSearch']);
        }

        $all[$model] = $merged;
        $session->write($sessionKey, $all);

        $requestQuery = $this->publicIndexState($merged);
        if ($incomingQ && $newQ === '') {
            unset($requestQuery[$qKey]);
        }

        $this->request = $this->request->withQueryParams($requestQuery);
        $this->set('indexSearch', (string)($requestQuery[$qKey] ?? ''));

        if ($this->indexUrlNeedsCanonicalRedirect($query, $requestQuery)) {
            return $this->redirect($this->indexListUrlFromState($model, $requestQuery));
        }

        return null;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, string>
     */
    protected function publicIndexState(array $state): array
    {
        $qKey = AdminSearch::queryParam();
        $out = [];
        foreach (['sort', 'direction', 'page', 'limit', $qKey] as $key) {
            if (!array_key_exists($key, $state)) {
                continue;
            }
            $val = $state[$key];
            if ($val === null || $val === '') {
                continue;
            }
            if (is_string($val) || is_int($val) || is_float($val)) {
                $out[$key] = (string)$val;
            }
        }
        if (!isset($out['page']) || $out['page'] === '' || $out['page'] === '0') {
            $out['page'] = '1';
        }

        return $out;
    }

    /**
     * @param array<string, string> $state
     * @return array<string, mixed>
     */
    protected function indexListUrlFromState(string $model, array $state): array
    {
        $prefix = (string)$this->request->getParam('prefix');
        if ($prefix === '') {
            $prefix = 'Admin';
        }
        $url = ['prefix' => $prefix, 'controller' => $model, 'action' => 'index'];
        if ($state !== []) {
            $url['?'] = $state;
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $browserQuery
     * @param array<string, string> $canonicalQuery
     */
    protected function indexUrlNeedsCanonicalRedirect(array $browserQuery, array $canonicalQuery): bool
    {
        if (!empty($browserQuery['clear_search'])) {
            return true;
        }

        $qKey = AdminSearch::queryParam();
        foreach (['sort', 'direction', 'page', 'limit', $qKey] as $key) {
            $a = array_key_exists($key, $browserQuery) && $browserQuery[$key] !== null && $browserQuery[$key] !== ''
                ? (string)$browserQuery[$key]
                : null;
            $b = $canonicalQuery[$key] ?? null;
            if ($a === $b) {
                continue;
            }
            if (($a === null || $a === '') && ($b === null || $b === '')) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param array<string, mixed> $paginateOptions
     */
    protected function resolveIndexPageForLastVisited(string $model, SelectQuery $query, array $paginateOptions = []): ?Response
    {
        $session = $this->request->getSession();
        $sessionKey = $this->indexStateSessionKey();
        $all = $session->read($sessionKey);
        if (!is_array($all) || empty($all[$model]['_resolveLastVisitedPage'])) {
            return null;
        }

        $saved = is_array($all[$model]) ? $all[$model] : [];

        $limit = (int)($this->request->getQuery('limit') ?: ($saved['limit'] ?? $this->indexLimit));
        if ($limit < 1) {
            $limit = $this->indexLimit;
        }
        $limit = min($limit, $this->indexMaxLimit);

        $lastId = $this->getLastVisitedId($model);
        $page = 1;
        if ($lastId !== null) {
            $page = $this->findRecordPageNumber($query, $lastId, $limit, $paginateOptions);
        } elseif (!empty($saved['_pageBeforeSearch']) && ctype_digit((string)$saved['_pageBeforeSearch'])) {
            $page = max(1, (int)$saved['_pageBeforeSearch']);
        }
        unset($saved['_pageBeforeSearch']);

        $currentPage = max(1, (int)($this->request->getQuery('page') ?: ($saved['page'] ?? 1)));
        $saved['page'] = (string)$page;

        if ($currentPage === $page) {
            unset($saved['_resolveLastVisitedPage']);
            $all[$model] = $saved;
            $session->write($sessionKey, $all);
            $requestQuery = $this->publicIndexState($saved);
            $this->request = $this->request->withQueryParams($requestQuery);
            $this->set('indexSearch', (string)($requestQuery[AdminSearch::queryParam()] ?? ''));

            return null;
        }

        // Keep flag across the page redirect so applyIndexListState does not clear last-visited.
        $saved['_resolveLastVisitedPage'] = '1';
        $all[$model] = $saved;
        $session->write($sessionKey, $all);

        $requestQuery = $this->publicIndexState($saved);
        $this->request = $this->request->withQueryParams($requestQuery);
        $this->set('indexSearch', (string)($requestQuery[AdminSearch::queryParam()] ?? ''));

        return $this->redirect($this->indexListUrlFromState($model, $requestQuery));
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param array<string, mixed> $paginateOptions
     * @param int|string $recordId Numeric PK or UUID
     */
    protected function findRecordPageNumber(
        SelectQuery $query,
        int|string $recordId,
        int $limit,
        array $paginateOptions = [],
    ): int {
        $recordId = $this->normalizeLastVisitedId($recordId);
        if ($limit < 1 || $recordId === null) {
            return 1;
        }

        $table = $query->getRepository();
        $alias = $table->getAlias();
        $pk = $table->getPrimaryKey();
        if (!is_string($pk) || $pk === '') {
            return 1;
        }

        try {
            $ordered = clone $query;
            $ordered
                ->select([$alias . '.' . $pk], true)
                ->distinct([$alias . '.' . $pk])
                ->enableHydration(false)
                ->limit(null)
                ->offset(null);

            $sort = $this->request->getQuery('sort');
            $direction = strtolower((string)$this->request->getQuery('direction')) === 'desc' ? 'DESC' : 'ASC';
            if (is_string($sort) && $sort !== '') {
                $ordered->orderBy([$sort => $direction], true);
            } elseif (!empty($paginateOptions['order']) && is_array($paginateOptions['order'])) {
                $ordered->orderBy($paginateOptions['order'], true);
            }

            $ids = [];
            foreach ($ordered->all() as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = $row[$pk] ?? $row[$alias . '__' . $pk] ?? null;
                $normalized = $this->normalizeLastVisitedId($id);
                if ($normalized !== null) {
                    $ids[] = $normalized;
                }
            }

            $needle = $recordId;
            $position = array_search($needle, $ids, true);
            if ($position === false) {
                // Loose compare for int/string numeric ids stored differently.
                foreach ($ids as $i => $candidate) {
                    if ((string)$candidate === (string)$needle) {
                        $position = $i;
                        break;
                    }
                }
            }
            if ($position === false) {
                return 1;
            }

            return (int)floor((int)$position / $limit) + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function getIndexState(string $model): array
    {
        $all = $this->request->getSession()->read($this->indexStateSessionKey());
        if (!is_array($all) || !isset($all[$model]) || !is_array($all[$model])) {
            return [];
        }

        return $this->publicIndexState($all[$model]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function indexListUrl(string $model): array
    {
        return $this->indexListUrlFromState($model, $this->getIndexState($model));
    }

    /**
     * After add/edit/view handling: go to the index page that contains the last-visited row.
     */
    protected function redirectToIndexList(?string $model = null): Response
    {
        $model = $model ?: (string)$this->request->getParam('controller');
        if ($model !== '' && $this->getLastVisitedId($model) !== null) {
            $session = $this->request->getSession();
            $sessionKey = $this->indexStateSessionKey();
            $all = $session->read($sessionKey);
            if (!is_array($all)) {
                $all = [];
            }
            $saved = is_array($all[$model] ?? null) ? $all[$model] : [];
            $saved['_resolveLastVisitedPage'] = '1';
            $all[$model] = $saved;
            $session->write($sessionKey, $all);
        }

        return $this->redirect($this->indexListUrl($model));
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function applyIndexSearch(SelectQuery $query, Table $table): SelectQuery
    {
        $term = trim((string)$this->request->getQuery(AdminSearch::queryParam()));
        if ($term === '') {
            return $query;
        }

        return AdminSearch::applyToQuery($query, $table, $term);
    }

    /**
     * Remember last viewed / edited / saved record (int PK or UUID).
     */
    protected function rememberLastVisited(string $model, int|string|null $id): void
    {
        if ($model === '') {
            return;
        }
        $normalized = $this->normalizeLastVisitedId($id);
        if ($normalized === null) {
            return;
        }

        $session = $this->request->getSession();
        $sessionKey = $this->lastVisitedSessionKey();
        $all = $session->read($sessionKey);
        if (!is_array($all)) {
            $all = [];
        }

        $all[$model] = $normalized;
        $all['_last'] = [
            'model' => $model,
            'id' => $normalized,
        ];
        $session->write($sessionKey, $all);
    }

    protected function clearLastVisited(string $model): void
    {
        if ($model === '') {
            return;
        }

        $session = $this->request->getSession();
        $sessionKey = $this->lastVisitedSessionKey();
        $all = $session->read($sessionKey);
        if (!is_array($all)) {
            return;
        }

        unset($all[$model]);
        if (
            isset($all['_last']) && is_array($all['_last'])
            && ($all['_last']['model'] ?? null) === $model
        ) {
            unset($all['_last']);
        }
        $session->write($sessionKey, $all);
    }

    /**
     * @return int|string|null
     */
    protected function getLastVisitedId(string $model): int|string|null
    {
        $all = $this->request->getSession()->read($this->lastVisitedSessionKey());
        if (!is_array($all) || !isset($all[$model])) {
            return null;
        }

        return $this->normalizeLastVisitedId($all[$model]);
    }

    /**
     * Normalize session / comparison id: positive int, or non-empty string (UUID).
     *
     * @return int|string|null
     */
    protected function normalizeLastVisitedId(mixed $id): int|string|null
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (is_int($id)) {
            return $id > 0 ? $id : null;
        }
        if (is_float($id)) {
            $asInt = (int)$id;

            return $asInt > 0 ? $asInt : null;
        }
        if (!is_string($id) && !is_numeric($id)) {
            return null;
        }
        $asString = trim((string)$id);
        if ($asString === '') {
            return null;
        }
        // Pure integer PK (avoid casting UUIDs — (int)"597cc7…" === 597).
        if (ctype_digit($asString)) {
            $asInt = (int)$asString;

            return $asInt > 0 ? $asInt : null;
        }

        return $asString;
    }

    protected function setLastVisitedForIndex(string $model): void
    {
        $this->set('lastVisitedId', $this->getLastVisitedId($model));
    }

    protected function deleteEntityOrFail(Table $table, EntityInterface $entity): ?Response
    {
        $model = $table->getAlias();
        $pk = $table->getPrimaryKey();
        $deletedId = is_string($pk) ? $entity->get($pk) : null;

        if ($table->delete($entity)) {
            if (
                $deletedId !== null
                && (string)$this->getLastVisitedId($model) === (string)$deletedId
            ) {
                $this->clearLastVisited($model);
            }
            $this->Flash->success(__('The record has been deleted.'));
        } else {
            $errors = $entity->getError('_delete');
            $message = (is_array($errors) && $errors !== [])
                ? (string)reset($errors)
                : __('The record could not be deleted. Please try again.');
            $this->Flash->error($message);
        }

        return $this->redirectToIndexList($model);
    }

    protected function newEntityWithSchemaDefaults(Table $table): EntityInterface
    {
        $entity = $table->newEmptyEntity();
        if (method_exists($table, 'applySchemaDefaults')) {
            /** @var callable $fn */
            $fn = [$table, 'applySchemaDefaults'];
            $fn($entity);
        }

        return $entity;
    }

    /**
     * Flash validation / rule errors so the user sees *why* save failed.
     *
     * @param \Cake\Datasource\EntityInterface|array<string, mixed> $entityOrErrors
     * @param array<string, string> $fieldLabels Optional field => human label
     */
    protected function flashEntityErrors(
        EntityInterface|array $entityOrErrors,
        ?string $fallback = null,
        array $fieldLabels = [],
    ): void {
        $this->Flash->error(EntityFormErrors::flashText($entityOrErrors, $fieldLabels, $fallback));
    }

    protected function containRelatedForModal(string $alias): \Closure
    {
        $limit = $this->modalRelatedLimit;

        return function ($q) use ($alias, $limit) {
            return $q
                ->orderBy([$alias . '.modified' => 'DESC'])
                ->limit($limit);
        };
    }

    /**
     * @param iterable<\Cake\Datasource\EntityInterface>|null $entities
     * @return list<array{id: mixed, name: string}>
     */
    protected function relatedNameLinksForModal(?iterable $entities): array
    {
        $items = [];
        foreach ($entities ?? [] as $entity) {
            $items[] = [
                'id' => $entity->get('id'),
                'name' => (string)$entity->get('name'),
            ];
        }
        usort($items, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    protected function setCanDeleteFlag(Table $table, EntityInterface $entity): void
    {
        $canDelete = true;
        if (method_exists($table, 'canDelete')) {
            /** @var callable $fn */
            $fn = [$table, 'canDelete'];
            $canDelete = (bool)$fn($entity);
        }
        $this->set('canDelete', $canDelete);
    }
}
