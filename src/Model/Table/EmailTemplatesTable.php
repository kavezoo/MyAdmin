<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Email templates — one row per (country_id, language_id, slug).
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 */
class EmailTemplatesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('email_templates');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\EmailTemplate::class);

        $this->addBehavior('Timestamp');
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'joinType' => 'INNER',
            'className' => 'Languages',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->integer('language_id')
            ->requirePresence('language_id', 'create')
            ->notEmptyString('language_id');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 100)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->regex('slug', '/^[a-z0-9_]+$/', __('Use lowercase letters, numbers and underscores only.'));

        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');

        $validator
            ->scalar('body_html')
            ->requirePresence('body_html', 'create')
            ->notEmptyString('body_html');

        $validator
            ->scalar('body_text')
            ->requirePresence('body_text', 'create')
            ->notEmptyString('body_text');

        $validator
            ->boolean('enabled')
            ->allowEmptyString('enabled');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);
        $rules->add($rules->isUnique(['country_id', 'language_id', 'slug']), [
            'errorField' => 'slug',
            'message' => __('This template slug already exists for the selected country and language.'),
        ]);

        return $rules;
    }

    /**
     * Enabled template for country + language + slug, or null.
     */
    public function findEnabledByCountryLanguageAndSlug(
        int $countryId,
        int $languageId,
        string $slug,
    ): ?\Cake\Datasource\EntityInterface {
        if ($countryId < 1 || $languageId < 1 || $slug === '') {
            return null;
        }

        return $this->find()
            ->where([
                'EmailTemplates.country_id' => $countryId,
                'EmailTemplates.language_id' => $languageId,
                'EmailTemplates.slug' => $slug,
                'EmailTemplates.enabled' => true,
            ])
            ->first();
    }

    /**
     * @deprecated Use findEnabledByCountryLanguageAndSlug
     */
    public function findEnabledByLanguageAndSlug(int $languageId, string $slug): ?\Cake\Datasource\EntityInterface
    {
        if ($languageId < 1 || $slug === '') {
            return null;
        }

        return $this->find()
            ->where([
                'EmailTemplates.language_id' => $languageId,
                'EmailTemplates.slug' => $slug,
                'EmailTemplates.enabled' => true,
            ])
            ->orderBy(['EmailTemplates.country_id' => 'ASC'])
            ->first();
    }
}
