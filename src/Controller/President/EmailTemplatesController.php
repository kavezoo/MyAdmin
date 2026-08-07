<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Utility\AdminLanguage;
use App\Utility\EmailTemplateSlugs;
use Cake\Http\Response;

/**
 * Email templates — per language (`email_templates`).
 *
 * @property \App\Model\Table\EmailTemplatesTable $EmailTemplates
 */
class EmailTemplatesController extends AppController
{
    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    /**
     * Session key: Email templates index language filter.
     */
    protected const FILTER_LANGUAGE_SESSION = 'President.emailTemplatesFilterLanguageId';

    /**
     * @return void
     */
    protected function setAccessFlags(): void
    {
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', true);
    }

    /**
     * Default language for index filter / new form: current UI locale.
     */
    protected function localLanguageId(array $languageOptions): int
    {
        $localId = AdminLanguage::idForLocale(\Cake\I18n\I18n::getLocale());
        if ($localId > 0 && isset($languageOptions[$localId])) {
            return $localId;
        }

        return 0;
    }

    /**
     * Index language filter: query → session → local UI language → 0 (all).
     *
     * @return array{0: int, 1: array<int, string>}
     */
    protected function resolveLanguageFilter(): array
    {
        $languageOptions = AdminLanguage::idOptions();
        $localId = $this->localLanguageId($languageOptions);
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('language_id', $query)) {
            $raw = $query['language_id'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $languageId = (int)$raw;
            if ($languageId > 0 && !isset($languageOptions[$languageId])) {
                $languageId = $localId;
            }
            $session->write(self::FILTER_LANGUAGE_SESSION, $languageId);
        } else {
            $saved = $session->read(self::FILTER_LANGUAGE_SESSION);
            if ($saved !== null && is_numeric($saved)) {
                $languageId = (int)$saved;
                if ($languageId > 0 && !isset($languageOptions[$languageId])) {
                    $languageId = $localId;
                }
            } else {
                $languageId = $localId;
                $session->write(self::FILTER_LANGUAGE_SESSION, $languageId);
            }
        }

        return [$languageId, $languageOptions];
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Email templates'));
        $this->viewBuilder()->setVar('breadcrumb', __('Email templates'));
        $this->setAccessFlags();

        [$filterLanguageId, $languageOptions] = $this->resolveLanguageFilter();
        $filterLanguageLabel = $filterLanguageId > 0
            ? (string)($languageOptions[$filterLanguageId] ?? '')
            : '';

        // Keep language_id in the URL so paginator / sort / search preserve the filter.
        if (!array_key_exists('language_id', $this->request->getQueryParams())) {
            $params = $this->request->getQueryParams();
            $params['language_id'] = (string)$filterLanguageId;
            if (!isset($params['page'])) {
                $params['page'] = '1';
            }

            return $this->redirect(['action' => 'index', '?' => $params]);
        }

        $redirect = $this->applyIndexListState('EmailTemplates');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->EmailTemplates, [
            'sortableFields' => [
                'id',
                'name',
                'slug',
                'subject',
                'language_id',
                'Languages.code',
                'Languages.name',
                'enabled',
                'visible',
                'pos',
                'created',
                'modified',
            ],
            'order' => [
                'Languages.code' => 'ASC',
                'EmailTemplates.slug' => 'ASC',
            ],
        ], [
            'Languages' => $this->EmailTemplates->Languages->getTarget(),
        ]);

        $query = $this->EmailTemplates->find()->contain(['Languages']);
        if ($filterLanguageId > 0) {
            $query->where(['EmailTemplates.language_id' => $filterLanguageId]);
        }
        $query = $this->applyIndexSearch($query, $this->EmailTemplates);

