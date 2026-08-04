<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use App\Utility\AdminCountry;
use App\Utility\AdminSearch;
use App\Utility\AdminTranslate;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Admin Application Controller
 *
 * Shared base for controllers under the Admin prefix.
 * Locale: login-screen language (session/cookie), then Users.country_id → Countries.locale,
 * else App.adminLocale. No language switcher in the UI.
 */
class AppController extends BaseController
{
    /**
     * Index lista: alapértelmezett sor / oldal (`?limit=` nélkül).
     * Child controller felülírhatja az osztály tetején.
     */
    protected int $indexLimit = 100;

    /**
     * Index lista: maximális sor / oldal.
     * Ha valaki a URL-ben nagyobb `?limit=`-et próbál (hack), ennél több soha nem jelenik meg.
     */
    protected int $indexMaxLimit = 1000;

    /**
     * Session key: last worked-on Admin records (per Table alias + global `_last`).
     * Structure: ['Samples' => 12, 'Cities' => 3, '_last' => ['model' => 'Samples', 'id' => 12]]
     */
    protected const LAST_VISITED_SESSION_KEY = 'Admin.lastVisited';

    /**
     * Session: per-model index list state (sort, direction, page, q, limit).
     * Structure: Admin.indexState[Samples] = ['sort' => …, 'direction' => …, 'page' => 2, 'q' => '…']
     */
    protected const INDEX_STATE_SESSION_KEY = 'Admin.indexState';

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $request = $this->getRequest();
        $locale = BrowserLocale::forLoggedIn($request, $request->getAttribute('identity'));
        I18n::setLocale($locale);
        Configure::write('App.defaultLocale', $locale);
        BrowserLocale::remember($request, $locale);
        AdminCountry::applyTranslateLocale($locale);

        $this->viewBuilder()->setLayout('admin');
        $this->set('panelPrefix', 'Admin');
        $this->set('panelBrand', __('Admin'));
        $this->set('panelSidebar', 'admin/sidebar');
        $this->set('panelHomeUrl', [
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);
    }

