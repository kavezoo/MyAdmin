<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\SetupAccess;
use App\Utility\AdminCountry;
use App\Utility\AdminTranslate;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use App\Utility\SetupEditBy;
use App\Utility\SetupNameI18n;
use App\Utility\SetupValue;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * Setups Controller — typed application settings (per working country).
 *
 * Access: Superuser, Admin, President, Vice president (see SetupAccess).
 * Create / country switch / metadata: Superuser only.
 *
 * @property \App\Model\Table\SetupsTable $Setups
 */
class SetupsController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!SetupAccess::canAccessModule($this->request)) {
            throw new ForbiddenException(__('You are not allowed to access Setups.'));
        }

        $this->set('canAdd', SetupAccess::canCreate($this->request));
        $this->set('canChangeCountry', SetupAccess::canChangeCountry($this->request));
        $this->set('canEditSetupMetadata', SetupAccess::canEditMetadata($this->request));
        $this->set('canDeleteSetup', SetupAccess::canDelete($this->request));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $canChangeCountry = SetupAccess::canChangeCountry($this->request);

        if ($canChangeCountry) {
            $queryCountryId = $this->request->getQuery('country_id');
            if ($queryCountryId !== null && $queryCountryId !== '') {
                $newId = (int)$queryCountryId;
                $redirect = $this->redirect(['action' => 'index', '?' => []]);
                if ($redirect instanceof Response) {
                    return AdminCountry::set($newId, $this->request, $redirect);
                }
            }
        }

        $workingCountryId = AdminCountry::id($this->request);
        $countryOptions = $canChangeCountry ? AdminCountry::options() : [];
        $workingCountryLabel = AdminCountry::label($workingCountryId);

        $title = $workingCountryLabel !== ''
            ? __('Setups') . ' — ' . $workingCountryLabel
            : __('Setups');
        $this->set('title', $title);
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));

        if ($workingCountryId < 1) {
            $this->Flash->error(__('No visible country is available. Add or enable a country first.'));
            $this->set([
                'setups' => [],
                'setupTypeOptions' => SetupValue::typeOptions(),
                'setupEditByOptions' => SetupEditBy::options(),
                'workingCountryId' => 0,
                'workingCountryLabel' => '',
                'countryOptions' => [],
            ]);

            return;
        }

        $redirect = $this->applyIndexListState('Setups');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Setups, [
            'sortableFields' => [
                'id',
                'name',
                'slug',
                'type',
                'edit_by',
                'created',
                'modified',
            ],
        ]);
        $query = $this->Setups->find()
            ->where(['Setups.country_id' => $workingCountryId]);
        $query = $this->applyIndexSearch($query, $this->Setups);
        $redirect = $this->resolveIndexPageForLastVisited('Setups', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }
        $setups = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Setups');
        $this->set(compact('setups', 'workingCountryId', 'workingCountryLabel', 'countryOptions'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
        $this->set('setupEditByOptions', SetupEditBy::options());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        if (!SetupAccess::canCreate($this->request)) {
            throw new ForbiddenException(__('Only a Superuser may create setups.'));
        }

        $setup = $this->newEntityWithSchemaDefaults($this->Setups);
        if ($setup->get('type') === null || $setup->get('type') === '') {
            $setup->set('type', SetupValue::TYPE_STRING);
        }
        if ($setup->get('edit_by') === null || $setup->get('edit_by') === '') {
            $setup->set('edit_by', SetupEditBy::ADMIN);
        }
        $setup->set('country_id', AdminCountry::id($this->request));

        if ($this->request->is('post')) {
            try {
                $data = $this->request->getData();
                $countryId = AdminCountry::id($this->request);
                $data['country_id'] = $countryId;
                if (empty($data['edit_by'])) {
                    $data['edit_by'] = SetupEditBy::ADMIN;
                }
                $data['edit_by'] = SetupEditBy::normalizeStored((string)$data['edit_by']);

                $setup = $this->Setups->patchEntity($setup, $data);
                if (!$setup->getErrors()) {
                    $saved = $this->Setups->createForAllCountries($data, $countryId);
                    if ($saved !== null) {
                        $this->rememberLastVisited('Setups', $saved->id);
                        $this->Flash->success(__('The setup has been saved for all countries.'));

                        return $this->redirectToIndexList('Setups');
                    }
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($setup);
        $this->set('title', __('New setup'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        AdminTranslate::applyLocale($this->Setups);
        $setup = $this->Setups->get($id);
        $this->rememberLastVisited('Setups', $setup->id);

        if (!SetupAccess::canEditValue($setup, $this->request)) {
            throw new ForbiddenException(__('You are not allowed to edit this setup.'));
        }

        $canMeta = SetupAccess::canEditMetadata($this->request);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                unset($data['country_id']);
                if (!$canMeta) {
                    unset($data['name'], $data['slug'], $data['type'], $data['edit_by'], $data['pos'], $data['visible']);
                } elseif (isset($data['edit_by'])) {
                    $data['edit_by'] = SetupEditBy::normalizeStored((string)$data['edit_by']);
                }

                $accessible = ['country_id' => false];
                if (!$canMeta) {
                    $accessible = [
                        'country_id' => false,
                        'name' => false,
                        'slug' => false,
                        'type' => false,
                        'edit_by' => false,
                        'pos' => false,
                        'visible' => false,
                        'value' => true,
                    ];
                }

                $setup = $this->Setups->patchEntity($setup, $data, [
                    'accessibleFields' => $accessible,
                ]);
                if ($this->Setups->save($setup)) {
                    $this->rememberLastVisited('Setups', $setup->id);
                    if ($canMeta) {
                        $nameMsgid = trim((string)($data['name'] ?? $setup->name ?? ''));
                        if ($nameMsgid !== '') {
                            SetupNameI18n::seedForEntity($this->Setups, $setup, $nameMsgid);
                        }
                    }
                    $this->Flash->success(__('The setup has been saved.'));

                    return $this->redirectToIndexList('Setups');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($setup);
        $this->setCanDeleteFlag($this->Setups, $setup);
        if (!SetupAccess::canDelete($this->request)) {
            $this->set('canDelete', false);
        }
        $this->set('title', __('Edit setup'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        AdminTranslate::applyLocale($this->Setups);
        $setup = $this->Setups->get($id, contain: ['Countries']);
        $this->rememberLastVisited('Setups', $setup->id);
        $this->set(compact('setup'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
        $this->set('setupEditByOptions', SetupEditBy::options());
        $this->setCanDeleteFlag($this->Setups, $setup);
        if (!SetupAccess::canDelete($this->request)) {
            $this->set('canDelete', false);
        }
        $this->set('canEditThisSetup', SetupAccess::canEditValue($setup, $this->request));
        $this->set('canEdit', SetupAccess::canEditValue($setup, $this->request));
        $this->set('title', __('Setup details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!SetupAccess::canDelete($this->request)) {
            throw new ForbiddenException(__('Only a Superuser may delete setups.'));
        }

        return $this->deleteEntityOrFail($this->Setups, $this->Setups->get($id));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            AdminTranslate::applyLocale($this->Setups);
            $setup = $this->Setups->get($id);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Setups', $setup->id);
        $typeLabels = SetupValue::typeOptions();
        $type = (string)$setup->type;
        $editBy = SetupEditBy::normalizeStored((string)$setup->edit_by);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $setup->id,
                    'name' => $setup->name,
                    'slug' => $setup->slug,
                    'type' => $typeLabels[$type] ?? $type,
                    'edit_by' => SetupEditBy::label($editBy),
                    'value' => SetupValue::formatForDisplay($type, $setup->value),
                    'created' => $setup->created ? LocaleDateParser::format($setup->created, 'datetime_short') : '',
                    'modified' => $setup->modified ? LocaleDateParser::format($setup->modified, 'datetime_short') : '',
                    'can_delete' => SetupAccess::canDelete($this->request),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param \App\Model\Entity\Setup $setup
     * @return void
     */
    protected function setFormOptions($setup): void
    {
        $type = (string)($setup->type ?: SetupValue::TYPE_STRING);
        $this->set(compact('setup'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
        $this->set('setupEditByOptions', SetupEditBy::options());
        $this->set('setupValueForm', SetupValue::formatForForm($type, $setup->value !== null ? (string)$setup->value : null));
        $this->set('dateFormat', LocaleDateParser::jsConfig());
        $this->set('numberFormat', LocaleNumberParser::jsConfig());
        $this->set('workingCountryLabel', AdminCountry::label((int)$setup->country_id));
        $this->set('canEditSetupMetadata', SetupAccess::canEditMetadata($this->request));
    }
}
