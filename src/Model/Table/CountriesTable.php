<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\AdminCountry;
use App\Utility\AdminTimezone;
use App\Utility\AdminTranslate;
use App\Utility\PhoneNumber;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Countries Model — matches `countries` schema.
 *
 * Columns: iso2, name, endonim_name, locale, timezone, phone_prefix, continent_id, visible, pos, user_count, created, modified
 * - `name` Translate EAV → i18n (model=Countries)
 * - `endonim_name` endonym (native script), not translated
 * - `timezone` IANA (display/save via AdminTimezone + LocaleDateParser)
 * - belongsTo Continents via continent_id
 * - `user_count`: CounterCache from UsersTable (belongsTo Countries)
 * - Access: superuser full CRUD; admin visible + pos only (see CountryAccess)
 *
 * Note: BelongsToMany `VisibleCountries` is kept for association wiring / save helpers,
 * but eager `contain(['VisibleCountries'])` is unreliable on this self-ref + Translate
 * setup — prefer {@see additionalLanguageIds()} / {@see replaceVisibleCountryIds()}.
 *
 * @property \App\Model\Table\ContinentsTable&\Cake\ORM\Association\BelongsTo $Continents
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 * @property \App\Model\Table\SetupsTable&\Cake\ORM\Association\HasMany $Setups
 * @property \App\Model\Table\CountryVisibilitiesTable&\Cake\ORM\Association\HasMany $CountryVisibilities
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsToMany $VisibleCountries
 *
 * @method \App\Model\Entity\Country newEmptyEntity()
 * @method \App\Model\Entity\Country newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Country> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Country get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Country findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Country patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Country> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Country|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Country saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 */
