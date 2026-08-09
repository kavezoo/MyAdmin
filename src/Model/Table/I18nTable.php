<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * I18n Model — CakePHP Translate EAV table.
 *
 * Used by TranslateBehavior (EavStrategy) for Countries.name, Continents.name, etc.
 *
 * @method \App\Model\Entity\I18nTranslation newEmptyEntity()
 * @method \App\Model\Entity\I18nTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 */
class I18nTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('i18n');
        $this->setDisplayField('field');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\I18nTranslation::class);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('locale')
            ->maxLength('locale', 6)
            ->requirePresence('locale', 'create')
            ->notEmptyString('locale');

        $validator
            ->scalar('model')
            ->maxLength('model', 255)
            ->requirePresence('model', 'create')
            ->notEmptyString('model');

        $validator
            ->scalar('foreign_key')
            ->maxLength('foreign_key', 36)
            ->requirePresence('foreign_key', 'create')
            ->notEmptyString('foreign_key');

        $validator
            ->scalar('field')
            ->maxLength('field', 255)
            ->requirePresence('field', 'create')
            ->notEmptyString('field');

        $validator
            ->scalar('content')
            ->allowEmptyString('content');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }
}
