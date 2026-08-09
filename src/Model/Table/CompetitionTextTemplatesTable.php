<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Competition announcement text templates (country-scoped).
 *
 * Only `description` is translated / applied to competitions; name/title/… belong on the competition form.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 */
class CompetitionTextTemplatesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @var list<string>
     */
    public const TRANSLATE_FIELDS = [
        'description',
    ];

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('competition_text_templates');
        $this->setDisplayField('label');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\CompetitionTextTemplate::class);

        $this->addBehavior('Timestamp');
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->addBehavior('Translate', [
            'strategyClass' => EavStrategy::class,
            'fields' => self::TRANSLATE_FIELDS,
            'defaultLocale' => 'en_GB',
            'allowEmptyTranslations' => false,
        ]);

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->scalar('label')
            ->maxLength('label', 150)
            ->requirePresence('label', 'create')
            ->notEmptyString('label');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('enabled')
            ->allowEmptyString('enabled');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);

        return $rules;
    }

    /**
     * Select2 options for competition form (enabled + visible, country-scoped).
     *
     * @return array<int, string>
     */
    public function optionsForCountry(int $countryId): array
    {
        if ($countryId < 1) {
            return [];
        }

        return $this->find()
            ->select(['id', 'label'])
            ->where([
                'CompetitionTextTemplates.country_id' => $countryId,
                'CompetitionTextTemplates.enabled' => true,
                'CompetitionTextTemplates.visible' => true,
            ])
            ->orderBy([
                'CompetitionTextTemplates.pos' => 'ASC',
                'CompetitionTextTemplates.label' => 'ASC',
                'CompetitionTextTemplates.id' => 'ASC',
            ])
            ->all()
            ->combine('id', 'label')
            ->toArray();
    }
}
