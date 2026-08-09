<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\CompetitionTextTemplate;
use App\Model\Table\CompetitionTextTemplatesTable;
use App\Utility\AdminCountry;
use App\Utility\AdminCountryScope;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionTextRender;
use App\Utility\FormLanguages;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Response;
use Throwable;

/**
 * Global Admin CRUD for country-scoped competition text templates.
 *
 * @property \App\Model\Table\CompetitionTextTemplatesTable $CompetitionTextTemplates
 */
class CompetitionTextTemplatesController extends AppController
{
    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    protected CompetitionTextTemplatesTable $CompetitionTextTemplates;

    /**
     * @var list<string>
     */
    protected const FORM_FIELDS = [
        'country_id',
        'label',
        'enabled',
        'description',
        'visible',
        'pos',
    ];

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->CompetitionTextTemplates = $this->fetchTable('CompetitionTextTemplates');
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Competition text templates'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));

        $scoped = $this->beginAdminCountryScopedIndex($this->CompetitionTextTemplates);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('CompetitionTextTemplates');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->CompetitionTextTemplates, [
            'sortableFields' => [
                'id',
                'label',
                'Countries.name',
                'enabled',
                'visible',
                'pos',
                'created',
                'modified',
            ],
            'order' => [
                'CompetitionTextTemplates.pos' => 'ASC',
                'CompetitionTextTemplates.label' => 'ASC',
            ],
        ], [
            'Countries' => $this->CompetitionTextTemplates->Countries->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->CompetitionTextTemplates->find()->contain(['Countries']),
            $this->CompetitionTextTemplates,
            $filterCountryId,
        );
        $query = $this->applyIndexSearch($query, $this->CompetitionTextTemplates);

        $redirect = $this->resolveIndexPageForLastVisited(
            'CompetitionTextTemplates',
            $query,
            $paginateOptions,
        );
        if ($redirect !== null) {
            return $redirect;
        }

        $competitionTextTemplates = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('CompetitionTextTemplates');
        $this->set(compact('competitionTextTemplates'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $scoped = AdminCountryScope::scopeForTable($this->request, $this->CompetitionTextTemplates);
        $filterCountryId = $scoped['countryId'];
        $competitionTextTemplate = $this->newEntityWithSchemaDefaults($this->CompetitionTextTemplates);
        if ($filterCountryId > 0) {
            $competitionTextTemplate->country_id = $filterCountryId;
        }
        $competitionTextTemplate->enabled = true;
        $competitionTextTemplate->visible = true;

        if ($this->request->is('post')) {
            if ($this->saveTemplate($competitionTextTemplate)) {
                $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);
                $this->Flash->success(__('The competition text template has been saved.'));

                return $this->redirectToIndexList('CompetitionTextTemplates');
            }
            $this->flashEntityErrors($competitionTextTemplate);
        }

        $this->setFormVariables($competitionTextTemplate, $filterCountryId);
        $this->set('title', __('New competition text template'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $competitionTextTemplate = $this->getTemplate($id, withTranslations: true);
        $denied = $this->denyIfOutsideAdminCountryScope($competitionTextTemplate);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->saveTemplate($competitionTextTemplate)) {
                $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);
                $this->Flash->success(__('The competition text template has been saved.'));

                return $this->redirectToIndexList('CompetitionTextTemplates');
            }
            $this->flashEntityErrors($competitionTextTemplate);
        }

        $this->setFormVariables($competitionTextTemplate);
        $this->set('title', __('Edit competition text template'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $competitionTextTemplate = $this->getTemplate($id, ['Countries']);
        $denied = $this->denyIfOutsideAdminCountryScope($competitionTextTemplate);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);

        $this->set(compact('competitionTextTemplate'));
        $this->set('countryLabel', AdminCountry::label((int)$competitionTextTemplate->country_id));
        $this->set('title', __('Competition text template details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $competitionTextTemplate = $this->getTemplate($id);
        $denied = $this->denyIfOutsideAdminCountryScope($competitionTextTemplate);
        if ($denied !== null) {
            return $denied;
        }

        return $this->deleteEntityOrFail($this->CompetitionTextTemplates, $competitionTextTemplate);
    }

    /**
     * JSON payload used when applying a template to a competition form.
     */
    public function applyData(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $template = $this->getTemplate($id, withTranslations: true);
        } catch (Throwable) {
            return $this->jsonError(__('Record not found.'), 404);
        }
        if (!AdminCountryScope::entityAllowed($template, $this->request)) {
            return $this->jsonError(__('You are not allowed to access records from another country.'), 403);
        }

        return $this->jsonResponse($this->applyPayload($template));
    }

    /**
     * JSON record details for the index modal.
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $template = $this->getTemplate($id, ['Countries']);
        } catch (Throwable) {
            return $this->jsonError(__('Record not found.'), 404);
        }
        if (!AdminCountryScope::entityAllowed($template, $this->request)) {
            return $this->jsonError(__('You are not allowed to access records from another country.'), 403);
        }

        $this->rememberLastVisited('CompetitionTextTemplates', $template->id);

        return $this->jsonResponse([
            'success' => true,
            'record' => $this->recordPayload($template, true),
        ]);
    }

    /**
     * @param \App\Model\Entity\CompetitionTextTemplate $template
     * @return bool
     */
    protected function saveTemplate(CompetitionTextTemplate $template): bool
    {
        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }
        $data = $this->constrainAdminCountryData($data);
        $countryId = (int)($data['country_id'] ?? $template->country_id ?? 0);
        if ($countryId < 1 || !AdminCountry::isValidCountryId($countryId)) {
            $template->setError('country_id', __('Select a valid country.'));

            return false;
        }

        $data['country_id'] = $countryId;
        $data['enabled'] = !empty($data['enabled']);
        $data['visible'] = !empty($data['visible']);
        $data = $this->scrubEmptyTranslations($data);
        $this->setFormTranslateLocale($this->CompetitionTextTemplates, $countryId);

        $this->CompetitionTextTemplates->patchEntity($template, $data, [
            'fields' => array_merge(self::FORM_FIELDS, ['_translations']),
        ]);
        if ($template->getErrors()) {
            return false;
        }
        if (!$this->CompetitionTextTemplates->save($template)) {
            if ($template->getErrors() === []) {
                $template->setError('_save', __('The database could not save this record. Please try again.'));
            }

            return false;
        }

        return true;
    }

