<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\AdminCountry;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Countries Model — matches `countries` schema.
 *
 * Columns: iso2, name, locale, continent_id, visible, pos, user_count, created, modified
 * - `name` Translate EAV → i18n (model=Countries)
 * - belongsTo Continents via continent_id
 * - Admin may change only visible + pos
 *
 * @property \App\Model\Table\ContinentsTable&\Cake\ORM\Association\BelongsTo $Continents
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
            ->scalar('locale')
            ->maxLength('locale', 10)
            ->requirePresence('locale', 'create')
            ->notEmptyString('locale');

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

        return $query
            ->where(['Countries.visible' => true])
            ->orderBy(['Countries.name' => 'ASC']);
    }
}
