<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Utility\AdminCountry;
use App\Utility\AdminCountryScope;
use App\Utility\AdminLanguage;
use App\Utility\EmailTemplateService;
use App\Utility\EmailTemplateSlugs;
use Cake\Http\Response;

/**
 * Email templates — per country + language (`email_templates`).
 *
 * Unique key: (country_id, language_id, slug).
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
    protected const FILTER_LANGUAGE_SESSION = 'Admin.emailTemplatesFilterLanguageId';

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
     * Default index filter: UI locale mapped onto email-template languages
     * (e.g. en_US → en_GB). Options are only those locales — not every visible language.
     */
    protected function localLanguageId(array $languageOptions): int
    {
        $localId = EmailTemplateService::templateLanguageIdForLocale();
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
        $languageOptions = EmailTemplateService::templateLanguageOptions();
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
     * After save: show the language (+ country) of the saved row (and highlight it).
     */
    protected function redirectToEmailTemplatesIndex(int $savedId): Response
    {
        $languageId = 0;
        $countryId = 0;
        try {
            $row = $this->EmailTemplates->get($savedId);
            $languageId = (int)$row->language_id;
            $countryId = (int)$row->country_id;
        } catch (\Throwable $e) {
            $languageId = 0;
            $countryId = 0;
        }
        if ($languageId > 0) {
            $this->request->getSession()->write(self::FILTER_LANGUAGE_SESSION, $languageId);
        }

        $query = $this->getIndexState('EmailTemplates');
        $query['page'] = '1';
        $query['language_id'] = (string)$languageId;
        if ($countryId > 0) {
            $query['country_id'] = (string)$countryId;
        }

        return $this->redirect(['action' => 'index', '?' => $query]);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Email templates'));
        $this->viewBuilder()->setVar('breadcrumb', __('Email templates'));
        $this->setAccessFlags();

        $scoped = $this->beginAdminCountryScopedIndex($this->EmailTemplates);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        [$filterLanguageId, $languageOptions] = $this->resolveLanguageFilter();
        $filterLanguageLabel = $filterLanguageId > 0
            ? (string)($languageOptions[$filterLanguageId] ?? '')
            : '';

        // Keep language_id in the URL so paginator / sort / search preserve the filter.
        // Preserve country_id (already set by beginAdminCountryScopedIndex).
        if (!array_key_exists('language_id', $this->request->getQueryParams())) {
            $params = $this->request->getQueryParams();
            $params['language_id'] = (string)$filterLanguageId;
            $params['country_id'] = (string)$filterCountryId;
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
                'country_id',
                'language_id',
                'Countries.name',
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
            'Countries' => $this->EmailTemplates->Countries->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->EmailTemplates->find()->contain(['Languages', 'Countries']),
            $this->EmailTemplates,
            $filterCountryId
        );
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

        $ownScope = AdminCountryScope::scopeForTable($this->request, $this->EmailTemplates);
        if ($ownScope['countryId'] > 0) {
            $emailTemplate->country_id = $ownScope['countryId'];
        }

        $prefillSlug = trim((string)$this->request->getQuery('slug'));
        if ($prefillSlug !== '' && isset(EmailTemplateSlugs::options()[$prefillSlug])) {
            $emailTemplate->slug = $prefillSlug;
        }

        if ($this->request->is('post')) {
            $savedId = $this->saveTranslationsFromRequest($emailTemplate);
            if ($savedId !== null) {
                $this->rememberLastVisited('EmailTemplates', $savedId);
                $this->Flash->success(__('The email template has been saved.'));

                return $this->redirectToEmailTemplatesIndex($savedId);
            }
            $this->flashEntityErrors($emailTemplate);
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
        $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages', 'Countries']);
        $denied = $this->denyIfOutsideAdminCountryScope($emailTemplate);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('EmailTemplates', $emailTemplate->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $savedId = $this->saveTranslationsFromRequest($emailTemplate);
            if ($savedId !== null) {
                $this->rememberLastVisited('EmailTemplates', $savedId);
                $this->Flash->success(__('The email template has been saved.'));

                return $this->redirectToEmailTemplatesIndex($savedId);
            }
            $this->flashEntityErrors($emailTemplate);
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
        $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages', 'Countries']);
        $denied = $this->denyIfOutsideAdminCountryScope($emailTemplate);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('EmailTemplates', $emailTemplate->id);
        $this->set(compact('emailTemplate'));
        $this->setAccessFlags();
        $this->set('countryLabel', AdminCountry::label((int)$emailTemplate->country_id));
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
        $emailTemplate = $this->EmailTemplates->get($id);
        $denied = $this->denyIfOutsideAdminCountryScope($emailTemplate);
        if ($denied !== null) {
            return $denied;
        }

        return $this->deleteEntityOrFail($this->EmailTemplates, $emailTemplate);
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
            $emailTemplate = $this->EmailTemplates->get($id, contain: ['Languages', 'Countries']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        if (!AdminCountryScope::entityAllowed($emailTemplate, $this->request)) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('You are not allowed to access records from another country.'),
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
                    'country' => AdminCountry::label((int)$emailTemplate->country_id),
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
     * Save slug + shared flags + per-language text fields (scoped by country_id).
     *
     * @return int|null Primary saved row id (active / first language)
     */
    protected function saveTranslationsFromRequest(\App\Model\Entity\EmailTemplate $anchor): ?int
    {
        $data = $this->constrainAdminCountryData($this->request->getData());
        $slug = trim((string)($data['slug'] ?? $anchor->slug ?? ''));
        if ($slug === '' || !preg_match('/^[a-z0-9_]+$/', $slug)) {
            $anchor->setError('slug', __('Use lowercase letters, numbers and underscores only.'));

            return null;
        }

        $countryId = (int)($data['country_id'] ?? 0);
        if ($countryId < 1) {
            $countryId = (int)($anchor->country_id ?? 0);
        }
        if ($countryId < 1) {
            $countryId = (int)(AdminCountryScope::scopeForTable($this->request, $this->EmailTemplates)['countryId'] ?? 0);
        }
        if ($countryId < 1) {
            $anchor->setError('country_id', __('Please select a country.'));

            return null;
        }
        $data['country_id'] = $countryId;
        $anchor->country_id = $countryId;

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
                // Trumbowyg empty editor often yields <p></p> / <p><br></p>
                $bodyHtmlPlain = trim(html_entity_decode(strip_tags($bodyHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                // Skip completely empty language panes (except when editing that language's existing row).
                $existingId = (int)($rowData['id'] ?? 0);
                $isEmpty = $subject === '' && $bodyHtmlPlain === '' && $bodyText === '';
                if ($isEmpty && $existingId < 1) {
                    continue;
                }
                if ($subject === '' || $bodyHtmlPlain === '' || $bodyText === '') {
                    $connection->rollback();
                    $anchor->setError('slug', __('Please fill subject and both bodies for each language you edit.'));

                    return null;
                }

                $entity = null;
                if ($existingId > 0) {
                    try {
                        $entity = $this->EmailTemplates->get($existingId);
                        if ((int)$entity->country_id !== $countryId) {
                            $entity = null;
                        } elseif (!AdminCountryScope::entityAllowed($entity, $this->request)) {
                            $connection->rollback();
                            $anchor->setError('country_id', __('You are not allowed to access records from another country.'));

                            return null;
                        }
                    } catch (\Throwable $e) {
                        $entity = null;
                    }
                }
                if ($entity === null) {
                    $entity = $this->EmailTemplates->find()
                        ->where([
                            'EmailTemplates.country_id' => $countryId,
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
                    'country_id' => $countryId,
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
                        'country_id',
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
                    foreach ($entity->getErrors() as $field => $errs) {
                        $anchor->setError($field, $errs);
                    }

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
        $localId = EmailTemplateService::templateLanguageIdForLocale();
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
        $countryId = (int)($emailTemplate->country_id ?? 0);
        if ($countryId < 1) {
            $countryId = (int)(AdminCountryScope::scopeForTable($this->request, $this->EmailTemplates)['countryId'] ?? 0);
        }

        if ($slug !== '' && $countryId > 0) {
            $rows = $this->EmailTemplates->find()
                ->where([
                    'EmailTemplates.slug' => $slug,
                    'EmailTemplates.country_id' => $countryId,
                ])
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
            $activeLanguageId = EmailTemplateService::templateLanguageIdForLocale();
        }

        $canChange = AdminCountryScope::canChangeCountry($this->request);
        $formCountryOptions = $canChange
            ? AdminCountry::options()
            : (
                $countryId > 0
                    ? [$countryId => AdminCountry::label($countryId)]
                    : []
            );

        $this->set('slugOptions', EmailTemplateSlugs::options());
        $this->set('emailTemplateLanguageTabs', $tabs);
        $this->set('emailTemplateTranslations', $translations);
        $this->set('emailTemplateActiveLanguageId', $activeLanguageId);
        $this->set('countryOptions', $formCountryOptions);
        $this->set('canChangeCountry', $canChange);
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