    /**
     * @param list<string> $contain
     */
    protected function getTemplate(
        ?string $id,
        array $contain = [],
        bool $withTranslations = false,
    ): CompetitionTextTemplate {
        if ($withTranslations) {
            $probe = $this->CompetitionTextTemplates->find()
                ->select(['CompetitionTextTemplates.id', 'CompetitionTextTemplates.country_id'])
                ->where(['CompetitionTextTemplates.id' => $id])
                ->firstOrFail();
            /** @var \App\Model\Entity\CompetitionTextTemplate $template */
            $template = $this->getWithTranslations(
                $this->CompetitionTextTemplates,
                $id,
                $contain,
                (int)$probe->country_id ?: null,
            );

            return $template;
        }

        AdminTranslate::applyLocale($this->CompetitionTextTemplates);
        /** @var \App\Model\Entity\CompetitionTextTemplate $template */
        $template = $this->CompetitionTextTemplates->get($id, contain: $contain);

        return $template;
    }

    /**
     * @param \App\Model\Entity\CompetitionTextTemplate $template
     * @param int $fallbackCountryId
     * @return void
     */
    protected function setFormVariables(CompetitionTextTemplate $template, int $fallbackCountryId = 0): void
    {
        $countryId = (int)($template->country_id ?? $fallbackCountryId);
        $this->setFormLanguageTabs($countryId > 0 ? $countryId : null);
        $canChangeCountry = AdminCountryScope::canChangeCountry($this->request);
        $countryOptions = $canChangeCountry
            ? AdminCountry::options()
            : ($countryId > 0 ? [$countryId => AdminCountry::label($countryId)] : []);
        $placeholderHelp = CompetitionTextRender::placeholderHelp();

        $this->set(compact(
            'template',
            'countryOptions',
            'canChangeCountry',
            'placeholderHelp',
        ));
        $this->set('competitionTextTemplate', $template);
    }

    /**
     * @return array<string, mixed>
     */
    protected function applyPayload(CompetitionTextTemplate $template): array
    {
        $countryId = (int)$template->country_id;
        $defaultLocale = FormLanguages::defaultLocaleForForm($countryId);
        $fields = [];
        foreach (FormLanguages::locales($countryId) as $locale) {
            $fields[$locale] = $this->emptyTextFields();
        }
        $fields[$defaultLocale] = $this->textFieldsFrom($template);

        $translations = $template->get('_translations');
        if (is_array($translations)) {
            foreach ($translations as $locale => $translation) {
                $fields[(string)$locale] = array_merge(
                    $fields[(string)$locale] ?? $this->emptyTextFields(),
                    $this->textFieldsFrom($translation),
                );
            }
        }

        return [
            'id' => $template->id,
            'label' => (string)$template->label,
            'defaultLocale' => $defaultLocale,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordPayload(CompetitionTextTemplate $template, bool $withCountry): array
    {
        $record = [
            'id' => $template->id,
            'label' => (string)$template->label,
            'description' => (string)$template->description,
            'enabled' => (bool)$template->enabled,
            'visible' => (bool)$template->visible,
            'pos' => LocaleNumberParser::format($template->pos, decimals: 0),
            'created' => $template->created ? LocaleDateParser::format($template->created, 'datetime_short') : '',
            'modified' => $template->modified ? LocaleDateParser::format($template->modified, 'datetime_short') : '',
            'can_delete' => true,
        ];
        if ($withCountry) {
            $record = ['id' => $record['id'], 'country' => AdminCountry::label((int)$template->country_id)]
                + array_slice($record, 1, null, true);
        }

        return $record;
    }

    /**
     * @return array<string, string>
     */
    protected function emptyTextFields(): array
    {
        return array_fill_keys(CompetitionTextTemplatesTable::TRANSLATE_FIELDS, '');
    }

    /**
     * @return array<string, string>
     */
    protected function textFieldsFrom(mixed $source): array
    {
        $fields = [];
        foreach (CompetitionTextTemplatesTable::TRANSLATE_FIELDS as $field) {
            if (is_object($source) && method_exists($source, 'get')) {
                $fields[$field] = (string)($source->get($field) ?? '');
            } elseif (is_array($source)) {
                $fields[$field] = (string)($source[$field] ?? '');
            } else {
                $fields[$field] = '';
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $message
     * @param int $status
     * @return \Cake\Http\Response
     */
    protected function jsonError(string $message, int $status): Response
    {
        return $this->jsonResponse(['success' => false, 'message' => $message], $status);
    }
}
