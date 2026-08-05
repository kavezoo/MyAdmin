<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Language;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\AdminCountry;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Languages — UI locales for login language select (Translate on name).
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 */
class LanguagesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * Soft-protected locale codes (seed / fallback always expects these).
     *
     * @var list<string>
     */
    public const PROTECTED_CODES = [
        'en_GB',
        'hu_HU',
    ];

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('languages');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(Language::class);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'strategyClass' => EavStrategy::class,
            'fields' => ['name'],
            'defaultLocale' => 'en_GB',
            'allowEmptyTranslations' => false,
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('code')
            ->maxLength('code', 10)
            ->requirePresence('code', 'create')
            ->notEmptyString('code');

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
            ->boolean('visible')
            ->allowEmptyString('visible');

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);

        return $rules;
    }

    /**
     * Cannot delete if protected code, or any country primary locale matches.
     */
    public function canDelete(EntityInterface $language): bool
    {
        $code = trim((string)$language->get('code'));
        if ($code === '' || in_array($code, self::PROTECTED_CODES, true)) {
            return false;
        }

        return !$this->isUsedAsCountryLocale($code);
    }

    public function isUsedAsCountryLocale(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        try {
            $countries = TableRegistry::getTableLocator()->get('Countries');

            return $countries->exists(['Countries.locale' => $code]);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return bool|null
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): ?bool
    {
        if (!$this->canDelete($entity)) {
            $code = trim((string)$entity->get('code'));
            if (in_array($code, self::PROTECTED_CODES, true)) {
                $entity->setError('_delete', [
                    __('Cannot delete this language because it is required by the system ({0}).', $code),
                ]);
            } else {
                $entity->setError('_delete', [
                    __('Cannot delete this language because one or more countries use it as primary locale.'),
                ]);
            }
            $event->stopPropagation();

            return false;
        }

        return null;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        $id = (int)$entity->get('id');
        if ($id < 1) {
            return;
        }

        try {
            TableRegistry::getTableLocator()->get('I18n')->deleteAll([
                'model' => 'Languages',
                'foreign_key' => $id,
            ]);
        } catch (\Throwable) {
            // i18n table may be missing in stripped installs
        }
    }

    /**
     * Visible languages with translated names for the UI locale.
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
            ->where(['Languages.visible' => true])
            ->orderBy([
                'CASE WHEN Languages.code = \'en_GB\' THEN 0 WHEN Languages.code LIKE \'en_%\' THEN 1 ELSE 2 END' => 'ASC',
                'Languages.pos' => 'ASC',
                'Languages.code' => 'ASC',
            ]);
    }
}