        $redirect = $this->resolveIndexPageForLastVisited('EmailTemplates', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $emailTemplates = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('EmailTemplates');

        $this->set(compact(
            'emailTemplates',
            'filterLanguageId',
            'filterLanguageLabel',
            'languageOptions'
        ));
        $this->set('slugOptions', EmailTemplateSlugs::options());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $emailTemplate = $this->newEntityWithSchemaDefaults($this->EmailTemplates);
        $emailTemplate->enabled = true;
        $emailTemplate->visible = true;

        $prefillSlug = trim((string)$this->request->getQuery('slug'));
        if ($prefillSlug !== '' && isset(EmailTemplateSlugs::options()[$prefillSlug])) {
            $emailTemplate->slug = $prefillSlug;
        }

        if ($this->request->is('post')) {
            $savedId = $this->saveTranslationsFromRequest($emailTemplate);
            if ($savedId !== null) {
                $this->rememberLastVisited('EmailTemplates', $savedId);
                $this->Flash->success(__('The email template has been saved.'));

                return $this->redirectToIndexList('EmailTemplates');
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($emailTemplate);
        $this->set(compact('emailTemplate'));
        $this->setAccessFlags();
        $this->set('title', __('New email template'));
        $this->viewBuilder()->setVar('breadcrumb', __('Email templates'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages']);
        $this->rememberLastVisited('EmailTemplates', $emailTemplate->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $savedId = $this->saveTranslationsFromRequest($emailTemplate);
            if ($savedId !== null) {
                $this->rememberLastVisited('EmailTemplates', $savedId);
                $this->Flash->success(__('The email template has been saved.'));

                return $this->redirectToIndexList('EmailTemplates');
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($emailTemplate);
        $this->set(compact('emailTemplate'));
        $this->setAccessFlags();
        $this->set('title', __('Edit email template'));
        $this->viewBuilder()->setVar('breadcrumb', __('Email templates'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages']);
        $this->rememberLastVisited('EmailTemplates', $emailTemplate->id);
        $this->set(compact('emailTemplate'));
        $this->setAccessFlags();
        $this->set('languageLabel', AdminLanguage::labelById((int)$emailTemplate->language_id));
        $this->set('slugLabel', EmailTemplateSlugs::options()[(string)$emailTemplate->slug] ?? (string)$emailTemplate->slug);
        $this->set('title', __('Email template details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Email templates'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        return $this->deleteEntityOrFail($this->EmailTemplates, $this->EmailTemplates->get($id));
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

        try {
            $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('EmailTemplates', $emailTemplate->id);
        $slugOptions = EmailTemplateSlugs::options();

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $emailTemplate->id,
                    'language' => AdminLanguage::labelById((int)$emailTemplate->language_id),
                    'slug' => $slugOptions[(string)$emailTemplate->slug] ?? (string)$emailTemplate->slug,
                    'name' => $emailTemplate->name,
                    'subject' => $emailTemplate->subject,
                    'body_html' => $emailTemplate->body_html,
                    'body_text' => $emailTemplate->body_text,
                    'enabled' => (bool)$emailTemplate->enabled,
                    'visible' => (bool)$emailTemplate->visible,
                    'pos' => \App\Utility\LocaleNumberParser::format($emailTemplate->pos, decimals: 0),
                    'created' => $emailTemplate->created
                        ? \App\Utility\LocaleDateParser::format($emailTemplate->created, 'datetime_short')
                        : '',
                    'modified' => $emailTemplate->modified
                        ? \App\Utility\LocaleDateParser::format($emailTemplate->modified, 'datetime_short')
                        : '',
                    'can_delete' => true,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Save slug + shared flags + per-language text fields.
     *
     * @return int|null Primary saved row id (active / first language)
     */
    protected function saveTranslationsFromRequest(\App\Model\Entity\EmailTemplate $anchor): ?int
    {
        $data = $this->request->getData();
        $slug = trim((string)($data['slug'] ?? $anchor->slug ?? ''));
        $slugOptions = EmailTemplateSlugs::options();
        if ($slug === '' || !isset($slugOptions[$slug])) {
            $anchor->setError('slug', __('Select template...'));

            return null;
        }

        $enabled = !empty($data['enabled']);
        $visible = !empty($data['visible']);
        $posRaw = $data['pos'] ?? null;
        $pos = is_numeric($posRaw) ? (int)$posRaw : null;

        /** @var array<int|string, mixed> $translations */
        $translations = is_array($data['translations'] ?? null) ? $data['translations'] : [];
        $tabs = $this->buildLanguageTabs();
        if ($tabs === []) {
            return null;
        }

        $savedIds = [];
        $connection = $this->EmailTemplates->getConnection();
        try {
            $connection->begin();
            foreach ($tabs as $tab) {
                $languageId = (int)$tab['language_id'];
                $rowData = is_array($translations[$languageId] ?? null) ? $translations[$languageId] : [];
                $subject = trim((string)($rowData['subject'] ?? ''));
                $bodyHtml = trim((string)($rowData['body_html'] ?? ''));
                $bodyText = trim((string)($rowData['body_text'] ?? ''));

                // Skip completely empty language panes (except when editing that language's existing row).
                $existingId = (int)($rowData['id'] ?? 0);
                $isEmpty = $subject === '' && $bodyHtml === '' && $bodyText === '';
                if ($isEmpty && $existingId < 1) {
                    continue;
                }
                if ($subject === '' || $bodyHtml === '' || $bodyText === '') {
                    $connection->rollback();
                    $anchor->setError('slug', __('Please fill subject and both bodies for each language you edit.'));

                    return null;
                }

                $entity = null;
                if ($existingId > 0) {
                    try {
                        $entity = $this->EmailTemplates->get($existingId);
                    } catch (\Throwable $e) {
                        $entity = null;
                    }
                }
                if ($entity === null) {
                    $entity = $this->EmailTemplates->find()
                        ->where([
                            'EmailTemplates.language_id' => $languageId,
                            'EmailTemplates.slug' => $slug,
                        ])
                        ->first();
                }
                if ($entity === null) {
                    $entity = $this->newEntityWithSchemaDefaults($this->EmailTemplates);
                }

                // Admin label only (not emailed) — keep existing or locale default from seed map.
                $name = trim((string)($entity->name ?? ''));
                if ($name === '') {
                    $defaults = \App\Utility\EmailTemplateDefaults::forSlugLocale($slug, (string)$tab['locale']);
                    $name = $defaults['name'];
                }

                $payload = [
                    'language_id' => $languageId,
                    'slug' => $slug,
                    'name' => $name,
                    'subject' => $subject,
                    'body_html' => $bodyHtml,
                    'body_text' => $bodyText,
                    'enabled' => $enabled,
                    'visible' => $visible,
                ];
                if ($pos !== null && $pos > 0) {
                    $payload['pos'] = $pos;
                }

                $entity = $this->EmailTemplates->patchEntity($entity, $payload, [
                    'fields' => [
                        'language_id',
                        'slug',
                        'name',
                        'subject',
                        'body_html',
                        'body_text',
                        'enabled',
                        'visible',
                        'pos',
                    ],
                ]);
                if ($entity->getErrors() || !$this->EmailTemplates->save($entity)) {
                    $connection->rollback();

                    return null;
                }
                $savedIds[$languageId] = (int)$entity->id;
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollback();

            return null;
        }

        if ($savedIds === []) {
            return null;
        }

        $activeId = (int)$anchor->language_id;
        if ($activeId > 0 && isset($savedIds[$activeId])) {
            return $savedIds[$activeId];
        }
        $localId = AdminLanguage::idForLocale(\Cake\I18n\I18n::getLocale());
        if ($localId > 0 && isset($savedIds[$localId])) {
            return $savedIds[$localId];
        }

        return (int)reset($savedIds);
    }

    /**
     * @param \App\Model\Entity\EmailTemplate $emailTemplate
     * @return void
     */
    protected function setFormOptions(\App\Model\Entity\EmailTemplate $emailTemplate): void
    {
        $tabs = $this->buildLanguageTabs();
        $translations = [];
        $slug = (string)($emailTemplate->slug ?? '');

        if ($slug !== '') {
            $rows = $this->EmailTemplates->find()
                ->where(['EmailTemplates.slug' => $slug])
                ->all();
            foreach ($rows as $row) {
                $lid = (int)$row->language_id;
                $translations[$lid] = [
                    'id' => (int)$row->id,
                    'subject' => (string)$row->subject,
                    'body_html' => (string)$row->body_html,
                    'body_text' => (string)$row->body_text,
                ];
            }
        }

        // Prefill empty tabs from defaults when adding a known slug (optional UX).
        if ($emailTemplate->isNew() && $slug !== '') {
            foreach ($tabs as $tab) {
                $lid = (int)$tab['language_id'];
                if (isset($translations[$lid])) {
                    continue;
                }
                $defaults = \App\Utility\EmailTemplateDefaults::forSlugLocale($slug, (string)$tab['locale']);
                $translations[$lid] = [
                    'id' => null,
                    'subject' => $defaults['subject'],
                    'body_html' => $defaults['body_html'],
                    'body_text' => $defaults['body_text'],
                ];
            }
        }

        $activeLanguageId = (int)($emailTemplate->language_id ?? 0);
        if ($activeLanguageId < 1) {
            $activeLanguageId = AdminLanguage::idForLocale(\Cake\I18n\I18n::getLocale());
        }

        $this->set('slugOptions', EmailTemplateSlugs::options());
        $this->set('emailTemplateLanguageTabs', $tabs);
        $this->set('emailTemplateTranslations', $translations);
        $this->set('emailTemplateActiveLanguageId', $activeLanguageId);
    }

    /**
     * Tabs for the six seeded email languages (existing languages rows only).
     *
     * @return list<array{language_id: int, locale: string, code: string, label: string}>
     */
    protected function buildLanguageTabs(): array
    {
        $tabs = [];
        foreach (\App\Utility\EmailTemplateDefaults::locales() as $locale) {
            $languageId = AdminLanguage::idForLocale($locale);
            if ($languageId < 1) {
                continue;
            }
            $short = strtoupper(substr(str_replace('-', '_', $locale), 0, 2));
            $tabs[] = [
                'language_id' => $languageId,
                'locale' => $locale,
                'code' => $short,
                'label' => AdminLanguage::labelById($languageId),
            ];
        }

        return $tabs;
    }
}
