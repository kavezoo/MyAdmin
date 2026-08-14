<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Utility\ActivityLogLocale;
use App\Utility\EventLogChanges;
use App\Utility\EventLogger;
use App\Utility\MembershipFee;
use App\Auth\MembershipProfile;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior;

/**
 * Log entity create / update / delete into event_logs.
 *
 * Skips language / meta tables — only meaningful domain data changes.
 * Dirty fields are stored as from → to (secrets redacted).
 */
class EventLogBehavior extends Behavior
{
    /**
     * Table aliases that never write event_logs (i18n noise, self).
     *
     * @var list<string>
     */
    protected const SKIP_ALIASES = [
        'EventLogs',
        'Languages',
        'I18n',
    ];

    /**
     * Fields ignored in change diffs (noise / auto).
     *
     * @var list<string>
     */
    protected const SKIP_FIELDS = [
        'created',
        'modified',
        'last_login',
        'lockout_time',
        'token_expires',
        'login_token_date',
        'activation_date',
        'tos_date',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'enabled' => true,
        'skipOption' => 'skipEventLog',
    ];

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $options->offsetSet('_eventLogWasNew', $entity->isNew());
        $options->offsetSet('_eventLogChanges', $this->captureChanges($entity));
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->getConfig('enabled') || !empty($options[$this->getConfig('skipOption')])) {
            return;
        }

        $table = $this->table();
        $alias = $table->getAlias();
        if ($this->shouldSkipAlias($alias)) {
            return;
        }

        $created = !empty($options['_eventLogWasNew']);
        /** @var array<string, array{from: mixed, to: mixed}> $changes */
        $changes = $options['_eventLogChanges'] ?? [];
        if (is_array($changes) && $changes !== []) {
            $changes = $this->refreshToValues($entity, $changes);
            $changes = $this->filterMeaningfulChanges($changes);
        } else {
            $changes = [];
        }
        if (!$created && $changes === []) {
            return;
        }

        $pk = $entity->get($table->getPrimaryKey());
        $label = $this->entityLabel($entity);
        $changeSummary = EventLogChanges::summary($changes, 350);

        $countryId = 0;
        if ($entity->get('country_id') !== null && $entity->get('country_id') !== '') {
            $countryId = (int)$entity->get('country_id');
        }

        $feeChanges = [];
        $clubFeeChanges = [];
        $membershipChanges = [];
        if ($alias === 'Users' && $changes !== []) {
            foreach (MembershipFee::DATE_FIELDS as $feeField) {
                if (isset($changes[$feeField])) {
                    $feeChanges[$feeField] = $changes[$feeField];
                }
            }
            foreach (
                [
                    MembershipProfile::FIELD_JOINED,
                    'role',
                    'membership_status',
                    'enabled',
                ] as $membershipField
            ) {
                if (isset($changes[$membershipField])) {
                    $membershipChanges[$membershipField] = $changes[$membershipField];
                }
            }
        } elseif ($alias === 'Clubs' && $changes !== []) {
            foreach (MembershipFee::CLUB_ENTITY_DATE_FIELDS as $feeField) {
                if (isset($changes[$feeField])) {
                    $clubFeeChanges[$feeField] = $changes[$feeField];
                }
            }
        }

        if ($membershipChanges !== []) {
            if ($countryId > 0) {
                $description = ActivityLogLocale::runForCountry(
                    $countryId,
                    fn () => MembershipProfile::activityDescriptions($entity, $membershipChanges, $created)
                );
            } else {
                $description = MembershipProfile::activityDescriptions($entity, $membershipChanges, $created);
            }
            if ($description === '' && $feeChanges !== []) {
                $description = $countryId > 0
                    ? ActivityLogLocale::runForCountry(
                        $countryId,
                        fn () => MembershipFee::activityDescriptions($entity, $feeChanges, $created)
                    )
                    : MembershipFee::activityDescriptions($entity, $feeChanges, $created);
            }
            if ($description === '') {
                $description = sprintf(
                    '%s %s #%s%s',
                    $created ? 'Created' : 'Updated',
                    $alias,
                    (string)$pk,
                    $label !== '' ? ' (' . $label . ')' : ''
                );
                if ($changeSummary !== '') {
                    $description .= ': ' . $changeSummary;
                }
            } elseif ($feeChanges !== []) {
                $feeDesc = $countryId > 0
                    ? ActivityLogLocale::runForCountry(
                        $countryId,
                        fn () => MembershipFee::activityDescriptions($entity, $feeChanges, $created)
                    )
                    : MembershipFee::activityDescriptions($entity, $feeChanges, $created);
                if ($feeDesc !== '') {
                    $description .= '; ' . $feeDesc;
                }
            }
        } elseif ($feeChanges !== []) {
            // Membership fee date edit (user club / national) — gated by ActivityLogSetup in EventLogger
            if ($countryId > 0) {
                $description = ActivityLogLocale::runForCountry(
                    $countryId,
                    fn () => MembershipFee::activityDescriptions($entity, $feeChanges, $created)
                );
            } else {
                $description = MembershipFee::activityDescriptions($entity, $feeChanges, $created);
            }
            if ($description === '') {
                $description = sprintf(
                    '%s %s #%s%s',
                    $created ? 'Created' : 'Updated',
                    $alias,
                    (string)$pk,
                    $label !== '' ? ' (' . $label . ')' : ''
                );
                if ($changeSummary !== '') {
                    $description .= ': ' . $changeSummary;
                }
            }
        } elseif ($clubFeeChanges !== []) {
            if ($countryId > 0) {
                $description = ActivityLogLocale::runForCountry(
                    $countryId,
                    fn () => MembershipFee::clubEntityActivityDescriptions($entity, $clubFeeChanges, $created)
                );
            } else {
                $description = MembershipFee::clubEntityActivityDescriptions($entity, $clubFeeChanges, $created);
            }
            if ($description === '') {
                $description = sprintf(
                    '%s %s #%s%s',
                    $created ? 'Created' : 'Updated',
                    $alias,
                    (string)$pk,
                    $label !== '' ? ' (' . $label . ')' : ''
                );
                if ($changeSummary !== '') {
                    $description .= ': ' . $changeSummary;
                }
            }
        } else {
            $description = sprintf(
                '%s %s #%s%s',
                $created ? 'Created' : 'Updated',
                $alias,
                (string)$pk,
                $label !== '' ? ' (' . $label . ')' : ''
            );
            if ($changeSummary !== '') {
                $description .= ': ' . $changeSummary;
            }
        }

        $logCountryId = $countryId > 0 ? $countryId : null;

        EventLogger::log([
            'module' => $alias,
            'action' => $created ? 'add' : 'edit',
            'entity' => $alias,
            'entity_id' => $pk,
            'description' => $description,
            'country_id' => $logCountryId,
            'request_data' => [
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->getConfig('enabled') || !empty($options[$this->getConfig('skipOption')])) {
            return;
        }

        $table = $this->table();
        $alias = $table->getAlias();
        if ($this->shouldSkipAlias($alias)) {
            return;
        }

        $pk = $entity->get($table->getPrimaryKey());
        $label = $this->entityLabel($entity);
        $snapshot = $this->snapshotEntity($entity);

        EventLogger::log([
            'module' => $alias,
            'action' => 'delete',
            'entity' => $alias,
            'entity_id' => $pk,
            'description' => sprintf(
                'Deleted %s #%s%s',
                $alias,
                (string)$pk,
                $label !== '' ? ' (' . $label . ')' : ''
            ),
            'request_data' => [
                'deleted' => $snapshot,
            ],
        ]);
    }

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function captureChanges(EntityInterface $entity): array
    {
        $dirty = [];
        foreach ($entity->getDirty() as $field) {
            if (!in_array($field, self::SKIP_FIELDS, true)) {
                $dirty[] = $field;
            }
        }
        if ($dirty === []) {
            return [];
        }

        $originals = $entity->isNew() ? [] : $entity->extractOriginalChanged($dirty);
        $changes = [];
        foreach ($dirty as $field) {
            if ($field === '_translations') {
                continue;
            }
            $from = $entity->isNew() ? null : ($originals[$field] ?? $entity->getOriginal($field));
            $to = $entity->get($field);

            if (EventLogger::isSecretField($field)) {
                $changes[$field] = [
                    'from' => $this->secretPresence($from),
                    'to' => '[changed]',
                ];
                continue;
            }

            if ($field === 'avatar' || $field === 'logo') {
                $fromNorm = $this->normalizeAvatarValue($from);
                $toNorm = $this->normalizeAvatarValue($to);
                if ($fromNorm === $toNorm) {
                    continue;
                }
                $changes[$field] = [
                    'from' => $fromNorm,
                    'to' => $toNorm,
                ];
                continue;
            }

            $fromNorm = $this->normalizeValue($from);
            $toNorm = $this->normalizeValue($to);
            if ($fromNorm === $toNorm || EventLogChanges::isEmptyEmpty($fromNorm, $toNorm)) {
                continue;
            }

            $changes[$field] = [
                'from' => $fromNorm,
                'to' => $toNorm,
            ];
        }

        foreach ($this->captureTranslationChanges($entity) as $field => $pair) {
            $changes[$field] = $pair;
        }

        return $changes;
    }

    /**
     * @param array<string, array{from: mixed, to: mixed}> $changes
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function refreshToValues(EntityInterface $entity, array $changes): array
    {
        foreach ($changes as $field => $pair) {
            if (EventLogger::isSecretField($field)) {
                continue;
            }
            if (!$entity->has($field) && !array_key_exists($field, $entity->toArray())) {
                continue;
            }
            if ($field === 'avatar' || $field === 'logo') {
                $changes[$field]['to'] = $this->normalizeAvatarValue($entity->get($field));
                continue;
            }
            $changes[$field]['to'] = $this->normalizeValue($entity->get($field));
        }

        return $changes;
    }

    /**
     * @param array<string, array{from: mixed, to: mixed}> $changes
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function filterMeaningfulChanges(array $changes): array
    {
        $out = [];
        foreach ($changes as $field => $pair) {
            if (EventLogger::isSecretField((string)$field)) {
                $out[$field] = $pair;
                continue;
            }
            $from = $pair['from'] ?? null;
            $to = $pair['to'] ?? null;
            if ($from === $to || EventLogChanges::isEmptyEmpty($from, $to)) {
                continue;
            }
            $out[$field] = $pair;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotEntity(EntityInterface $entity): array
    {
        $out = [];
        foreach ($entity->toArray() as $field => $value) {
            if (in_array($field, self::SKIP_FIELDS, true)) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            if (EventLogger::isSecretField((string)$field)) {
                $out[(string)$field] = $this->secretPresence($value);
                continue;
            }
            $out[(string)$field] = $this->normalizeValue($value);
        }

        return $out;
    }

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    protected function captureTranslationChanges(EntityInterface $entity): array
    {
        if (!$entity->isDirty('_translations')) {
            return [];
        }

        $to = $entity->get('_translations');
        if (!is_array($to)) {
            return [];
        }

        $from = $entity->getOriginal('_translations');
        if (!is_array($from)) {
            $from = [];
        }

        $changes = [];
        foreach ($to as $locale => $fields) {
            if (!is_string($locale) || !is_array($fields)) {
                continue;
            }
            foreach ($fields as $transField => $newVal) {
                if (!is_string($transField)) {
                    continue;
                }
                $oldVal = is_array($from[$locale] ?? null) ? ($from[$locale][$transField] ?? null) : null;
                $fromNorm = $this->normalizeValue($oldVal);
                $toNorm = $this->normalizeValue($newVal);
                if ($fromNorm === $toNorm || EventLogChanges::isEmptyEmpty($fromNorm, $toNorm)) {
                    continue;
                }
                $changes[$transField . ':' . $locale] = [
                    'from' => $fromNorm,
                    'to' => $toNorm,
                ];
            }
        }

        return $changes;
    }

    protected function normalizeAvatarValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '[empty]';
        }

        return '[set]';
    }

    protected function secretPresence(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '[empty]';
        }

        return '[set]';
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof DateTime || $value instanceof Date) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_string($value) && strlen($value) > 200) {
            return substr($value, 0, 200) . '…';
        }
        if (is_array($value) || is_object($value)) {
            return '[complex]';
        }

        return $value;
    }

    protected function shouldSkipAlias(string $alias): bool
    {
        return in_array($alias, self::SKIP_ALIASES, true);
    }

    protected function entityLabel(EntityInterface $entity): string
    {
        foreach (['name', 'title', 'email', 'username', 'iso2', 'slug'] as $field) {
            if ($entity->has($field)) {
                $val = trim((string)$entity->get($field));
                if ($val !== '') {
                    return mb_substr($val, 0, 80);
                }
            }
        }

        return '';
    }
}
