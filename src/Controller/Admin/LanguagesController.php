<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\LanguageAccess;
use App\Utility\AdminTranslate;
use Cake\Http\Response;
use Cake\I18n\I18n;

/**
 * Languages Controller — UI locales (`languages` + Translate on name).
 *
 * Access:
 * - superuser: add, delete, full edit
 * - admin: edit visible + pos only
 *
 * @property \App\Model\Table\LanguagesTable $Languages
 */
class LanguagesController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * Session key: Languages index „visible only” filter.
     */
    protected const LANGUAGES_VISIBLE_ONLY_SESSION_KEY = 'Admin.languagesVisibleOnly';

    /**
     * Apply UI locale to Languages Translate behavior.
     *
     * @return void
     */
    protected function setTranslateLocales(): void
    {
        AdminTranslate::applyLocales(['Languages'], I18n::getLocale());
    }

    /**
     * Languages index filter: only `visible=1` rows (default) or all.
     *
     * @return bool
     */
    protected function resolveLanguagesVisibleOnly(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('visible_only', $query)) {
            $raw = $query['visible_only'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $visibleOnly = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::LANGUAGES_VISIBLE_ONLY_SESSION_KEY, $visibleOnly);

            return $visibleOnly;
        }

        $saved = $session->read(self::LANGUAGES_VISIBLE_ONLY_SESSION_KEY);
        if ($saved === null) {
            return true;
        }

        return (bool)$saved;
    }

    /**
     * Breadcrumb flags for Languages CRUD.
     *
     * @param \App\Model\Entity\Language|null $language
     * @return void
     */
    protected function setLanguageAccessFlags(?\App\Model\Entity\Language $language = null): void
    {
        $this->set('canAdd', LanguageAccess::canAdd());
        $canDelete = LanguageAccess::canDelete();
        if ($canDelete && $language !== null) {
            $canDelete = $this->Languages->canDelete($language);
        } elseif ($language === null) {
            $canDelete = false;
        }
        $this->set('canDelete', $canDelete);
        $this->set('canEditFully', LanguageAccess::canEditFully());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        if (!LanguageAccess::canAccessModule()) {
            return $this->denyWithFlashWarning(__('You are not allowed to access languages.'));
        }

        $this->set('title', __('Languages'));
        $this->viewBuilder()->setVar('breadcrumb', __('Languages'));
        $this->setLanguageAccessFlags();

        $this->setTranslateLocales();

        $languagesVisibleOnly = $this->resolveLanguagesVisibleOnly();
        $this->set(compact('languagesVisibleOnly'));

        $redirect = $this->applyIndexListState('Languages');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Languages, [
            'sortableFields' => [
                'id',
                'code',
                'name',
                'endonim_name',
                'pos',
                'visible',
                'created',
                'modified',
            ],
            'order' => [
                'Languages.pos' => 'ASC',
                'Languages.code' => 'ASC',
            ],
        ]);

        $query = $this->applyIndexSearch(
            $this->Languages->find(),
            $this->Languages
        );
        if ($languagesVisibleOnly) {
            $query->where(['Languages.visible' => true]);
        }
        $redirect = $this->resolveIndexPageForLastVisited('Languages', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $languages = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Languages');

        $deletableLanguageIds = [];
        if (LanguageAccess::canDelete()) {
            foreach ($languages as $language) {
                if ($this->Languages->canDelete($language)) {
                    $deletableLanguageIds[(int)$language->id] = true;
                }
            }
        }

        $this->set(compact('languages', 'deletableLanguageIds'));
        $this->set('canDeleteLanguage', LanguageAccess::canDelete());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        if (!LanguageAccess::canAdd()) {
            return $this->denyWithFlashWarning(__('Only a superuser can add languages.'));
        }

        $this->setTranslateLocales();
        $language = $this->newEntityWithSchemaDefaults($this->Languages);

        if ($this->request->is('post')) {
            try {
                $language = $this->Languages->patchEntity($language, $this->request->getData(), [
                    'fields' => ['code', 'name', 'endonim_name', 'visible', 'pos'],
                    'accessibleFields' => [
                        'code' => true,
                        'name' => true,
                        'endonim_name' => true,
                        'visible' => true,
                        'pos' => true,
                    ],
                ]);
                if ($this->Languages->save($language)) {
                    $this->rememberLastVisited('Languages', $language->id);
                    $this->Flash->success(__('The language has been saved.'));

                    return $this->redirectToIndexList('Languages');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('language'));
        $this->setLanguageAccessFlags($language);
        $this->set('title', __('New language'));
        $this->viewBuilder()->setVar('breadcrumb', __('Languages'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        if (!LanguageAccess::canEditMeta()) {
            return $this->denyWithFlashWarning(__('You are not allowed to edit languages.'));
        }

        $this->setTranslateLocales();
        $language = $this->Languages->get($id);
        $this->rememberLastVisited('Languages', $language->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                if (LanguageAccess::canEditFully()) {
                    $language = $this->Languages->patchEntity($language, $data, [
                        'fields' => ['code', 'name', 'endonim_name', 'visible', 'pos'],
                        'accessibleFields' => [
                            'code' => true,
                            'name' => true,
                            'endonim_name' => true,
                            'visible' => true,
                            'pos' => true,
                        ],
                    ]);
                } else {
                    $language = $this->Languages->patchEntity($language, [
                        'visible' => $data['visible'] ?? $language->visible,
                        'pos' => $data['pos'] ?? $language->pos,
                    ], [
                        'fields' => ['visible', 'pos'],
                    ]);
                }
                if ($this->Languages->save($language)) {
                    $this->rememberLastVisited('Languages', $language->id);
                    $this->Flash->success(__('The language has been saved.'));

                    return $this->redirectToIndexList('Languages');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('language'));
        $this->setLanguageAccessFlags($language);
        $this->set('title', __('Edit language'));
        $this->viewBuilder()->setVar('breadcrumb', __('Languages'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        if (!LanguageAccess::canAccessModule()) {
            return $this->denyWithFlashWarning(__('You are not allowed to access languages.'));
        }

        $this->setTranslateLocales();
        $language = $this->Languages->get($id);
        $this->rememberLastVisited('Languages', $language->id);
        $this->set(compact('language'));
        $this->setLanguageAccessFlags($language);
        $this->set('title', __('Language details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Languages'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!LanguageAccess::canDelete()) {
            return $this->denyWithFlashWarning(__('Only a superuser can delete languages.'));
        }

        return $this->deleteEntityOrFail($this->Languages, $this->Languages->get($id));
    }

    /**
     * JSON: record details for index modal.
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        if (!LanguageAccess::canAccessModule()) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('You are not allowed to access languages.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        try {
            $this->setTranslateLocales();
            $language = $this->Languages->get($id);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Languages', $language->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'endonim_name' => $language->endonim_name,
                    'visible' => (bool)$language->visible,
                    'pos' => \App\Utility\LocaleNumberParser::format($language->pos, decimals: 0),
                    'created' => $language->created ? \App\Utility\LocaleDateParser::format($language->created, 'datetime_short') : '',
                    'modified' => $language->modified ? \App\Utility\LocaleDateParser::format($language->modified, 'datetime_short') : '',
                    'can_delete' => LanguageAccess::canDelete() && $this->Languages->canDelete($language),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }
}
