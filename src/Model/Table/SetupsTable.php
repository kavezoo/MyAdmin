<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Setup;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\AdminCountry;
use App\Utility\ActivityLogSetup;
use App\Utility\SetupEditBy;
use App\Utility\SetupNameI18n;
use App\Utility\SetupValue;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Setups Model — typed application settings (EAV value by type), scoped by country.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 *
 * @method \App\Model\Entity\Setup newEmptyEntity()
 * @method \App\Model\Entity\Setup newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Setup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Setup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Setup|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 */
class SetupsTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait {
        beforeMarshal as protected applyDatabaseColumnDefaultsMarshal;
    }

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('setups');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(Setup::class);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'strategyClass' => EavStrategy::class,
            'fields' => ['name'],
            'defaultLocale' => SetupNameI18n::DEFAULT_LOCALE,
            'allowEmptyTranslations' => true,
        ]);

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
        ]);

        $this->addBehavior('CounterCache', [
            'Countries' => ['setup_count'],
        ]);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \ArrayObject<string, mixed> $data
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $this->applyDatabaseColumnDefaultsMarshal($event, $data, $options);

        $copy = $data->getArrayCopy();
        $type = isset($copy['type']) && is_string($copy['type']) ? $copy['type'] : '';

        if (isset($copy['slug']) && is_string($copy['slug'])) {
            $data['slug'] = strtolower(trim($copy['slug']));
        }

        if (isset($copy['edit_by']) && is_string($copy['edit_by'])) {
            $data['edit_by'] = SetupEditBy::normalizeStored($copy['edit_by']);
        }

        if ($type === SetupValue::TYPE_BOOLEAN && !array_key_exists('value', $copy)) {
            $data['value'] = '0';
            $copy['value'] = '0';
        }

        // Secret: empty → do not overwrite; beforeSave restores original on edit / '' on create.
        if (
            $type === SetupValue::TYPE_SECRET
            && array_key_exists('value', $copy)
            && ($copy['value'] === null || (is_string($copy['value']) && trim($copy['value']) === ''))
        ) {
            unset($data['value']);

            return;
        }

        if (array_key_exists('value', $copy) && $copy['value'] === null) {
            $data['value'] = '';
            $copy['value'] = '';
        }

        $copy = $data->getArrayCopy();
        if ($type === '' || !array_key_exists('value', $copy)) {
            return;
        }

        $result = SetupValue::normalize($type, $copy['value']);
        if ($result['ok']) {
            $data['value'] = $result['value'] ?? '';
        }
    }

    /**
     * Keep previous secret when the password field was left blank on edit.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof Setup) {
            return;
        }
        if ((string)$entity->get('type') !== SetupValue::TYPE_SECRET) {
            return;
        }
        if ($entity->isNew()) {
            if ($entity->get('value') === null) {
                $entity->set('value', '');
            }

            return;
        }
        if (!$entity->isDirty('value')) {
            return;
        }
        $new = $entity->get('value');
        if ($new !== null && $new !== '') {
            return;
        }
        $entity->set('value', $entity->getOriginal('value'));
        $entity->setDirty('value', false);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 255)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->add('slug', 'format', [
                'rule' => static function ($value) {
                    return is_string($value) && SetupValue::isValidSlug($value);
                },
                'message' => __('The slug may only contain lowercase letters, numbers and underscores.'),
            ]);

        $validator
            ->scalar('type')
            ->maxLength('type', 20)
            ->requirePresence('type', 'create')
            ->notEmptyString('type')
            ->inList('type', SetupValue::typeList(), __('Invalid type.'));

        $validator
            ->scalar('edit_by')
            ->maxLength('edit_by', 20)
            ->notEmptyString('edit_by')
            ->inList('edit_by', SetupEditBy::list(), __('Invalid edit permission.'));

        $validator
            ->scalar('value')
            ->allowEmptyString('value')
            ->add('value', 'typedValue', [
                'rule' => static function ($value, $context) {
                    $type = (string)($context['data']['type'] ?? '');
                    if ($type === '' || !SetupValue::isValidType($type)) {
                        return true;
                    }
                    // Empty secret on update is OK (keep previous).
                    if ($type === SetupValue::TYPE_SECRET && ($value === null || $value === '')) {
                        return true;
                    }
                    $result = SetupValue::normalize($type, $value);
                    if ($result['ok']) {
                        return true;
                    }

                    return $result['error'] ?: __('The value is not valid for the selected type.');
                },
            ]);

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);
        $rules->add(
            $rules->isUnique(
                ['country_id', 'slug'],
                __('This slug is already in use for this country.')
            ),
            ['errorField' => 'slug']
        );

        return $rules;
    }

    /**
     * Create the same setup slug for every visible country.
     * Returns the entity for $primaryCountryId (working country), or null on failure.
     *
     * @param array<string, mixed> $data Shared fields (name, slug, type, value, edit_by, …)
     * @param int $primaryCountryId Country shown in the Admin UI after save
     */
    public function createForAllCountries(array $data, int $primaryCountryId): ?Setup
    {
        $slug = strtolower(trim((string)($data['slug'] ?? '')));
        if ($slug === '' || $primaryCountryId < 1) {
            return null;
        }

        $countryIds = AdminCountry::visibleIds();
        if ($countryIds === []) {
            return null;
        }
        if (!in_array($primaryCountryId, $countryIds, true)) {
            $countryIds[] = $primaryCountryId;
        }

        $existing = $this->find()
            ->select(['country_id'])
            ->where(['slug' => $slug, 'country_id IN' => $countryIds])
            ->all()
            ->extract('country_id')
            ->toList();
        $existingMap = array_fill_keys(array_map('intval', $existing), true);

        // Working country must not already have this slug.
        if (isset($existingMap[$primaryCountryId])) {
            return null;
        }

        $primary = null;
        $connection = $this->getConnection();
        $connection->begin();
        try {
            foreach ($countryIds as $countryId) {
                if (isset($existingMap[$countryId])) {
                    continue;
                }

                $rowData = $data;
                $rowData['country_id'] = $countryId;
                if (!isset($rowData['edit_by']) || $rowData['edit_by'] === '') {
                    $rowData['edit_by'] = SetupEditBy::ADMIN;
                }
                if (!array_key_exists('value', $rowData) || $rowData['value'] === null) {
                    $rowData['value'] = '';
                }

                $entity = $this->newEmptyEntity();
                $this->applySchemaDefaults($entity);
                $entity = $this->patchEntity($entity, $rowData);
                if ($entity->getErrors()) {
                    $connection->rollback();

                    return null;
                }
                if (!$this->save($entity)) {
                    $connection->rollback();

                    return null;
                }
                $nameMsgid = trim((string)($rowData['name'] ?? ''));
                if ($nameMsgid !== '') {
                    SetupNameI18n::seedForEntity($this, $entity, $nameMsgid);
                }
                if ($countryId === $primaryCountryId) {
                    $primary = $entity;
                }
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollback();

            return null;
        }

        return $primary;
    }

    /**
     * Ensure activity-log setup slugs exist for visible countries (or one country).
     */
    public function ensureActivityLogSetups(?int $countryId = null): void
    {
        if ($countryId !== null && $countryId > 0) {
            $this->ensureActivityLogSetupForCountry($countryId);

            return;
        }

        foreach (AdminCountry::visibleIds() as $id) {
            $this->ensureActivityLogSetupForCountry((int)$id);
        }
    }

    /**
     * @return \App\Model\Entity\Setup|null
     */
    public function findBySlugAndCountry(string $slug, int $countryId): ?Setup
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || $countryId < 1 || !SetupValue::isValidSlug($slug)) {
            return null;
        }

        return $this->find()
            ->where([
                'Setups.slug' => $slug,
                'Setups.country_id' => $countryId,
            ])
            ->first();
    }

    /**
     * Flip a boolean setup value for a country. Returns new bool state or null on failure.
     */
    public function toggleBoolean(int $countryId, string $slug): ?bool
    {
        $this->ensureActivityLogSetupForCountry($countryId);
        $setup = $this->findBySlugAndCountry($slug, $countryId);
        if ($setup === null) {
            return null;
        }

        $current = (bool)SetupValue::cast(
            (string)$setup->get('type'),
            (string)($setup->get('value') ?? '0')
        );
        $new = !$current;
        $setup = $this->patchEntity($setup, [
            'value' => $new ? '1' : '0',
        ], [
            'fields' => ['value'],
        ]);
        if ($setup->hasErrors() || !$this->save($setup)) {
            return null;
        }

        return $new;
    }

    protected function ensureActivityLogSetupForCountry(int $countryId): void
    {
        if ($countryId < 1) {
            return;
        }

        foreach (ActivityLogSetup::definitions() as $slug => $def) {
            if ($this->exists(['slug' => $slug, 'country_id' => $countryId])) {
                continue;
            }

            $entity = $this->newEmptyEntity();
            $this->applySchemaDefaults($entity);
            $entity = $this->patchEntity($entity, [
                'country_id' => $countryId,
                'name' => $def['name'],
                'slug' => $slug,
                'type' => $def['type'],
                'value' => $def['value'],
                'edit_by' => $def['edit_by'],
                'visible' => $def['visible'],
            ]);
            if (!$entity->hasErrors()) {
                if ($this->save($entity, ['checkRules' => false])) {
                    SetupNameI18n::seedForEntity($this, $entity, (string)$def['name']);
                }
            }
        }
    }

    /**
     * Typed PHP value by slug for a country (or default if missing / invisible).
     *
     * @param string $slug Setup slug
     * @param mixed $default Fallback
     * @param int|null $countryId null → AdminCountry::id()
     */
    public function getValue(string $slug, mixed $default = null, ?int $countryId = null): mixed
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !SetupValue::isValidSlug($slug)) {
            return $default;
        }
        $countryId ??= AdminCountry::id();
        if ($countryId < 1) {
            return $default;
        }

        $row = $this->find()
            ->select(['type', 'value', 'visible'])
            ->where([
                'Setups.slug' => $slug,
                'Setups.country_id' => $countryId,
            ])
            ->first();
        if ($row === null || !(bool)$row->get('visible')) {
            return $default;
        }

        $stored = $row->get('value');

        return SetupValue::cast((string)$row->get('type'), $stored !== null ? (string)$stored : null);
    }
}