    /**
     * Expose Back-to-list URL with restored index query (sort / page / search).
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $controller = (string)$this->request->getParam('controller');
        if ($controller === '' || in_array($controller, ['Dashboard', 'Search'], true)) {
            return;
        }
        $this->set('indexListUrl', $this->indexListUrl($controller));
    }

    /**
     * Paginator options for Admin index lists (limit + anti-abuse maxLimit).
     *
     * @param array<string, mixed> $extra e.g. sortableFields
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
     * Like {@see indexPaginateOptions()} but remaps Translate fields for UI-locale ORDER BY.
     *
 * URL sort keys stay logical (`name`, `Continents.name`); SQL uses
 * `Alias_field_translation.content` then `Alias.field` (no COALESCE — Paginator-safe).
     *
     * @param array<string, mixed> $extra Must include `sortableFields` (list of logical keys)
     * @param array<string, \Cake\ORM\Table> $associationTables e.g. ['Continents' => $continentsTable]
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

    /**
     * Restore + persist index list query (sort, direction, page, q, limit) in session.
     * Call at the start of every Admin `index()` before building the query.
     *
     * - Bare `/admin/samples` → redirect to last saved bookmarkable URL (incl. page).
     * - Explicit query = source of truth; missing `page` means page 1 (Cake used to omit it).
     * - Search form (`q` only, no page/sort/…) → page 1.
     * - Paginator / sort / any list query with page → clear last-visited; session page updated to URL.
     * - `clear_search=1` → drop `q`, flag last-visited page resolve.
     *
     * @param string $model Table / controller alias (Samples, …)
     * @return \Cake\Http\Response|null Redirect when canonical URL differs
     */
    protected function applyIndexListState(string $model): ?Response
    {
        $qKey = AdminSearch::queryParam();
        $paramKeys = ['sort', 'direction', 'page', 'limit', $qKey];
        $query = $this->request->getQueryParams();
        $session = $this->request->getSession();
        $all = $session->read(self::INDEX_STATE_SESSION_KEY);
        if (!is_array($all)) {
            $all = [];
        }
        $saved = isset($all[$model]) && is_array($all[$model]) ? $all[$model] : [];

        // Clear search: drop q; page jumps to last-visited after the unfiltered query is built
        if (!empty($query['clear_search'])) {
            if (isset($saved['page'])) {
                $saved['_pageBeforeSearch'] = $saved['page'];
            }
            unset($saved[$qKey]);
            $saved['_resolveLastVisitedPage'] = '1';
            unset($saved['page']);
            $all[$model] = $saved;
            $session->write(self::INDEX_STATE_SESSION_KEY, $all);

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

        // Bare index URL → bookmarkable redirect to last state
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
        // Search form posts only `q` — paginator / sort links keep other params (+ page).
        $searchSubmit = $incomingQ && !$hasPagingMeta;

        $merged = [];
        foreach (['_pageBeforeSearch'] as $internal) {
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

        // Explicit list URL without page → page 1 (first-page links / search)
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
            // Paginator / sort: session page follows URL; clear last-visited only when page changes
            $prevPage = (string)($saved['page'] ?? '');
            $newPage = (string)($merged['page'] ?? '1');
            if ($prevPage !== $newPage) {
                $this->clearLastVisited($model);
            }
            unset($merged['_pageBeforeSearch']);
        }

        $all[$model] = $merged;
        $session->write(self::INDEX_STATE_SESSION_KEY, $all);

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
     * Public query keys from saved/merged index state (always includes page).
     *
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
     * @param string $model
     * @param array<string, string> $state
     * @return array<string, mixed>
     */
    protected function indexListUrlFromState(string $model, array $state): array
    {
        $url = ['prefix' => 'Admin', 'controller' => $model, 'action' => 'index'];
        if ($state !== []) {
            $url['?'] = $state;
        }

        return $url;
    }

    /**
     * True when the browser query should be rewritten to the bookmarkable canonical form.
     *
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
     * After clear_search: set `page` so the last-visited record is on the current page
     * (unfiltered list, current sort). Call after building the index query, before paginate().
     *
     * @param string $model
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param array<string, mixed> $paginateOptions Same options passed to paginate() (for default order)
     * @return \Cake\Http\Response|null Canonical redirect after page is resolved
     */
    protected function resolveIndexPageForLastVisited(string $model, SelectQuery $query, array $paginateOptions = []): ?Response
    {
        $session = $this->request->getSession();
        $all = $session->read(self::INDEX_STATE_SESSION_KEY);
        if (!is_array($all) || empty($all[$model]['_resolveLastVisitedPage'])) {
            return null;
        }

        $saved = is_array($all[$model]) ? $all[$model] : [];
        unset($saved['_resolveLastVisitedPage']);

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

        $saved['page'] = (string)$page;
        $all[$model] = $saved;
        $session->write(self::INDEX_STATE_SESSION_KEY, $all);

        $requestQuery = $this->publicIndexState($saved);
        $this->request = $this->request->withQueryParams($requestQuery);
        $this->set('indexSearch', '');

        return $this->redirect($this->indexListUrlFromState($model, $requestQuery));
    }

    /**
     * 1-based page index of $recordId in $query's sort order (no search filter).
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param array<string, mixed> $paginateOptions
     */
    protected function findRecordPageNumber(
        SelectQuery $query,
        int $recordId,
        int $limit,
        array $paginateOptions = [],
    ): int {
        if ($limit < 1 || $recordId < 1) {
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
                if ($id !== null) {
                    $ids[] = (int)$id;
                }
            }

            $position = array_search($recordId, $ids, true);
            if ($position === false) {
                return 1;
            }

            return (int)floor($position / $limit) + 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * @param string $model
     * @return array<string, string>
     */
    protected function getIndexState(string $model): array
    {
        $all = $this->request->getSession()->read(self::INDEX_STATE_SESSION_KEY);
        if (!is_array($all) || !isset($all[$model]) || !is_array($all[$model])) {
            return [];
        }

        return $this->publicIndexState($all[$model]);
    }

    /**
     * URL array for this model's index with saved query state (always bookmarkable, incl. page).
     *
     * @param string $model
     * @return array<string, mixed>
     */
    protected function indexListUrl(string $model): array
    {
        return $this->indexListUrlFromState($model, $this->getIndexState($model));
    }

    /**
     * Redirect to index with restored sort / page / search.
     *
     * @param string|null $model Defaults to current controller name
     * @return \Cake\Http\Response
     */
    protected function redirectToIndexList(?string $model = null): Response
    {
        $model = $model ?: (string)$this->request->getParam('controller');

        return $this->redirect($this->indexListUrl($model));
    }

    /**
     * Apply configured text-field search (`q`) to an index query.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @param \Cake\ORM\Table $table
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
     * Remember the record the user last viewed / opened for edit / created.
     *
     * @param string $model Table alias (Samples, Parents, Cities, …)
     * @param int|string|null $id
     * @return void
     */
    protected function rememberLastVisited(string $model, int|string|null $id): void
    {
        if ($model === '' || $id === null || $id === '') {
            return;
        }
        $id = (int)$id;
        if ($id < 1) {
            return;
        }

        $session = $this->request->getSession();
        $all = $session->read(self::LAST_VISITED_SESSION_KEY);
        if (!is_array($all)) {
            $all = [];
        }

        $all[$model] = $id;
        $all['_last'] = [
            'model' => $model,
            'id' => $id,
        ];
        $session->write(self::LAST_VISITED_SESSION_KEY, $all);
    }

    /**
     * Drop last-visited for a model (e.g. after paginator click).
     *
     * @param string $model
     * @return void
     */
    protected function clearLastVisited(string $model): void
    {
        if ($model === '') {
            return;
        }

        $session = $this->request->getSession();
        $all = $session->read(self::LAST_VISITED_SESSION_KEY);
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
        $session->write(self::LAST_VISITED_SESSION_KEY, $all);
    }

    /**
     * Last visited id for this model (index highlight), or null.
     *
     * @param string $model
     * @return int|null
     */
    protected function getLastVisitedId(string $model): ?int
    {
        $all = $this->request->getSession()->read(self::LAST_VISITED_SESSION_KEY);
        if (!is_array($all) || !isset($all[$model])) {
            return null;
        }
        $id = (int)$all[$model];

        return $id > 0 ? $id : null;
    }

    /**
     * Set `$lastVisitedId` for index templates (`last-visited` CSS class).
     *
     * @param string $model
     * @return void
     */
    protected function setLastVisitedForIndex(string $model): void
    {
        $this->set('lastVisitedId', $this->getLastVisitedId($model));
    }

    /**
     * Flash üzenet SweetAlert2 modallal (egyszerre egy; több sorban).
     *
     * Alapértelmezett Flash → Simple Notify toast (több is lehet).
     * SWAL: $this->flashSwal('success', __('…'));
     *
     * @param string $type success|error|warning|info|default
     * @param string $message
     * @param array<string, mixed> $options Flash options (key, params, …)
     * @return void
     */
    protected function flashSwal(string $type, string $message, array $options = []): void
    {
        $map = [
            'success' => 'flash/success_swal',
            'error' => 'flash/error_swal',
            'warning' => 'flash/warning_swal',
            'info' => 'flash/info_swal',
            'default' => 'flash/default_swal',
        ];
        $options['element'] = $map[$type] ?? $map['default'];
        if (in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $this->Flash->{$type}($message, $options);

            return;
        }
        $this->Flash->set($message, $options);
    }

    /**
     * Delete entity and set Flash from model `_delete` error when blocked by children.
     *
     * @param \Cake\ORM\Table $table
     * @param \Cake\Datasource\EntityInterface $entity
     * @return \Cake\Http\Response|null
     */
    protected function deleteEntityOrFail(Table $table, EntityInterface $entity): ?Response
    {
        if ($table->delete($entity)) {
            $this->Flash->success(__('The record has been deleted.'));
        } else {
            $errors = $entity->getError('_delete');
            $message = (is_array($errors) && $errors !== [])
                ? (string)reset($errors)
                : __('The record could not be deleted. Please try again.');
            $this->Flash->error($message);
        }

        return $this->redirectToIndexList();
    }

    /**
     * New empty entity with DB column defaults applied (add form display).
     * Does not invent PHP fallbacks — values come from the Table schema.
     * Required NOT NULL columns without a DEFAULT (e.g. *_count) stay null
     * until the controller / request sets them.
     *
     * @param \Cake\ORM\Table $table
     * @return \Cake\Datasource\EntityInterface
     */
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
     * Modal kapcsolt névlista: max. ennyi gyerek (utoljára módosítottak).
     */
    protected int $modalRelatedLimit = 20;

    /**
     * Contain callback: utoljára módosított N rekord (modified DESC + limit).
     * A megjelenítési ABC sorrendet {@see relatedNameLinksForModal()} adja.
     *
     * @param string $alias Association alias (Samples, Cities, …)
     * @return \Closure
     */
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
     * Modal JSON: [{id, name}, …] — a containből kapott (max. N) entitások ABC (name ASC) sorrendben.
     *
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

    /**
     * Language tabs for translatable Admin forms.
     * Source: active country’s `country_visibilities` (own + additional languages).
     *
     * @return void
     */
    protected function setFormLanguageTabs(): void
    {
        $tabs = \App\Utility\FormLanguages::tabs();
        $this->set('formLanguageTabs', $tabs);
        $this->set('formDefaultLocale', \App\Utility\FormLanguages::defaultLocaleForForm());
    }

    /**
     * Load entity with all Translate EAV rows (edit form).
     * Root fields use the form default locale (own language, or en_GB when that tab exists).
     *
     * @param \Cake\ORM\Table $table
     * @param mixed $id
     * @param array<string, mixed>|list<string> $contain
     * @return \Cake\Datasource\EntityInterface
     */
    protected function getWithTranslations(Table $table, mixed $id, array $contain = []): EntityInterface
    {
        if (!$table->hasBehavior('Translate')) {
            return $table->get($id, contain: $contain);
        }

        $defaultLocale = \App\Utility\FormLanguages::defaultLocaleForForm();
        $table->getBehavior('Translate')->setLocale($defaultLocale);

        $pk = $table->aliasField($table->getPrimaryKey());
        $query = $table->find('translations')->where([$pk => $id]);
        if ($contain !== []) {
            $query->contain($contain);
        }

        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $query->firstOrFail();

        return $entity;
    }

    /**
     * Expose canDelete for breadcrumb / view (Tables using PreventsDeleteWithChildrenTrait).
     *
     * @param \Cake\ORM\Table $table
     * @param \Cake\Datasource\EntityInterface $entity
     * @return void
     */
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
