<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Model\Entity\CompetitionTextTemplate;
use App\Model\Table\CompetitionTextTemplatesTable;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionTextRender;
use App\Utility\FormLanguages;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Throwable;

/**
 * Country-scoped competition text template CRUD for officers.
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
     * @return void
     */
    protected function setAccessFlags(): void
    {
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', true);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Competition text templates'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));
        $this->setAccessFlags();

        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a country yet. Contact an administrator.'));
            $this->set('competitionTextTemplates', $this->emptyPaginated($this->indexLimit));

            return;
        }

        $redirect = $this->applyIndexListState('CompetitionTextTemplates');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->CompetitionTextTemplates, [
            'sortableFields' => [
                'id',
                'label',
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
        ]);

        $query = $this->CompetitionTextTemplates->find()
            ->where(['CompetitionTextTemplates.country_id' => $countryId]);
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
        $countryId = $this->officerCountryId();
        $competitionTextTemplate = $this->newEntityWithSchemaDefaults($this->CompetitionTextTemplates);
        $competitionTextTemplate->country_id = $countryId;
        $competitionTextTemplate->enabled = true;
        $competitionTextTemplate->visible = true;

        if ($this->request->is('post')) {
            if ($this->saveTemplate($competitionTextTemplate, $countryId)) {
                $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);
                $this->Flash->success(__('The competition text template has been saved.'));

                return $this->redirectToIndexList('CompetitionTextTemplates');
            }
            $this->flashEntityErrors($competitionTextTemplate);
        }

        $this->setFormVariables($competitionTextTemplate, $countryId);
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
        $countryId = $this->officerCountryId();
        $competitionTextTemplate = $this->getScopedTemplate($id, $countryId, withTranslations: true);
        $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->saveTemplate($competitionTextTemplate, $countryId)) {
                $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);
                $this->Flash->success(__('The competition text template has been saved.'));

                return $this->redirectToIndexList('CompetitionTextTemplates');
            }
            $this->flashEntityErrors($competitionTextTemplate);
        }

        $this->setFormVariables($competitionTextTemplate, $countryId);
        $this->set('title', __('Edit competition text template'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition text templates'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return void
     */
    public function view(?string $id = null): void
    {
        $countryId = $this->officerCountryId();
        $competitionTextTemplate = $this->getScopedTemplate($id, $countryId);
        $this->rememberLastVisited('CompetitionTextTemplates', $competitionTextTemplate->id);

        $this->set(compact('competitionTextTemplate'));
        $this->setAccessFlags();
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
        $template = $this->getScopedTemplate($id, $this->officerCountryId());

        return $this->deleteEntityOrFail($this->CompetitionTextTemplates, $template);
    }

    /**
     * JSON payload used when applying a template to a competition form.
     */
    public function applyData(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();

        try {
            $template = $this->getScopedTemplate($id, $countryId, withTranslations: true);
        } catch (Throwable) {
            return $this->jsonError(__('Record not found.'), 404);
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
            $template = $this->getScopedTemplate($id, $this->officerCountryId());
        } catch (Throwable) {
            return $this->jsonError(__('Record not found.'), 404);
        }

        $this->rememberLastVisited('CompetitionTextTemplates', $template->id);

        return $this->jsonResponse([
            'success' => true,
            'record' => $this->recordPayload($template),
        ]);
    }

    /**
     * @param \App\Model\Entity\CompetitionTextTemplate $template
     * @param int $countryId
     * @return bool
     */
    protected function saveTemplate(CompetitionTextTemplate $template, int $countryId): bool
    {
        if ($countryId < 1) {
            $template->setError(
                'country_id',
                __('Your account is not assigned to a country yet. Contact an administrator.'),
            );

            return false;
        }

        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }
        $data['country_id'] = $countryId;
        $data['enabled'] = !empty($data['enabled']);
        $data['visible'] = !empty($data['visible']);
        $data = $this->scrubEmptyTranslations($data);
        $this->setFormTranslateLocale($this->CompetitionTextTemplates, $countryId);

        $this->CompetitionTextTemplates->patchEntity($template, $data, [
            'fields' => array_merge(self::FORM_FIELDS, ['country_id', '_translations']),
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
    protected function getScopedTemplate(
        ?string $id,
        int $countryId,
        array $contain = [],
        bool $withTranslations = false,
    ): CompetitionTextTemplate {
        if ($withTranslations) {
            /** @var \App\Model\Entity\CompetitionTextTemplate $template */
            $template = $this->getWithTranslations(
                $this->CompetitionTextTemplates,
                $id,
                $contain,
                $countryId,
            );
        } else {
            AdminTranslate::applyLocale($this->CompetitionTextTemplates);
            /** @var \App\Model\Entity\CompetitionTextTemplate $template */
            $template = $this->CompetitionTextTemplates->get($id, contain: $contain);
        }
        if ($countryId < 1 || (int)$template->country_id !== $countryId) {
            throw new NotFoundException(__('Record not found.'));
        }

        return $template;
    }

    /**
     * @param \App\Model\Entity\CompetitionTextTemplate $template
     * @param int $countryId
     * @return void
     */
    protected function setFormVariables(CompetitionTextTemplate $template, int $countryId): void
    {
        $this->setFormLanguageTabs($countryId > 0 ? $countryId : null);
        $placeholderHelp = CompetitionTextRender::placeholderHelp();
        $this->set(compact('placeholderHelp'));
        $this->set('competitionTextTemplate', $template);
        $this->setAccessFlags();
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
    protected function recordPayload(CompetitionTextTemplate $template): array
    {
        return [
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
