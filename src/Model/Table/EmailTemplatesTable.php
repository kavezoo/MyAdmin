<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Email templates — one row per (language_id, slug).
 *
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

        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'joinType' => 'INNER',
            'className' => 'Languages',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
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
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);
        $rules->add($rules->isUnique(['language_id', 'slug']), [
            'errorField' => 'slug',
            'message' => __('This template slug already exists for the selected language.'),
        ]);

        return $rules;
    }

    /**
     * Enabled template for language + slug, or null.
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
            ->first();
    }
}
