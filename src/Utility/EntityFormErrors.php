<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Datasource\EntityInterface;
use function Cake\I18n\__;

/**
 * Flatten entity validation / rule errors for Flash toasts and form summaries.
 *
 * Convention: on save failure always show *why* (field messages), not only a generic Flash.
 * See doc/admin-konvenciok.md → „Mentés hibakezelés”; rule: admin-form-save-errors.mdc.
 */
class EntityFormErrors
{
    /**
     * @param array<string, mixed> $errors Entity::getErrors() tree
     * @param array<string, string> $fieldLabels Optional human labels (field => label)
     * @return list<string>
     */
    public static function flatLines(array $errors, array $fieldLabels = []): array
    {
        $lines = [];
        foreach ($errors as $field => $fieldErrors) {
            $label = $fieldLabels[(string)$field] ?? (string)$field;
            foreach (static::flattenMessages($fieldErrors) as $message) {
                $message = trim($message);
                if ($message === '') {
                    continue;
                }
                $lines[] = $label . ': ' . $message;
            }
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param array<string, string> $fieldLabels
     * @return list<string>
     */
    public static function labeledLines(EntityInterface $entity, array $fieldLabels = []): array
    {
        return static::flatLines($entity->getErrors(), $fieldLabels);
    }

    /**
     * Single Flash toast string (semicolon-separated).
     *
     * @param array<string, string> $fieldLabels
     */
    public static function flashText(
        EntityInterface|array $entityOrErrors,
        array $fieldLabels = [],
        ?string $fallback = null,
    ): string {
        $errors = $entityOrErrors instanceof EntityInterface
            ? $entityOrErrors->getErrors()
            : $entityOrErrors;
        $lines = static::flatLines(is_array($errors) ? $errors : [], $fieldLabels);
        if ($lines === []) {
            return $fallback ?? (string)__('The record could not be saved. Please try again.');
        }

        return implode('; ', $lines);
    }

    /**
     * Multiline summary (e.g. form alert box).
     *
     * @param array<string, string> $fieldLabels
     */
    public static function summaryText(
        EntityInterface $entity,
        array $fieldLabels = [],
        string $fallback = '',
    ): string {
        $lines = static::labeledLines($entity, $fieldLabels);
        if ($lines === []) {
            return $fallback;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, string>
     */
    public static function profileFieldLabels(): array
    {
        return [
            'first_name' => (string)__('Name'),
            'phone' => (string)__('Phone'),
            'country_id' => (string)__('Country'),
            'club_id' => (string)__('Club'),
            'avatar' => (string)__('Profile picture'),
        ];
    }

    /**
     * @param array|string $errors
     * @return list<string>
     */
    protected static function flattenMessages(array|string $errors): array
    {
        if (is_string($errors)) {
            return [$errors];
        }

        $messages = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                foreach (static::flattenMessages($error) as $nested) {
                    $messages[] = $nested;
                }
                continue;
            }
            $messages[] = (string)$error;
        }

        return $messages;
    }
}