class CountriesTable extends Table
{
    use LocatorAwareTrait;
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('countries');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Country::class);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'strategyClass' => EavStrategy::class,
            'fields' => ['name'],
            'defaultLocale' => 'en_GB',
            'allowEmptyTranslations' => false,
        ]);

        $this->belongsTo('Continents', [
            'foreignKey' => 'continent_id',
            'joinType' => 'INNER',
        ]);

        // Delete protection / view related tabs — no cascade
        $this->hasMany('Users', [
            'foreignKey' => 'country_id',
            'className' => 'Users',
            'dependent' => false,
        ]);
        $this->hasMany('Setups', [
            'foreignKey' => 'country_id',
            'dependent' => false,
        ]);

        $this->hasMany('CountryVisibilities', [
            'foreignKey' => 'country_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->belongsToMany('VisibleCountries', [
            'className' => 'Countries',
            'through' => 'CountryVisibilities',
            'foreignKey' => 'country_id',
            'targetForeignKey' => 'visible_country_id',
            'saveStrategy' => 'replace',
            'sort' => [
                'CountryVisibilities.pos' => 'ASC',
                'VisibleCountries.id' => 'ASC',
            ],
        ]);
    }

    /**
     * Country id whose locale is en_GB (canonical English for Translate/UI fallback).
     */
    public function englishDefaultCountryId(): ?int
    {
        $row = $this->find()
            ->select(['Countries.id'])
            ->where(['Countries.locale' => 'en_GB'])
            ->first();
        if ($row !== null) {
            return (int)$row->get('id');
        }
        // Fallback: any English primary locale
        $row = $this->find()
            ->select(['Countries.id', 'Countries.locale'])
            ->where(['Countries.locale LIKE' => 'en_%'])
            ->orderBy(['Countries.id' => 'ASC'])
            ->first();

        return $row !== null ? (int)$row->get('id') : null;
    }

    /**
     * Ensure self country id is in the list and first (own language always on tabs).
     *
     * @param list<int> $ids
     * @return list<int>
     */
    public function ensureSelfFirst(array $ids, int $selfCountryId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        if ($selfCountryId < 1) {
            return $ids;
        }
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $selfCountryId));
        array_unshift($ids, $selfCountryId);

        return $ids;
    }

    /**
     * Display helper: put en_GB first in option lists (login/master selects) — not a visibility lock.
     *
     * @param list<int> $ids
     * @return list<int>
     */
    public function ensureEnglishDefaultFirst(array $ids): array
    {
        $enId = $this->englishDefaultCountryId();
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        if ($enId === null) {
            return $ids;
        }
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $enId));
        array_unshift($ids, $enId);

        return $ids;
    }

    /**
     * Visible partner country ids for an active (viewer) country.
     * Always includes self first; extras are optional additional languages.
     *
     * @return list<int>
     */
    public function visibleCountryIdsFor(int $activeCountryId): array
    {
        if ($activeCountryId < 1) {
            return $this->loginVisibleCountryIds();
        }

        /** @var \App\Model\Table\CountryVisibilitiesTable $junction */
        $junction = $this->fetchTable('CountryVisibilities');
        $ids = $junction->find()
            ->select(['visible_country_id'])
            ->where([
                'CountryVisibilities.country_id' => $activeCountryId,
                'CountryVisibilities.visible' => true,
            ])
            ->orderBy(['CountryVisibilities.pos' => 'ASC', 'CountryVisibilities.id' => 'ASC'])
            ->all()
            ->extract('visible_country_id')
            ->toList();

        return $this->ensureSelfFirst(array_map('intval', $ids), $activeCountryId);
    }

    /**
     * Login / global select ids: DISTINCT visible_country_id where visible=1.
     * (Self-edges mean every country can appear; extras add partners to the union.)
     *
     * @return list<int>
     */
    public function loginVisibleCountryIds(): array
    {
        /** @var \App\Model\Table\CountryVisibilitiesTable $junction */
        $junction = $this->fetchTable('CountryVisibilities');
        $ids = $junction->find()
            ->select(['visible_country_id'])
            ->distinct(['visible_country_id'])
            ->where(['CountryVisibilities.visible' => true])
            ->all()
            ->extract('visible_country_id')
            ->toList();

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Countries visible for an active (viewer) country — junction + Translate names.
     * Own country first, then extras by junction pos.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findVisibleForCountry(SelectQuery $query, int $activeCountryId = 0, ?string $locale = null): SelectQuery
    {
        $locale = AdminCountry::normalizeTranslateLocale(
            ($locale !== null && $locale !== '') ? $locale : I18n::getLocale()
        );
        $this->getBehavior('Translate')->setLocale($locale);

        $ids = $this->visibleCountryIdsFor($activeCountryId);
        if ($ids === []) {
            return $query->where(['Countries.id' => 0]);
        }

        $order = [];
        if ($activeCountryId > 0) {
            $order['CASE WHEN Countries.id = ' . (int)$activeCountryId . ' THEN 0 ELSE 1 END'] = 'ASC';
        }
        $order['FIELD(Countries.id, ' . implode(',', $ids) . ')'] = 'ASC';

        return $query
            ->where(['Countries.id IN' => $ids])
            ->orderBy($order);
    }

    /**
     * Login / global select: DISTINCT countries that appear as visible_country anywhere.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findLoginVisible(SelectQuery $query, ?string $locale = null): SelectQuery
    {
        $locale = AdminCountry::normalizeTranslateLocale(
            ($locale !== null && $locale !== '') ? $locale : I18n::getLocale()
        );
        $this->getBehavior('Translate')->setLocale($locale);

        $ids = $this->loginVisibleCountryIds();
        if ($ids === []) {
            // Fallback before seed: master visible flag + en_GB
            return $this->findVisibleTranslated($query, $locale)->orderBy([
                'CASE WHEN Countries.locale = \'en_GB\' THEN 0 WHEN Countries.locale LIKE \'en_%\' THEN 1 ELSE 2 END' => 'ASC',
                'Countries.pos' => 'ASC',
            ], true);
        }

        return $query
            ->where(['Countries.id IN' => $ids])
            ->orderBy([
                'CASE WHEN Countries.locale = \'en_GB\' THEN 0 WHEN Countries.locale LIKE \'en_%\' THEN 1 ELSE 2 END' => 'ASC',
                'FIELD(Countries.id, ' . implode(',', $ids) . ')' => 'ASC',
            ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('iso2')
            ->lengthBetween('iso2', [2, 2])
            ->requirePresence('iso2', 'create')
            ->notEmptyString('iso2');

        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('endonim_name')
            ->maxLength('endonim_name', 150)
            ->requirePresence('endonim_name', 'create')
            ->notEmptyString('endonim_name');

        $validator
            ->scalar('locale')
            ->maxLength('locale', 10)
            ->requirePresence('locale', 'create')
            ->notEmptyString('locale');

        $validator
            ->scalar('timezone')
            ->maxLength('timezone', 64)
            ->requirePresence('timezone', 'create')
            ->notEmptyString('timezone')
            ->add('timezone', 'iana', [
                'rule' => static function ($value) {
                    return is_string($value) && AdminTimezone::isValid($value);
                },
                'message' => 'Invalid IANA timezone',
            ]);

        $validator
            ->scalar('phone_prefix')
            ->maxLength('phone_prefix', 16)
            ->allowEmptyString('phone_prefix')
            ->add('phone_prefix', 'e164Prefix', [
                'rule' => static function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return (bool)preg_match('/^\+\d{1,4}$/', (string)$value);
                },
                'message' => __('Use an international prefix like +36.'),
            ]);

        $validator
            ->nonNegativeInteger('continent_id')
            ->requirePresence('continent_id', 'create')
            ->notEmptyString('continent_id');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        $validator
            ->nonNegativeInteger('user_count')
            ->allowEmptyString('user_count');

        return $validator;
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isDirty('phone_prefix')) {
            $normalized = PhoneNumber::normalizePrefix($entity->get('phone_prefix'));
            $entity->set('phone_prefix', $normalized);
        }
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['iso2']), ['errorField' => 'iso2']);
        $rules->add($rules->existsIn(['continent_id'], 'Continents'), ['errorField' => 'continent_id']);

        return $rules;
    }

    /**
     * Delete only when no users and no setups reference the country.
     * Users: CounterCache `user_count` (fresh DB read). Setups: live count (no setup_count column yet).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return bool
     */
    public function canDelete(EntityInterface $entity): bool
    {
        if ($this->countUsers($entity) > 0) {
            return false;
        }

        $setups = $this->fetchTable('Setups');
        $setupCount = $setups->find()
            ->where(['Setups.country_id' => $entity->get('id')])
            ->count();

        return $setupCount === 0;
    }

    /**
     * Related users blocking delete — CounterCache `user_count` (fresh DB when PK present).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
    public function countUsers(EntityInterface $entity): int
    {
        $id = $entity->get($this->getPrimaryKey());
        if ($id !== null && $id !== '') {
            $row = $this->find()
                ->select(['user_count'])
                ->where([$this->aliasField($this->getPrimaryKey()) => $id])
                ->disableHydration()
                ->first();
            if (is_array($row) && array_key_exists('user_count', $row)) {
                return (int)$row['user_count'];
            }
        }

        return (int)($entity->get('user_count') ?? 0);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->canDelete($entity)) {
            $entity->setError('_delete', [
                __('Cannot delete this record because it has related child records.'),
            ]);
            $event->stopPropagation();
            $event->setResult(false);
        }
    }

    /**
     * Visible countries with `name` in the UI / Admin page locale (Translate).
     * Use for Select2 and any listing that must follow the page language (not a language switch).
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findVisibleTranslated(SelectQuery $query, ?string $locale = null): SelectQuery
    {
        $locale = AdminCountry::normalizeTranslateLocale(
            ($locale !== null && $locale !== '') ? $locale : I18n::getLocale()
        );
        $this->getBehavior('Translate')->setLocale($locale);

        $order = [];
        foreach (AdminTranslate::orderFieldList($this, 'name') as $sqlField) {
            $order[$sqlField] = 'ASC';
        }

        return $query
            ->where(['Countries.visible' => true])
            ->orderBy($order);
    }

    /**
     * Extra (non-self) visible partner country ids for the Countries form Select2.
     * Reads the junction directly — do not rely on contain(['VisibleCountries']).
     *
     * @return list<int>
     */
    public function additionalLanguageIds(int $countryId): array
    {
        if ($countryId < 1) {
            return [];
        }

        /** @var \App\Model\Table\CountryVisibilitiesTable $junction */
        $junction = $this->fetchTable('CountryVisibilities');
        $ids = $junction->find()
            ->select(['visible_country_id'])
            ->where([
                'CountryVisibilities.country_id' => $countryId,
                'CountryVisibilities.visible' => true,
                'CountryVisibilities.visible_country_id !=' => $countryId,
            ])
            ->orderBy(['CountryVisibilities.pos' => 'ASC', 'CountryVisibilities.id' => 'ASC'])
            ->all()
            ->extract('visible_country_id')
            ->toList();

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Extra language partner countries (Translate names) for index modal / lists.
     *
     * @return list<\App\Model\Entity\Country>
     */
    public function additionalLanguageCountries(int $countryId, ?string $locale = null): array
    {
        $ids = $this->additionalLanguageIds($countryId);
        if ($ids === []) {
            return [];
        }

        $locale = AdminCountry::normalizeTranslateLocale(
            ($locale !== null && $locale !== '') ? $locale : I18n::getLocale()
        );
        $this->getBehavior('Translate')->setLocale($locale);

        return $this->find()
            ->where(['Countries.id IN' => $ids])
            ->orderBy(['FIELD(Countries.id, ' . implode(',', $ids) . ')' => 'ASC'])
            ->all()
            ->toList();
    }

    /**
     * Replace visibility edges for a country: mandatory self + posted extras.
     * Prefer this over BelongsToMany `_ids` save (self-ref contain/hydration is unreliable).
     *
     * @param list<int> $extraVisibleCountryIds Partner ids only (self is always added)
     */
    public function replaceVisibleCountryIds(int $countryId, array $extraVisibleCountryIds): void
    {
        if ($countryId < 1) {
            return;
        }

        $extras = array_values(array_unique(array_filter(
            array_map('intval', $extraVisibleCountryIds),
            static fn(int $id): bool => $id > 0 && $id !== $countryId
        )));

        /** @var \App\Model\Table\CountryVisibilitiesTable $junction */
        $junction = $this->fetchTable('CountryVisibilities');
        $junction->deleteAll(['country_id' => $countryId]);

        $this->ensureSelfVisibility($countryId);
        $pos = 10;
        foreach ($extras as $visibleId) {
            $this->ensurePartnerVisibility($countryId, $visibleId, preferredPos: $pos);
            $pos += 10;
        }
    }

    /**
     * Ensure junction row: country always sees itself (visible=1, pos=1).
     */
    public function ensureSelfVisibility(int $countryId): void
    {
        $this->ensurePartnerVisibility($countryId, $countryId, preferredPos: 1);
    }

    /**
     * Insert or re-enable a single visibility edge.
     */
    public function ensurePartnerVisibility(int $countryId, int $visibleCountryId, ?int $preferredPos = null): void
    {
        if ($countryId < 1 || $visibleCountryId < 1) {
            return;
        }

        /** @var \App\Model\Table\CountryVisibilitiesTable $junction */
        $junction = $this->fetchTable('CountryVisibilities');
        if ($junction->exists([
            'country_id' => $countryId,
            'visible_country_id' => $visibleCountryId,
        ])) {
            $fields = ['visible' => true];
            if ($preferredPos !== null) {
                $fields['pos'] = $preferredPos;
            }
            $junction->updateAll($fields, [
                'country_id' => $countryId,
                'visible_country_id' => $visibleCountryId,
            ]);

            return;
        }

        $entity = $junction->newEntity([
            'country_id' => $countryId,
            'visible_country_id' => $visibleCountryId,
            'visible' => true,
            'pos' => $preferredPos ?? ($countryId === $visibleCountryId ? 1 : 1000),
        ]);
        $junction->save($entity);
    }

    /**
     * After creating a country: only self-visibility (own language on tabs).
     * Extra languages are chosen later on the Countries form.
     */
    public function seedDefaultVisibilitiesForCountry(int $countryId): void
    {
        $this->ensureSelfVisibility($countryId);
    }
}
